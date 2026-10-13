<?php
/**
 * The forumulate/embed block: one block, four kinds (comments, widget,
 * changelog, feed), registered from blocks/embed/block.json and rendered on
 * the server by the same code as the shortcodes.
 *
 * @package Forumulate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block registration and render.
 */
class Forumulate_Block {

	/**
	 * Hook up.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Register the block type from its block.json.
	 */
	public static function register() {
		register_block_type( FORUMULATE_DIR . 'blocks/embed' );
	}

	/**
	 * Render a block's attributes to HTML.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes ) {
		$a    = is_array( $attributes ) ? $attributes : array();
		$kind = isset( $a['kind'] ) ? (string) $a['kind'] : 'comments';

		if ( 'feed' === $kind ) {
			return Forumulate_Feed::render(
				array(
					'type'    => isset( $a['feedType'] ) ? $a['feedType'] : 'blog',
					'space'   => isset( $a['space'] ) ? $a['space'] : '',
					'limit'   => isset( $a['limit'] ) ? $a['limit'] : 5,
					'excerpt' => isset( $a['showExcerpt'] ) ? (bool) $a['showExcerpt'] : true,
					'title'   => isset( $a['title'] ) ? $a['title'] : '',
				)
			);
		}

		$args = array(
			'space'       => isset( $a['space'] ) ? $a['space'] : '',
			'topic'       => isset( $a['topic'] ) ? $a['topic'] : '',
			'theme'       => isset( $a['theme'] ) ? $a['theme'] : '',
			'mode'        => isset( $a['mode'] ) ? $a['mode'] : 'search',
			'placeholder' => isset( $a['placeholder'] ) ? $a['placeholder'] : '',
		);
		if ( 'comments' === $kind && '' === $args['topic'] && is_singular() ) {
			$args['article_url'] = get_permalink();
		}
		return Forumulate_Embeds::render( $kind, $args );
	}

	/**
	 * Tags and attributes the block's markup may contain, for wp_kses().
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowed_html() {
		$common = array(
			'class'      => true,
			'id'         => true,
			'aria-label' => true,
		);
		return array(
			'div'  => $common,
			'p'    => $common,
			'h3'   => $common,
			'ul'   => $common,
			'li'   => $common,
			'span' => $common,
			'a'    => array_merge( $common, array( 'href' => true ) ),
			'time' => array_merge( $common, array( 'datetime' => true ) ),
		);
	}
}
