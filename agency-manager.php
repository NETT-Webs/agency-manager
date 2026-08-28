<?php
/**
 * Plugin Name:       Agency Manager
 * Plugin URI:        https://edencast.co.za/
 * Description:       Talent, casting, model and location management for agency websites — profiles, applications and Elementor widgets, all from one place. Works standalone on any WordPress site.
 * Version:           1.6.4
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Eden Cast
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       agency-manager
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AM_VERSION', '1.6.4' );
define( 'AM_PLUGIN_FILE', __FILE__ );
define( 'AM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once AM_PLUGIN_DIR . 'includes/autoload.php';

register_activation_hook( AM_PLUGIN_FILE, array( \AgencyManager\Activator::class, 'activate' ) );
register_deactivation_hook( AM_PLUGIN_FILE, array( \AgencyManager\Deactivator::class, 'deactivate' ) );

\AgencyManager\Plugin::instance();
