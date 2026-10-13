<?php
/**
 * Plugin Name:       Forumulate
 * Plugin URI:        https://github.com/forumulate/wordpress
 * Description:       Embed Forumulate comments, the docs search / ask widget, the changelog bell and blog, changelog or docs feeds in WordPress. Optionally replaces WordPress comments on the post types you choose.
 * Version:           1.0.0
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            Forumulate
 * Author URI:        https://forumulate.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       forumulate
 * Domain Path:       /languages
 *
 * @package Forumulate
 */

defined( 'ABSPATH' ) || exit;

define( 'FORUMULATE_VERSION', '1.0.0' );
define( 'FORUMULATE_FILE', __FILE__ );
define( 'FORUMULATE_DIR', plugin_dir_path( __FILE__ ) );
define( 'FORUMULATE_URL', plugin_dir_url( __FILE__ ) );

require_once FORUMULATE_DIR . 'includes/class-forumulate-options.php';
require_once FORUMULATE_DIR . 'includes/class-forumulate-token.php';
require_once FORUMULATE_DIR . 'includes/class-forumulate-api.php';
require_once FORUMULATE_DIR . 'includes/class-forumulate-embeds.php';
require_once FORUMULATE_DIR . 'includes/class-forumulate-feed.php';
require_once FORUMULATE_DIR . 'includes/class-forumulate-comments.php';
require_once FORUMULATE_DIR . 'includes/class-forumulate-shortcodes.php';
require_once FORUMULATE_DIR . 'includes/class-forumulate-block.php';

if ( is_admin() ) {
	require_once FORUMULATE_DIR . 'includes/class-forumulate-admin.php';
	Forumulate_Admin::init();
}

/**
 * Loads translations for copies installed from outside WordPress.org.
 * (WordPress.org-hosted copies get their language packs automatically.)
 */
function forumulate_load_textdomain() {
	load_plugin_textdomain( 'forumulate', false, dirname( plugin_basename( FORUMULATE_FILE ) ) . '/languages' );
}
add_action( 'init', 'forumulate_load_textdomain', 0 );

Forumulate_Embeds::init();
Forumulate_Comments::init();
Forumulate_Shortcodes::init();
Forumulate_Block::init();
