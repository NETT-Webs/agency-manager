<?php
namespace AgencyManager\Rest;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin REST wrapper around Settings::all()/update() for the two fields
 * Admin\Settings_Page's form manages (agency type, notification email) —
 * same sanitization as Settings_Page::maybe_save(). Backup/Restore keeps
 * using the existing am_export/am_import admin-post actions directly (see
 * Backup_Page and the Import/Export screen), not a REST call.
 */
class Settings_Rest_Controller extends Rest_Controller {

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE_V1, '/settings', array(
			array( 'methods' => 'GET', 'callback' => array( $this, 'get_settings' ), 'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'POST', 'callback' => array( $this, 'save_settings' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );
	}

	public function get_settings(): \WP_REST_Response {
		$settings = Settings::all();

		return new \WP_REST_Response( array(
			'agency_type'        => $settings['agency_type'],
			'notification_email' => $settings['notification_email'],
		) );
	}

	public function save_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$settings = Settings::all();

		$type = sanitize_key( (string) $request->get_param( 'agency_type' ) );
		$settings['agency_type'] = in_array( $type, array( 'talent', 'location', 'casting', 'model', 'both' ), true ) ? $type : $settings['agency_type'];

		$email = sanitize_email( (string) $request->get_param( 'notification_email' ) );
		if ( $email ) {
			$settings['notification_email'] = $email;
		}

		Settings::update( $settings );

		return new \WP_REST_Response( array( 'saved' => true ) );
	}
}
