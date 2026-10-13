<?php
/**
 * Settings → Forumulate.
 *
 * The form is a normal Settings API page (options.php checks its nonce and
 * the manage_options capability). Two extra buttons, "Check connection" and
 * "Clear feed cache", post to admin-post.php with their own nonces and
 * capability checks.
 *
 * @package Forumulate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin screen.
 */
class Forumulate_Admin {

	const PAGE = 'forumulate';

	/**
	 * Hook up.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_forumulate_check', array( __CLASS__, 'check' ) );
		add_action( 'admin_post_forumulate_flush', array( __CLASS__, 'flush' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( FORUMULATE_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Add the page under Settings.
	 */
	public static function menu() {
		add_options_page(
			__( 'Forumulate', 'forumulate' ),
			__( 'Forumulate', 'forumulate' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'page' )
		);
	}

	/**
	 * "Settings" link on the Plugins screen.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public static function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'forumulate' ) . '</a>' );
		return $links;
	}

	/**
	 * Register the setting, sections and fields.
	 */
	public static function register() {
		register_setting(
			self::PAGE,
			Forumulate_Options::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'Forumulate_Options', 'sanitize' ),
				'default'           => Forumulate_Options::defaults(),
			)
		);

		add_settings_section( 'forumulate_connection', __( 'Connection', 'forumulate' ), array( __CLASS__, 'section_connection' ), self::PAGE );
		add_settings_section( 'forumulate_look', __( 'Look', 'forumulate' ), '__return_false', self::PAGE );
		add_settings_section( 'forumulate_comments', __( 'Comments', 'forumulate' ), array( __CLASS__, 'section_comments' ), self::PAGE );
		add_settings_section( 'forumulate_feeds', __( 'Feeds', 'forumulate' ), '__return_false', self::PAGE );

		$text = array( __CLASS__, 'field_text' );
		self::field( 'org', __( 'Organization id', 'forumulate' ), $text, 'forumulate_connection', __( 'From your Forumulate console address: /o/<organization id>/…', 'forumulate' ) );
		self::field( 'space', __( 'Default space id', 'forumulate' ), $text, 'forumulate_connection', __( 'Used by every embed and feed that does not name its own space.', 'forumulate' ) );
		self::field( 'comments_space', __( 'Comments space id', 'forumulate' ), $text, 'forumulate_connection', __( 'The community space that holds article comments. Empty: the default space.', 'forumulate' ) );
		self::field( 'changelog_space', __( 'Changelog space id', 'forumulate' ), $text, 'forumulate_connection', __( 'For the changelog bell and changelog feeds. Empty: the default space.', 'forumulate' ) );
		self::field( 'token', __( 'API token (optional)', 'forumulate' ), array( __CLASS__, 'field_token' ), 'forumulate_connection' );
		self::field( 'api_base', __( 'API address', 'forumulate' ), $text, 'forumulate_connection', __( 'Leave as https://forumulate.com/api unless Forumulate support tells you otherwise.', 'forumulate' ) );

		self::field( 'theme', __( 'Comments theme', 'forumulate' ), array( __CLASS__, 'field_theme' ), 'forumulate_look' );
		self::field( 'accent', __( 'Accent colour', 'forumulate' ), $text, 'forumulate_look', __( 'A hex colour such as #4818f0. Empty: Forumulate’s default.', 'forumulate' ) );
		self::field( 'radius', __( 'Corner radius', 'forumulate' ), $text, 'forumulate_look', __( 'A CSS length such as 6px.', 'forumulate' ) );
		self::field( 'font', __( 'Font', 'forumulate' ), $text, 'forumulate_look', __( 'A CSS font-family list. Empty: the system font.', 'forumulate' ) );

		self::field( 'replace_comments', __( 'Replace WordPress comments', 'forumulate' ), array( __CLASS__, 'field_replace' ), 'forumulate_comments' );
		self::field( 'comment_post_types', __( 'On these post types', 'forumulate' ), array( __CLASS__, 'field_post_types' ), 'forumulate_comments' );

		self::field( 'docs_base_url', __( 'Docs address', 'forumulate' ), $text, 'forumulate_feeds', __( 'Where your docs space is published, e.g. https://docs.example.com. Docs feed links are this plus each page’s slug. Empty: looked up with the token, or titles are listed without links.', 'forumulate' ) );
		self::field( 'feed_cache_minutes', __( 'Cache feeds for (minutes)', 'forumulate' ), $text, 'forumulate_feeds' );
	}

	/**
	 * Shorthand for add_settings_field().
	 *
	 * @param string   $key      Setting key.
	 * @param string   $label    Label.
	 * @param callable $callback Renderer.
	 * @param string   $section  Section id.
	 * @param string   $help     Help text.
	 */
	private static function field( $key, $label, $callback, $section, $help = '' ) {
		add_settings_field(
			'forumulate_' . $key,
			$label,
			$callback,
			self::PAGE,
			$section,
			array(
				'key'       => $key,
				'help'      => $help,
				'label_for' => 'forumulate_' . $key,
			)
		);
	}

	/**
	 * The `name` attribute for a setting key.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	private static function name( $key ) {
		return Forumulate_Options::OPTION . '[' . $key . ']';
	}

	/**
	 * Connection section intro.
	 */
	public static function section_connection() {
		echo '<p>' . esc_html__( 'Public embeds and feeds need only the organization and space ids. A token is never sent to visitors’ browsers; it is only used by this site to check the connection and to find where a docs space is published.', 'forumulate' ) . '</p>';
	}

	/**
	 * Comments section intro.
	 */
	public static function section_comments() {
		echo '<p>' . esc_html__( 'When on, posts of the chosen types show a Forumulate thread instead of WordPress comments: one topic per post, created on its first visit. Existing WordPress comments are kept and return if you turn this off.', 'forumulate' ) . '</p>';
	}

	/**
	 * A text input.
	 *
	 * @param array $args Field args.
	 */
	public static function field_text( $args ) {
		$key   = $args['key'];
		$value = Forumulate_Options::get( $key );
		printf(
			'<input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s" />',
			esc_attr( 'forumulate_' . $key ),
			esc_attr( self::name( $key ) ),
			esc_attr( is_scalar( $value ) ? (string) $value : '' )
		);
		if ( '' !== $args['help'] ) {
			echo '<p class="description">' . esc_html( $args['help'] ) . '</p>';
		}
	}

	/**
	 * The token input: write-only.
	 */
	public static function field_token() {
		if ( Forumulate_Token::from_constant() ) {
			echo '<p>' . esc_html__( 'Set by the FORUMULATE_TOKEN constant in wp-config.php. Remove the constant to manage it here.', 'forumulate' ) . '</p>';
			return;
		}
		printf(
			'<input type="password" class="regular-text" id="forumulate_token" name="%s" value="" autocomplete="new-password" spellcheck="false" />',
			esc_attr( self::name( 'token' ) )
		);
		if ( Forumulate_Token::is_unreadable() ) {
			echo '<p class="description">' . esc_html__( 'A token is saved but can no longer be read (this site’s security keys changed). Enter it again.', 'forumulate' ) . '</p>';
		} elseif ( Forumulate_Token::has() ) {
			echo '<p class="description">' . esc_html__( 'A token is saved (encrypted). Leave this empty to keep it, or enter a new one to replace it.', 'forumulate' ) . '</p>';
			printf(
				'<label><input type="checkbox" name="%s" value="1" /> %s</label>',
				esc_attr( self::name( 'clear_token' ) ),
				esc_html__( 'Remove the saved token', 'forumulate' )
			);
		} else {
			echo '<p class="description">' . esc_html__( 'Optional. A Personal Access Token with the read scope, from Account → Tokens. You can also define FORUMULATE_TOKEN in wp-config.php instead.', 'forumulate' ) . '</p>';
		}
	}

	/**
	 * Theme select.
	 */
	public static function field_theme() {
		$value = Forumulate_Options::get( 'theme' );
		printf( '<select id="forumulate_theme" name="%s">', esc_attr( self::name( 'theme' ) ) );
		foreach ( array(
			'light' => __( 'Light', 'forumulate' ),
			'dark'  => __( 'Dark', 'forumulate' ),
		) as $option => $label ) {
			printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $option ), selected( $value, $option, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	/**
	 * Replace-comments checkbox.
	 */
	public static function field_replace() {
		printf(
			'<label><input type="checkbox" id="forumulate_replace_comments" name="%1$s" value="1"%2$s /> %3$s</label>',
			esc_attr( self::name( 'replace_comments' ) ),
			checked( (int) Forumulate_Options::get( 'replace_comments' ), 1, false ),
			esc_html__( 'Show Forumulate comments instead of WordPress comments', 'forumulate' )
		);
	}

	/**
	 * Post type checkboxes.
	 */
	public static function field_post_types() {
		$chosen = (array) Forumulate_Options::get( 'comment_post_types' );
		echo '<fieldset id="forumulate_comment_post_types">';
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}
			printf(
				'<label style="display:block"><input type="checkbox" name="%1$s[]" value="%2$s"%3$s /> %4$s</label>',
				esc_attr( self::name( 'comment_post_types' ) ),
				esc_attr( $type->name ),
				checked( in_array( $type->name, $chosen, true ), true, false ),
				esc_html( $type->labels->singular_name )
			);
		}
		echo '</fieldset>';
	}

	/**
	 * The settings page.
	 */
	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Forumulate', 'forumulate' ) . '</h1>';
		self::check_result();
		echo '<form method="post" action="options.php">';
		settings_fields( self::PAGE );
		do_settings_sections( self::PAGE );
		submit_button();
		echo '</form>';

		echo '<h2>' . esc_html__( 'Tools', 'forumulate' ) . '</h2>';
		foreach ( array(
			'forumulate_check' => __( 'Check connection', 'forumulate' ),
			'forumulate_flush' => __( 'Clear feed cache', 'forumulate' ),
		) as $action => $label ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:8px">';
			echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '" />';
			wp_nonce_field( $action );
			submit_button( $label, 'secondary', 'submit', false );
			echo '</form>';
		}

		echo '<h2>' . esc_html__( 'Shortcodes', 'forumulate' ) . '</h2><ul>';
		foreach ( array(
			'[forumulate_comments]',
			'[forumulate_widget mode="search"]',
			'[forumulate_changelog]',
			'[forumulate_feed type="blog" limit="5"]',
		) as $code ) {
			echo '<li><code>' . esc_html( $code ) . '</code></li>';
		}
		echo '</ul><p>' . esc_html__( 'Or add the Forumulate block in the editor.', 'forumulate' ) . '</p></div>';
	}

	/**
	 * Show the last connection check, once.
	 */
	private static function check_result() {
		$key    = 'forumulate_check_' . get_current_user_id();
		$result = get_transient( $key );
		if ( ! is_array( $result ) ) {
			return;
		}
		delete_transient( $key );
		foreach ( $result as $line ) {
			printf(
				'<div class="notice notice-%1$s"><p>%2$s</p></div>',
				esc_attr( $line['ok'] ? 'success' : 'error' ),
				esc_html( $line['text'] )
			);
		}
	}

	/**
	 * Verify a nonce'd admin-post request from someone allowed to make it.
	 *
	 * @param string $action Nonce action.
	 */
	private static function guard( $action ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'forumulate' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action );
	}

	/**
	 * Back to the settings page.
	 */
	private static function back() {
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE ) );
		exit;
	}

	/**
	 * "Check connection": the public embed check, then the token if there is one.
	 */
	public static function check() {
		self::guard( 'forumulate_check' );
		$lines = array();
		$org   = (string) Forumulate_Options::get( 'org' );
		$space = (string) Forumulate_Options::get( 'space' );

		$public = Forumulate_Api::get_json(
			'/embed/check',
			array(
				'org'   => $org,
				'space' => $space,
			)
		);
		if ( is_wp_error( $public ) ) {
			$lines[] = array(
				'ok'   => false,
				/* translators: %s: error message. */
				'text' => sprintf( __( 'Could not reach Forumulate: %s', 'forumulate' ), $public->get_error_message() ),
			);
		} elseif ( empty( $public['ok'] ) ) {
			$lines[] = array(
				'ok'   => false,
				'text' => __( 'Forumulate is reachable, but the organization or default space is missing.', 'forumulate' ),
			);
		} else {
			$lines[] = array(
				'ok'   => true,
				'text' => __( 'Forumulate is reachable and the organization and default space are set. Public embeds will work.', 'forumulate' ),
			);
		}

		if ( Forumulate_Token::has() ) {
			$me = Forumulate_Api::get_json( '/auth/me', array(), true );
			if ( is_wp_error( $me ) ) {
				$lines[] = array(
					'ok'   => false,
					/* translators: %s: error message. */
					'text' => sprintf( __( 'The token was refused: %s', 'forumulate' ), $me->get_error_message() ),
				);
			} else {
				$who     = isset( $me['user']['email'] ) ? (string) $me['user']['email'] : __( 'your account', 'forumulate' );
				$lines[] = array(
					'ok'   => true,
					/* translators: %s: account email. */
					'text' => sprintf( __( 'The token works and acts as %s.', 'forumulate' ), $who ),
				);
			}
		}

		set_transient( 'forumulate_check_' . get_current_user_id(), $lines, 5 * MINUTE_IN_SECONDS );
		self::back();
	}

	/**
	 * "Clear feed cache".
	 */
	public static function flush() {
		self::guard( 'forumulate_flush' );
		Forumulate_Feed::flush();
		set_transient(
			'forumulate_check_' . get_current_user_id(),
			array(
				array(
					'ok'   => true,
					'text' => __( 'Feed cache cleared.', 'forumulate' ),
				),
			),
			5 * MINUTE_IN_SECONDS
		);
		self::back();
	}
}
