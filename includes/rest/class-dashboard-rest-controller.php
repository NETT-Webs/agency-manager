<?php
namespace AgencyManager\Rest;

use AgencyManager\Admin\Dashboard_Data;
use AgencyManager\Admin\Shortcode_Reference;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read-only endpoints for the new React Dashboard. Every route just calls
 * an existing, already-correct PHP data source — Dashboard_Data (itself
 * extracted verbatim from the original Dashboard_Page) and
 * Shortcode_Reference::groups() (already a clean static data method, no
 * extraction needed).
 */
class Dashboard_Rest_Controller extends Rest_Controller {

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE_V1,
			'/dashboard/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_stats' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/dashboard/recent-activity',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_recent_activity' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/shortcodes',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_shortcodes' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	public function get_stats(): \WP_REST_Response {
		return new \WP_REST_Response( Dashboard_Data::get_stats() );
	}

	public function get_recent_activity(): \WP_REST_Response {
		return new \WP_REST_Response( Dashboard_Data::get_recent_activity() );
	}

	public function get_shortcodes(): \WP_REST_Response {
		return new \WP_REST_Response( Shortcode_Reference::groups() );
	}
}
