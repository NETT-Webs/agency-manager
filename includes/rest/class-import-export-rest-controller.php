<?php
namespace AgencyManager\Rest;

use AgencyManager\Export_Import\Importer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The actual export (file download) and import (multipart upload) requests
 * still go straight to their existing `admin-post.php?action=am_export` /
 * `am_import` handlers (Export_Import\Exporter/Importer) exactly as before
 * — a file download and a redirect-with-notice aren't a good fit for a JSON
 * REST call, and reimplementing them would duplicate real logic for no
 * benefit. This controller only exposes the one thing the React page can't
 * get any other way: the last import report, so it can show the result
 * after the browser's normal form-post-and-redirect completes.
 */
class Import_Export_Rest_Controller extends Rest_Controller {

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE_V1, '/import-export/report', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_report' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
	}

	public function get_report(): \WP_REST_Response {
		return new \WP_REST_Response( Importer::get_last_report() );
	}
}
