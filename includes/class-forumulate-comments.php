<?php
/**
 * Optional: replace WordPress comments with Forumulate comments on the post
 * types chosen in settings.
 *
 * Classic themes load comments through comments_template(), so that template
 * is swapped. Block themes render the core/comments block instead, so that
 * block's output is swapped too. Each post's thread is resolved by its
 * permalink: the first visit creates one topic per article in the comments
 * space, and every later visit finds the same one.
 *
 * Existing WordPress comments are not deleted; turning the option off brings
 * them straight back.
 *
 * @package Forumulate
 */

defined( 'ABSPATH' ) || exit;

/**
 * WordPress comments replacement.
 */
class Forumulate_Comments {

	/**
	 * Hook up.
	 */
	public static function init() {
		add_filter( 'comments_template', array( __CLASS__, 'template' ), 20 );
		add_filter( 'render_block_core/comments', array( __CLASS__, 'block' ), 20 );
	}

	/**
	 * Does the current post use Forumulate comments?
	 *
	 * @param WP_Post|null $post Post, defaults to the global one.
	 * @return bool
	 */
	public static function applies( $post = null ) {
		$post = get_post( $post );
		if ( ! $post || ! Forumulate_Options::get( 'replace_comments' ) ) {
			return false;
		}
		$types = (array) Forumulate_Options::get( 'comment_post_types' );
		if ( ! in_array( $post->post_type, $types, true ) ) {
			return false;
		}
		/**
		 * Filters whether a single post shows Forumulate comments.
		 *
		 * @param bool    $applies Whether Forumulate comments replace WordPress comments.
		 * @param WP_Post $post    The post.
		 */
		return (bool) apply_filters( 'forumulate_replace_comments', true, $post );
	}

	/**
	 * The thread markup for the current post.
	 *
	 * @return string
	 */
	public static function markup() {
		return '<section class="forumulate-comments" id="comments">' . Forumulate_Embeds::render(
			'comments',
			array( 'article_url' => get_permalink() )
		) . '</section>';
	}

	/**
	 * `comments_template` filter.
	 *
	 * @param string $template Theme template path.
	 * @return string
	 */
	public static function template( $template ) {
		return self::applies() ? FORUMULATE_DIR . 'templates/comments.php' : $template;
	}

	/**
	 * `render_block_core/comments` filter.
	 *
	 * @param string $content Block output.
	 * @return string
	 */
	public static function block( $content ) {
		return self::applies() ? self::markup() : $content;
	}
}
