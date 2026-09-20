<?php
/**
 * Plugin Name:       NettWebs Talent & Location Management
 * Plugin URI:        https://nettwebshosting.co.za/plugins/agency-manager
 * Description:       Talent, casting, and location management for agency websites — profiles, applications and Elementor widgets, all from one place. Works standalone on any WordPress site.
 * Version:           1.6.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            NettWebs
 * Author URI:        https://nettwebshosting.co.za/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nettwebs-talent-location-management
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// These constants keep their original AM_ prefix intentionally — they are
// internal-only (never part of any public URL, shortcode, hook, or stored
// data), so renaming them carries real risk (every file that references
// them) for zero WordPress.org compliance benefit. See docs/REBRAND.md.
define( 'AM_VERSION', '1.6.5' );
define( 'AM_PLUGIN_FILE', __FILE__ );
define( 'AM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once AM_PLUGIN_DIR . 'includes/autoload.php';

register_activation_hook( AM_PLUGIN_FILE, array( \AgencyManager\Activator::class, 'activate' ) );
register_deactivation_hook( AM_PLUGIN_FILE, array( \AgencyManager\Deactivator::class, 'deactivate' ) );

\AgencyManager\Plugin::instance();
