<?php
/**
 * Plugin Name: Big SEO Sitemap
 * Plugin URI: https://github.com/iam-knr/big-seo-sitemap
 * Description: Advanced XML sitemap generator with auto-updates every 24 hours, category-based grouping, manual override, search engine pinging, and full in-admin view & edit capabilities.
 * Version: 1.0.0
 * Author: Kailas Nath R
 * Author URI: https://www.linkedin.com/in/iamknr/
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: big-seo-sitemap-main
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BIG_SITEMAP_VERSION', '1.0.0' );
define( 'BIG_SITEMAP_PATH', plugin_dir_path( __FILE__ ) );
define( 'BIG_SITEMAP_URL', plugin_dir_url( __FILE__ ) );
define( 'BIG_SITEMAP_FILE', __FILE__ );

require_once BIG_SITEMAP_PATH . 'includes/class-big-sitemap-generator.php';
require_once BIG_SITEMAP_PATH . 'includes/class-big-sitemap-settings.php';
require_once BIG_SITEMAP_PATH . 'includes/class-big-sitemap-admin.php';
require_once BIG_SITEMAP_PATH . 'includes/class-big-sitemap-scheduler.php';

function big_sitemap_init() {
    Big_Sitemap_Settings::init();
    Big_Sitemap_Admin::init();
    Big_Sitemap_Scheduler::init();
}
add_action( 'plugins_loaded', 'big_sitemap_init' );

register_activation_hook( __FILE__, 'big_sitemap_activate' );
function big_sitemap_activate() {
    Big_Sitemap_Scheduler::schedule();
    Big_Sitemap_Generator::generate();
}

register_deactivation_hook( __FILE__, 'big_sitemap_deactivate' );
function big_sitemap_deactivate() {
    Big_Sitemap_Scheduler::unschedule();
}
