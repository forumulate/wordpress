<?php
/**
 * The plugin's settings: defaults, reads and sanitization.
 *
 * Everything except the API token lives in one option, `forumulate_settings`.
 * The token is kept apart (see Forumulate_Token) so it is never autoloaded,
 * never part of an options export of this array, and never echoed into a form.
 *
 * @package Forumulate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings store.
 */
class Forumulate_Options {

	const OPTION = 'forumulate_settings';

	/**
	 * Default settings. Every key the plugin reads is listed here, so a read
	 * never has to guess at a missing key.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'api_base'           => 'https://forumulate.com/api',
			'org'                => '',
			'space'              => '',
			'comments_space'     => '',
			'changelog_space'    => '',
			'theme'              => 'light',
			'accent'             => '',
			'radius'             => '',
			'font'               => '',
			'replace_comments'   => 0,
			'comment_post_types' => array( 'post' ),
			'docs_base_url'      => '',
			'feed_cache_minutes' => 10,
		);
	}

	/**
	 * All settings, merged over the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting name.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * The API base with no trailing slash, e.g. https://forumulate.com/api.
	 *
	 * @return string
	 */
	public static function api_base() {
		$base = (string) self::get( 'api_base' );
		return untrailingslashit( '' !== $base ? $base : 'https://forumulate.com/api' );
	}

	/**
	 * Is `$value` a usable Forumulate id or slug? Ids are UUIDs; slugs are
	 * lower-case words and hyphens. Anything else would end up in a URL path.
	 *
	 * @param string $value Candidate.
	 * @return bool
	 */
	public static function is_ref( $value ) {
		return (bool) preg_match( '/^[A-Za-z0-9][A-Za-z0-9_-]{0,127}$/', (string) $value );
	}

	/**
	 * A space reference, or '' when it is not a valid one.
	 *
	 * @param string $value Candidate.
	 * @return string
	 */
	public static function clean_ref( $value ) {
		$value = trim( sanitize_text_field( (string) $value ) );
		return self::is_ref( $value ) ? $value : '';
	}

	/**
	 * Sanitize callback for register_setting().
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$clean = self::defaults();

		$api = isset( $input['api_base'] ) ? esc_url_raw( trim( (string) $input['api_base'] ), array( 'https', 'http' ) ) : '';
		if ( '' !== $api ) {
			$clean['api_base'] = untrailingslashit( $api );
		}

		foreach ( array( 'org', 'space', 'comments_space', 'changelog_space' ) as $key ) {
			$raw           = isset( $input[ $key ] ) ? (string) $input[ $key ] : '';
			$clean[ $key ] = self::clean_ref( $raw );
			if ( '' !== trim( $raw ) && '' === $clean[ $key ] ) {
				add_settings_error(
					self::OPTION,
					'forumulate_bad_' . $key,
					/* translators: %s: the setting's field name. */
					sprintf( __( '%s is not a valid Forumulate id or slug, so it was cleared.', 'forumulate' ), $key )
				);
			}
		}

		$clean['theme'] = ( isset( $input['theme'] ) && 'dark' === $input['theme'] ) ? 'dark' : 'light';

		$accent          = isset( $input['accent'] ) ? sanitize_hex_color( (string) $input['accent'] ) : '';
		$clean['accent'] = $accent ? $accent : '';

		// Radius and font go into an inline style inside the embed, so only a
		// plain CSS length and a font-family list survive.
		$radius          = isset( $input['radius'] ) ? trim( (string) $input['radius'] ) : '';
		$clean['radius'] = preg_match( '/^\d{1,3}(\.\d+)?(px|rem|em|%)$/', $radius ) ? $radius : '';
		$font            = isset( $input['font'] ) ? trim( sanitize_text_field( (string) $input['font'] ) ) : '';
		$clean['font']   = preg_match( '/^[A-Za-z0-9 ,\'"_-]{1,200}$/', $font ) ? $font : '';

		$clean['replace_comments'] = empty( $input['replace_comments'] ) ? 0 : 1;

		$types                       = isset( $input['comment_post_types'] ) && is_array( $input['comment_post_types'] ) ? $input['comment_post_types'] : array();
		$known                       = get_post_types( array( 'public' => true ) );
		$clean['comment_post_types'] = array_values(
			array_filter(
				array_map( 'sanitize_key', $types ),
				function ( $type ) use ( $known ) {
					return isset( $known[ $type ] );
				}
			)
		);

		$docs                   = isset( $input['docs_base_url'] ) ? esc_url_raw( trim( (string) $input['docs_base_url'] ), array( 'https', 'http' ) ) : '';
		$clean['docs_base_url'] = untrailingslashit( $docs );

		$minutes                     = isset( $input['feed_cache_minutes'] ) ? absint( $input['feed_cache_minutes'] ) : 10;
		$clean['feed_cache_minutes'] = max( 1, min( 1440, $minutes ) );

		// The token rides in the same nonce-checked options.php submission but is
		// stored apart, encrypted, and dropped from this array. An empty field
		// means "keep the one saved"; it is never pre-filled.
		if ( current_user_can( 'manage_options' ) && ! Forumulate_Token::from_constant() ) {
			if ( ! empty( $input['clear_token'] ) ) {
				Forumulate_Token::clear();
			} elseif ( isset( $input['token'] ) && '' !== trim( (string) $input['token'] ) ) {
				$token = trim( sanitize_text_field( (string) $input['token'] ) );
				if ( ! Forumulate_Token::set( $token ) ) {
					add_settings_error( self::OPTION, 'forumulate_token', __( 'The token could not be stored: this server has no OpenSSL.', 'forumulate' ) );
				}
			}
		}

		// Settings changed: what was cached under the old org/space is stale.
		Forumulate_Feed::flush();

		return $clean;
	}
}
