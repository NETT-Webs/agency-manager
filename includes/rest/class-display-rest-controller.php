<?php
namespace AgencyManager\Rest;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin REST wrapper around Settings::all()/update() for exactly the slices
 * Admin\Website_Display_Page's form already manages (display mode,
 * placeholder manager, homepage section) — same sanitization rules as
 * Website_Display_Page::maybe_save(), just reading from a JSON body instead
 * of $_POST.
 */
class Display_Rest_Controller extends Rest_Controller {

	private const TYPES = array( 'talent', 'location' );

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE_V1, '/display-settings', array(
			array( 'methods' => 'GET', 'callback' => array( $this, 'get_settings' ), 'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'POST', 'callback' => array( $this, 'save_settings' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );
	}

	public function get_settings(): \WP_REST_Response {
		$settings = Settings::all();

		return new \WP_REST_Response( array(
			'display'     => $settings['display'],
			'placeholder' => $settings['placeholder'],
			'homepage'    => $settings['homepage'],
		) );
	}

	public function save_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$body     = $request->get_json_params();
		$settings = Settings::all();

		foreach ( self::TYPES as $type ) {
			$mode = sanitize_key( (string) ( $body['display'][ $type ] ?? 'scouting' ) );
			$settings['display'][ $type ] = in_array( $mode, array( 'hidden', 'scouting', 'live' ), true ) ? $mode : 'scouting';

			$ph = $body['placeholder'][ $type ] ?? array();
			$settings['placeholder'][ $type ]['badge']       = sanitize_text_field( (string) ( $ph['badge'] ?? '' ) );
			$settings['placeholder'][ $type ]['heading']     = sanitize_text_field( (string) ( $ph['heading'] ?? '' ) );
			$settings['placeholder'][ $type ]['description'] = sanitize_textarea_field( (string) ( $ph['description'] ?? '' ) );
			$settings['placeholder'][ $type ]['button_text'] = sanitize_text_field( (string) ( $ph['button_text'] ?? '' ) );
			$settings['placeholder'][ $type ]['button_link'] = esc_url_raw( (string) ( $ph['button_link'] ?? '' ) );
			$settings['placeholder'][ $type ]['image_ids']   = array_values( array_filter( array_map( 'absint', (array) ( $ph['image_ids'] ?? array() ) ) ) );
			$settings['placeholder'][ $type ]['count']       = max( 1, absint( $ph['count'] ?? 8 ) );

			$hp = $body['homepage'][ $type ] ?? array();
			$settings['homepage'][ $type ]['heading']      = sanitize_text_field( (string) ( $hp['heading'] ?? '' ) );
			$settings['homepage'][ $type ]['subheading']   = sanitize_text_field( (string) ( $hp['subheading'] ?? '' ) );
			$settings['homepage'][ $type ]['button_text']  = sanitize_text_field( (string) ( $hp['button_text'] ?? '' ) );
			$settings['homepage'][ $type ]['button_link']  = esc_url_raw( (string) ( $hp['button_link'] ?? '' ) );
			$settings['homepage'][ $type ]['count']        = max( 1, absint( $hp['count'] ?? 4 ) );
			$mode2 = sanitize_key( (string) ( $hp['display_mode'] ?? 'inherit' ) );
			$settings['homepage'][ $type ]['display_mode'] = in_array( $mode2, array( 'inherit', 'hidden', 'scouting', 'live' ), true ) ? $mode2 : 'inherit';
			$anim = sanitize_key( (string) ( $hp['animation'] ?? 'none' ) );
			$settings['homepage'][ $type ]['animation']    = in_array( $anim, array( 'none', 'lift', 'zoom', 'fade' ), true ) ? $anim : 'none';
		}

		Settings::update( $settings );

		return new \WP_REST_Response( array( 'saved' => true ) );
	}
}
