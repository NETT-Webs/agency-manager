<?php
/**
 * Plugin Name:       NettWebs Talent & Location Management
 * Plugin URI:        https://nettwebshosting.co.za/plugins/agency-manager
 * Description:       Talent, casting, and location management for agency websites — profiles, applications and Elementor widgets, all from one place. Works standalone on any WordPress site.
 * Version:           1.7.0
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

define( 'NETTALO_VERSION', '1.7.0' );
define( 'NETTALO_PLUGIN_FILE', __FILE__ );
define( 'NETTALO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NETTALO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NETTALO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Deprecated compatibility aliases — the plugin's own code uses the
// NETTALO_* constants above exclusively; these AM_* constants exist only
// so any external code (a theme, a site-specific mu-plugin) that referenced
// them before the 1.7.0 rename keeps working. See docs/REBRAND.md.
define( 'AM_VERSION', NETTALO_VERSION );
define( 'AM_PLUGIN_FILE', NETTALO_PLUGIN_FILE );
define( 'AM_PLUGIN_DIR', NETTALO_PLUGIN_DIR );
define( 'AM_PLUGIN_URL', NETTALO_PLUGIN_URL );
define( 'AM_PLUGIN_BASENAME', NETTALO_PLUGIN_BASENAME );

require_once NETTALO_PLUGIN_DIR . 'includes/autoload.php';

register_activation_hook( NETTALO_PLUGIN_FILE, array( \Nettalo\TalentLocationManagement\Activator::class, 'activate' ) );
register_deactivation_hook( NETTALO_PLUGIN_FILE, array( \Nettalo\TalentLocationManagement\Deactivator::class, 'deactivate' ) );

\Nettalo\TalentLocationManagement\Plugin::instance();
