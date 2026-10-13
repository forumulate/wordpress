<?php
/**
 * The three script embeds Forumulate serves: comments.js, widget.js and
 * changelog.js (all under {api}/embed/).
 *
 * Each embed reads its configuration from data-* attributes on its own
 * <script> tag and mounts into the element named by `data-mount`. So each
 * placement renders an empty container here and enqueues the script under a
 * handle of its own (two comment threads on one page are two scripts), and the
 * `script_loader_tag` filter adds the data-* attributes to exactly those tags.
 * Scripts go through wp_enqueue_script like any other, so caching and
 * optimisation plugins see them.
 *
 * @package Forumulate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Script embeds.
 */
class Forumulate_Embeds {

	/**
	 * Data attributes per enqueued handle.
	 *
	 * @var array<string, array<string, string>>
	 */
	private static $attributes = array();

	/**
	 * Placement counter, for unique handles and mount ids.
	 *
	 * @var int
	 */
	private static $count = 0;

	/**
	 * Hook up.
	 */
	public static function init() {
		add_filter( 'script_loader_tag', array( __CLASS__, 'add_data_attributes' ), 10, 2 );
	}

	/**
	 * Render one embed.
	 *
	 * @param string $kind 'comments', 'widget' or 'changelog'.
	 * @param array  $args {
	 *     Optional overrides.
	 *
	 *     @type string $space       Space id or slug (defaults per kind from settings).
	 *     @type string $topic       comments: a topic id to show instead of resolving by URL.
	 *     @type string $article_url comments: the URL the thread belongs to.
	 *     @type string $mode        widget: 'search' or 'ask'.
	 *     @type string $placeholder widget: input placeholder.
	 *     @type string $theme       comments: 'light' or 'dark'.
	 * }
	 * @return string HTML.
	 */
	public static function render( $kind, $args = array() ) {
		$files = array(
			'comments'  => 'comments.js',
			'widget'    => 'widget.js',
			'changelog' => 'changelog.js',
		);
		if ( ! isset( $files[ $kind ] ) ) {
			return '';
		}

		$settings = Forumulate_Options::all();
		$space    = self::space_for( $kind, isset( $args['space'] ) ? $args['space'] : '' );
		$org      = (string) $settings['org'];
		if ( ! Forumulate_Options::is_ref( $org ) || '' === $space ) {
			return self::notice( __( 'Forumulate: set the organization and space in Settings → Forumulate.', 'forumulate' ) );
		}

		++self::$count;
		$mount  = 'forumulate-' . $kind . '-' . self::$count;
		$handle = 'forumulate-' . $kind . '-' . self::$count;

		$data = array(
			'org'   => $org,
			'space' => $space,
			'mount' => '#' . $mount,
			'api'   => Forumulate_Options::api_base(),
		);
		foreach ( array( 'accent', 'radius', 'font' ) as $key ) {
			if ( '' !== (string) $settings[ $key ] ) {
				$data[ $key ] = (string) $settings[ $key ];
			}
		}

		if ( 'comments' === $kind ) {
			$theme         = isset( $args['theme'] ) && in_array( $args['theme'], array( 'light', 'dark' ), true ) ? $args['theme'] : $settings['theme'];
			$data['theme'] = $theme;
			if ( ! empty( $args['topic'] ) && Forumulate_Options::is_ref( $args['topic'] ) ) {
				$data['topic'] = (string) $args['topic'];
			}
			if ( ! empty( $args['article_url'] ) ) {
				$data['article-url'] = esc_url_raw( $args['article_url'] );
			}
		}
		if ( 'widget' === $kind ) {
			$data['mode'] = ( isset( $args['mode'] ) && 'ask' === $args['mode'] ) ? 'ask' : 'search';
			if ( ! empty( $args['placeholder'] ) ) {
				$data['placeholder'] = sanitize_text_field( $args['placeholder'] );
			}
		}

		self::$attributes[ $handle ] = $data;
		wp_enqueue_script(
			$handle,
			Forumulate_Options::api_base() . '/embed/' . $files[ $kind ],
			array(),
			FORUMULATE_VERSION . '.' . self::$count,
			true
		);

		$labels = array(
			'comments'  => __( 'Comments', 'forumulate' ),
			'widget'    => __( 'Search', 'forumulate' ),
			'changelog' => __( 'What’s new', 'forumulate' ),
		);
		return sprintf(
			'<div id="%1$s" class="forumulate-embed forumulate-embed--%2$s" aria-label="%3$s"></div>',
			esc_attr( $mount ),
			esc_attr( $kind ),
			esc_attr( $labels[ $kind ] )
		);
	}

	/**
	 * The space an embed of `$kind` uses: the explicit one, else the kind's own
	 * default from settings, else the general default space.
	 *
	 * @param string $kind     Embed kind.
	 * @param string $explicit Space passed by the shortcode or block.
	 * @return string
	 */
	public static function space_for( $kind, $explicit ) {
		$explicit = Forumulate_Options::clean_ref( (string) $explicit );
		if ( '' !== $explicit ) {
			return $explicit;
		}
		$settings = Forumulate_Options::all();
		$own      = $kind . '_space';
		if ( isset( $settings[ $own ] ) && '' !== $settings[ $own ] ) {
			return (string) $settings[ $own ];
		}
		return (string) $settings['space'];
	}

	/**
	 * `script_loader_tag` filter: add data-* attributes to our handles only.
	 *
	 * @param string $tag    The <script> tag.
	 * @param string $handle Script handle.
	 * @return string
	 */
	public static function add_data_attributes( $tag, $handle ) {
		if ( ! isset( self::$attributes[ $handle ] ) ) {
			return $tag;
		}
		$attrs = '';
		foreach ( self::$attributes[ $handle ] as $name => $value ) {
			$attrs .= sprintf( ' data-%s="%s"', esc_attr( $name ), esc_attr( $value ) );
		}
		return preg_replace( '/<script\b/', '<script' . $attrs, $tag, 1 );
	}

	/**
	 * A configuration hint, shown only to people who can fix it.
	 *
	 * @param string $message Plain text.
	 * @return string
	 */
	public static function notice( $message ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}
		return '<p class="forumulate-notice">' . esc_html( $message ) . '</p>';
	}
}
