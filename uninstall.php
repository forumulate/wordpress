<?php
/**
 * Removes the plugin's options when it is deleted from the Plugins screen.
 * Cached feeds are transients and expire on their own.
 *
 * @package Forumulate
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'forumulate_settings' );
delete_option( 'forumulate_token' );
delete_option( 'forumulate_feed_generation' );
