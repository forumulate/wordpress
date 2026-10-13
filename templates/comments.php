<?php
/**
 * Comments template used in place of the theme's when Forumulate comments are
 * on for this post type. Forumulate_Comments::markup() escapes everything it
 * prints, so it is output as-is.
 *
 * @package Forumulate
 */

defined( 'ABSPATH' ) || exit;

echo Forumulate_Comments::markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
