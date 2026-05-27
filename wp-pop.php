<?php

/**
 * The plugin bootstrap file.
 *
 * @link              https://example.com
 * @since             0.1.0
 * @package           Wp_Pop
 *
 * @wordpress-plugin
 * Plugin Name:       WP Pop
 * Plugin URI:        https://example.com
 * Description:       Create and manage popups for your WordPress site — sitewide or page-specific, with flexible frequency controls.
 * Version:           0.1.0
 * Author:            Codi Lechasseur
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-pop
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
die;
}

define( 'WP_POP_VERSION', '0.1.0' );
define( 'WP_POP_PLUGIN_FILE', __FILE__ );
define( 'WP_POP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_POP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

function activate_wp_pop() {
require_once WP_POP_PLUGIN_DIR . 'includes/class-wp-pop-activator.php';
Wp_Pop_Activator::activate();
}

function deactivate_wp_pop() {
require_once WP_POP_PLUGIN_DIR . 'includes/class-wp-pop-deactivator.php';
Wp_Pop_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_pop' );
register_deactivation_hook( __FILE__, 'deactivate_wp_pop' );

require WP_POP_PLUGIN_DIR . 'includes/class-wp-pop.php';

function run_wp_pop() {
$plugin = new Wp_Pop();
$plugin->run();
}

run_wp_pop();
