<?php
/**
 * Dependencies of the editor script. It is written against the wp.* globals
 * directly, so the plugin ships readable source with no build step.
 *
 * @package Forumulate
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'wp-server-side-render' ),
	'version'      => '1.0.0',
);
