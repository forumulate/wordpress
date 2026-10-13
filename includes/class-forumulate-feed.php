<?php
/**
 * A list of recent posts from a Forumulate blog, changelog or docs space,
 * read server-side from the public read API and cached in a transient.
 *
 *  - blog:      GET /public/orgs/{org}/spaces/{space}/blog/feed.json      (JSON Feed 1.1)
 *  - changelog: GET /public/orgs/{org}/spaces/{space}/changelog/feed.json (JSON Feed 1.1)
 *  - docs:      GET /public/orgs/{org}/spaces/{space}/updates             (recently updated pages)
 *
 * The JSON feeds carry each item's real address. Docs updates carry only a
 * slug, so a docs link is the space's published address plus the slug: the
 * "Docs address" setting when it is filled in, otherwise looked up with the
 * token (GET /orgs/{org}/spaces/{space}/addresses, the primary address), and
 * otherwise the titles are listed without links. The plugin never assembles a
 * reader URL out of ids: a space on a custom domain is not read there.
 *
 * A failed fetch keeps serving the last good copy (kept for a day) rather than
 * an empty box, and is retried after a minute rather than on every page view.
 *
 * @package Forumulate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Feed reader.
 */
class Forumulate_Feed {

	const GENERATION = 'forumulate_feed_generation';

	/**
	 * Invalidate every cached feed by moving to a new cache generation; the old
	 * transients are simply never read again and expire on their own.
	 */
	public static function flush() {
		update_option( self::GENERATION, (int) get_option( self::GENERATION, 0 ) + 1, false );
	}

	/**
	 * Render a feed.
	 *
	 * @param array $args {
	 *     Feed options.
	 *
	 *     @type string $type   'blog', 'changelog' or 'docs'.
	 *     @type string $space   Space id or slug (defaults to the general space).
	 *     @type int    $limit   Items to show, 1-20.
	 *     @type bool   $excerpt Show excerpts and dates.
	 *     @type string $title   Optional heading.
	 * }
	 * @return string HTML.
	 */
	public static function render( $args ) {
		$type    = isset( $args['type'] ) && in_array( $args['type'], array( 'blog', 'changelog', 'docs' ), true ) ? $args['type'] : 'blog';
		$space   = Forumulate_Embeds::space_for( 'changelog' === $type ? 'changelog' : 'feed', isset( $args['space'] ) ? $args['space'] : '' );
		$limit   = isset( $args['limit'] ) ? max( 1, min( 20, absint( $args['limit'] ) ) ) : 5;
		$excerpt = ! isset( $args['excerpt'] ) || ! in_array( $args['excerpt'], array( false, 0, '0', 'false', 'no' ), true );
		$title   = isset( $args['title'] ) ? sanitize_text_field( $args['title'] ) : '';

		$items = self::items( $type, $space );
		if ( is_wp_error( $items ) ) {
			return Forumulate_Embeds::notice(
				/* translators: %s: error message. */
				sprintf( __( 'Forumulate feed unavailable: %s', 'forumulate' ), $items->get_error_message() )
			);
		}
		if ( empty( $items ) ) {
			return '<div class="forumulate-feed forumulate-feed--empty"><p>' . esc_html__( 'Nothing published yet.', 'forumulate' ) . '</p></div>';
		}

		$html = '<div class="forumulate-feed forumulate-feed--' . esc_attr( $type ) . '">';
		if ( '' !== $title ) {
			$html .= '<h3 class="forumulate-feed__title">' . esc_html( $title ) . '</h3>';
		}
		$html .= '<ul class="forumulate-feed__list">';
		foreach ( array_slice( $items, 0, $limit ) as $item ) {
			$html .= '<li class="forumulate-feed__item">';
			$label = esc_html( $item['title'] );
			$html .= '' !== $item['url']
				? '<a class="forumulate-feed__link" href="' . esc_url( $item['url'] ) . '">' . $label . '</a>'
				: '<span class="forumulate-feed__link">' . $label . '</span>';
			if ( $excerpt && '' !== $item['date'] ) {
				$time  = strtotime( $item['date'] );
				$html .= $time
					? ' <time class="forumulate-feed__date" datetime="' . esc_attr( gmdate( 'c', $time ) ) . '">' . esc_html( date_i18n( get_option( 'date_format' ), $time ) ) . '</time>'
					: '';
			}
			if ( $excerpt && '' !== $item['summary'] ) {
				$html .= '<p class="forumulate-feed__excerpt">' . esc_html( $item['summary'] ) . '</p>';
			}
			$html .= '</li>';
		}
		$html .= '</ul></div>';
		return $html;
	}

	/**
	 * Normalised items, cached.
	 *
	 * @param string $type  Feed type.
	 * @param string $space Space id or slug.
	 * @return array<int, array{title: string, url: string, date: string, summary: string}>|WP_Error
	 */
	public static function items( $type, $space ) {
		$path = Forumulate_Api::public_space_path( $space );
		if ( '' === $path ) {
			return new WP_Error( 'forumulate_config', __( 'set the organization and space in Settings → Forumulate.', 'forumulate' ) );
		}

		$key   = 'forumulate_feed_' . md5( implode( '|', array( (int) get_option( self::GENERATION, 0 ), Forumulate_Options::api_base(), $path, $type ) ) );
		$fresh = get_transient( $key );
		if ( is_array( $fresh ) ) {
			return $fresh;
		}
		$stale = get_transient( $key . '_last' );
		if ( false !== get_transient( $key . '_retry' ) ) {
			return is_array( $stale ) ? $stale : new WP_Error( 'forumulate_retry', __( 'the last request failed; retrying shortly.', 'forumulate' ) );
		}

		$items = self::fetch( $type, $space, $path );
		if ( is_wp_error( $items ) ) {
			set_transient( $key . '_retry', 1, MINUTE_IN_SECONDS );
			return is_array( $stale ) ? $stale : $items;
		}

		$ttl = max( 1, (int) Forumulate_Options::get( 'feed_cache_minutes' ) ) * MINUTE_IN_SECONDS;
		set_transient( $key, $items, $ttl );
		set_transient( $key . '_last', $items, DAY_IN_SECONDS );
		return $items;
	}

	/**
	 * Fetch and normalise, uncached.
	 *
	 * @param string $type  Feed type.
	 * @param string $space Space id or slug.
	 * @param string $path  Public space path.
	 * @return array|WP_Error
	 */
	private static function fetch( $type, $space, $path ) {
		if ( 'docs' === $type ) {
			$rows = Forumulate_Api::get_json( $path . '/updates', array( 'limit' => '20' ) );
			if ( is_wp_error( $rows ) ) {
				return $rows;
			}
			// The legacy shape is a bare array; the versioned one is { updates: [...] }.
			$rows = isset( $rows['updates'] ) && is_array( $rows['updates'] ) ? $rows['updates'] : $rows;
			$base = self::docs_base( $space );
			$out  = array();
			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) || empty( $row['title'] ) ) {
					continue;
				}
				$slug  = isset( $row['slug'] ) ? (string) $row['slug'] : '';
				$out[] = array(
					'title'   => (string) $row['title'],
					'url'     => ( '' !== $base && '' !== $slug ) ? $base . '/' . rawurlencode( $slug ) : '',
					'date'    => isset( $row['updatedAt'] ) ? (string) $row['updatedAt'] : '',
					'summary' => '',
				);
			}
			return $out;
		}

		$feed = Forumulate_Api::get_json( $path . '/' . $type . '/feed.json' );
		if ( is_wp_error( $feed ) ) {
			return $feed;
		}
		$out = array();
		foreach ( isset( $feed['items'] ) && is_array( $feed['items'] ) ? $feed['items'] : array() as $item ) {
			if ( ! is_array( $item ) || empty( $item['title'] ) ) {
				continue;
			}
			$summary = '';
			if ( ! empty( $item['summary'] ) ) {
				$summary = (string) $item['summary'];
			} elseif ( ! empty( $item['content_text'] ) ) {
				$summary = (string) $item['content_text'];
			}
			$out[] = array(
				'title'   => (string) $item['title'],
				'url'     => isset( $item['url'] ) ? esc_url_raw( (string) $item['url'] ) : '',
				'date'    => isset( $item['date_published'] ) ? (string) $item['date_published'] : '',
				'summary' => wp_trim_words( wp_strip_all_tags( $summary ), 40 ),
			);
		}
		return $out;
	}

	/**
	 * Where a docs space is published, without a trailing slash; '' if unknown.
	 *
	 * @param string $space Space id or slug.
	 * @return string
	 */
	private static function docs_base( $space ) {
		$configured = (string) Forumulate_Options::get( 'docs_base_url' );
		if ( '' !== $configured ) {
			return untrailingslashit( $configured );
		}
		if ( ! Forumulate_Token::has() ) {
			return '';
		}
		$org  = (string) Forumulate_Options::get( 'org' );
		$data = Forumulate_Api::get_json( '/orgs/' . rawurlencode( $org ) . '/spaces/' . rawurlencode( $space ) . '/addresses', array(), true );
		if ( is_wp_error( $data ) || empty( $data['addresses'] ) || ! is_array( $data['addresses'] ) ) {
			return '';
		}
		foreach ( $data['addresses'] as $address ) {
			if ( ! empty( $address['primary'] ) && ! empty( $address['url'] ) ) {
				return untrailingslashit( esc_url_raw( (string) $address['url'] ) );
			}
		}
		return '';
	}
}
