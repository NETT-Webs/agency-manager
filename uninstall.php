<?php
/**
 * Fires only on "Delete" from the Plugins screen (never on deactivate).
 * Removes only the plugin's own settings option — Talent, Location, Form,
 * and Submission content are left untouched, since deleting the plugin is
 * not the same as asking to delete the agency's data.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'am_settings' );
