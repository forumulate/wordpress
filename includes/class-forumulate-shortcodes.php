<?php
/**
 * Shortcodes:
 *
 *   [forumulate_comments space="" topic="" theme="light|dark"]
 *   [forumulate_widget space="" mode="search|ask" placeholder=""]
 *   [forumulate_changelog space=""]
 *   [forumulate_feed type="blog|changelog|docs" space="" limit="5" excerpt="yes" title=""]
 *
 * Every attribute is optional; the space falls back to the one in settings.
 *
 * @package Forumulate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode handlers.
 */
class Forumulate_Shortcodes {

	/**
	 * Register the shortcodes.
	 */
	public static function init() {
		add_shortcode( 'forumulate_comments', array( __CLASS__, 'comments' ) );
		add_shortcode( 'forumulate_widget', array( __CLASS__, 'widget' ) );
		add_shortcode( 'forumulate_changelog', array( __CLASS__, 'changelog' ) );
		add_shortcode( 'forumulate_feed', array( __CLASS__, 'feed' ) );
	}

	/**
	 * [forumulate_comments].
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function comments( $atts ) {
		$atts = shortcode_atts(
			array(
				'space' => '',
				'topic' => '',
				'theme' => '',
			),
			$atts,
			'forumulate_comments'
		);
		$args = array(
			'space' => $atts['space'],
			'topic' => $atts['topic'],
			'theme' => $atts['theme'],
		);
		if ( '' === $atts['topic'] && is_singular() ) {
			$args['article_url'] = get_permalink();
		}
		return Forumulate_Embeds::render( 'comments', $args );
	}

	/**
	 * [forumulate_widget].
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function widget( $atts ) {
		$atts = shortcode_atts(
			array(
				'space'       => '',
				'mode'        => 'search',
				'placeholder' => '',
			),
			$atts,
			'forumulate_widget'
		);
		return Forumulate_Embeds::render( 'widget', $atts );
	}

	/**
	 * [forumulate_changelog].
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function changelog( $atts ) {
		$atts = shortcode_atts( array( 'space' => '' ), $atts, 'forumulate_changelog' );
		return Forumulate_Embeds::render( 'changelog', $atts );
	}

	/**
	 * [forumulate_feed].
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function feed( $atts ) {
		$atts = shortcode_atts(
			array(
				'type'    => 'blog',
				'space'   => '',
				'limit'   => 5,
				'excerpt' => 'yes',
				'title'   => '',
			),
			$atts,
			'forumulate_feed'
		);
		return Forumulate_Feed::render( $atts );
	}
}
