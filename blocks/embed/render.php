<?php
/**
 * Front-end render of the forumulate/embed block.
 *
 * @package Forumulate
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$forumulate_html = Forumulate_Block::render( $attributes );
if ( '' === $forumulate_html ) {
	return;
}
printf(
	'<div %1$s>%2$s</div>',
	get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core escapes these attributes.
	wp_kses( $forumulate_html, Forumulate_Block::allowed_html() )
);
