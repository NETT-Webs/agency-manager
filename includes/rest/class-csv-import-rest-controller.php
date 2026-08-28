<?php
namespace AgencyManager\Rest;

use AgencyManager\Csv_Import\Column_Mapper;
use AgencyManager\Csv_Import\Importer;
use AgencyManager\Csv_Import\Import_Session;
use AgencyManager\Csv_Import\Row_Resolver;
use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST surface for the CSV Import wizard (Upload -> Map -> Review -> Import
 * -> Results). Every route here is orchestration only — the actual CSV
 * reading, mapping, validation, and record-writing live in
 * Csv_Import\Csv_Parser / Column_Mapper / Row_Resolver / Importer /
 * Import_Session, and Importer::run() writes through the same
 * Talent_Rest_Controller / Location_Rest_Controller the manual Talent/
 * Location editors use, not a separate importer-specific writer.
 */
class Csv_Import_Rest_Controller extends Rest_Controller {

	private const MAX_FILE_SIZE = 20 * MB_IN_BYTES;

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE_V1, '/csv-import/fields', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_fields' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( self::NAMESPACE_V1, '/csv-import/upload', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'upload' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( self::NAMESPACE_V1, '/csv-import/session/(?P<id>[a-zA-Z0-9]+)', array(
			array( 'methods' => 'GET', 'callback' => array( $this, 'get_session' ), 'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'DELETE', 'callback' => array( $this, 'cancel_session' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );

		register_rest_route( self::NAMESPACE_V1, '/csv-import/session/(?P<id>[a-zA-Z0-9]+)/mapping', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'save_mapping' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( self::NAMESPACE_V1, '/csv-import/session/(?P<id>[a-zA-Z0-9]+)/preview', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'preview' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( self::NAMESPACE_V1, '/csv-import/session/(?P<id>[a-zA-Z0-9]+)/run', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'run' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( self::NAMESPACE_V1, '/csv-import/mapping-templates', array(
			array( 'methods' => 'GET', 'callback' => array( $this, 'list_templates' ), 'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'POST', 'callback' => array( $this, 'save_template' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );

		register_rest_route( self::NAMESPACE_V1, '/csv-import/mapping-templates/(?P<id>[a-zA-Z0-9_-]+)', array(
			array( 'methods' => 'PUT', 'callback' => array( $this, 'rename_template' ), 'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'DELETE', 'callback' => array( $this, 'delete_template' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );
	}

	// ---- Fields ----

	public function get_fields( \WP_REST_Request $request ): \WP_REST_Response {
		$type = 'location' === $request->get_param( 'type' ) ? 'location' : 'talent';
		return new \WP_REST_Response( Row_Resolver::available_targets( $type ) );
	}

	// ---- Upload ----

	public function upload( \WP_REST_Request $request ): \WP_REST_Response {
		$type = 'location' === $request->get_param( 'type' ) ? 'location' : 'talent';

		$files = $request->get_file_params();
		if ( empty( $files['file']['tmp_name'] ) || ! is_uploaded_file( $files['file']['tmp_name'] ) ) {
			return new \WP_REST_Response( array( 'message' => __( 'No file uploaded.', 'agency-manager' ) ), 400 );
		}

		$file = $files['file'];

		if ( $file['size'] > self::MAX_FILE_SIZE ) {
			return new \WP_REST_Response( array( 'message' => __( 'File is too large (20MB limit).', 'agency-manager' ) ), 400 );
		}

		$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $ext ) {
			return new \WP_REST_Response( array( 'message' => __( 'Please upload a .csv file.', 'agency-manager' ) ), 400 );
		}

		$filetype = wp_check_filetype( $file['name'], array( 'csv' => 'text/csv' ) );
		$allowed_mimes = array( 'text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel', 'text/comma-separated-values' );
		if ( empty( $filetype['ext'] ) || ! in_array( (string) $file['type'], $allowed_mimes, true ) ) {
			// Some browsers send a generic type for CSV; fall back to sniffing the first bytes for anything obviously non-text/binary rather than rejecting outright.
			$handle = fopen( $file['tmp_name'], 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
			$sample = $handle ? fread( $handle, 512 ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
			if ( $handle ) {
				fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			}
			if ( false !== strpos( (string) $sample, "\0" ) ) {
				return new \WP_REST_Response( array( 'message' => __( 'This does not look like a valid CSV file.', 'agency-manager' ) ), 400 );
			}
		}

		$session = Import_Session::create( $file['tmp_name'], $file['name'], $type );
		if ( empty( $session ) ) {
			return new \WP_REST_Response( array( 'message' => __( 'Could not process the uploaded file.', 'agency-manager' ) ), 500 );
		}

		return new \WP_REST_Response( $this->public_session( $session ) );
	}

	// ---- Session ----

	public function get_session( \WP_REST_Request $request ): \WP_REST_Response {
		$session = Import_Session::get( (string) $request->get_param( 'id' ) );
		if ( ! $session ) {
			return new \WP_REST_Response( array( 'message' => __( 'Import session not found or expired.', 'agency-manager' ) ), 404 );
		}
		return new \WP_REST_Response( $this->public_session( $session ) );
	}

	public function cancel_session( \WP_REST_Request $request ): \WP_REST_Response {
		Import_Session::delete( (string) $request->get_param( 'id' ) );
		return new \WP_REST_Response( array( 'deleted' => true ) );
	}

	public function save_mapping( \WP_REST_Request $request ): \WP_REST_Response {
		$id      = (string) $request->get_param( 'id' );
		$body    = (array) $request->get_json_params();
		$session = Import_Session::get( $id );
		if ( ! $session ) {
			return new \WP_REST_Response( array( 'message' => __( 'Import session not found or expired.', 'agency-manager' ) ), 404 );
		}

		$column_map = array();
		foreach ( (array) ( $body['columnMap'] ?? array() ) as $csv_column => $target_key ) {
			$column_map[ sanitize_text_field( (string) $csv_column ) ] = $target_key ? sanitize_key( (string) $target_key ) : '';
		}

		$options_in = (array) ( $body['options'] ?? array() );
		$options    = array(
			'createTerms'   => ! empty( $options_in['createTerms'] ),
			'importImages'  => ! empty( $options_in['importImages'] ),
			'duplicateMode' => in_array( $options_in['duplicateMode'] ?? '', array( 'create', 'update', 'skip' ), true ) ? $options_in['duplicateMode'] : 'create',
			'matchField'    => in_array( $options_in['matchField'] ?? '', array( 'id', 'email', 'title' ), true ) ? $options_in['matchField'] : 'email',
			'clearBlanks'   => ! empty( $options_in['clearBlanks'] ),
		);

		$session = Import_Session::update( $id, array( 'columnMap' => $column_map, 'options' => $options ) );

		return new \WP_REST_Response( $this->public_session( $session ) );
	}

	public function preview( \WP_REST_Request $request ): \WP_REST_Response {
		$session = Import_Session::get( (string) $request->get_param( 'id' ) );
		if ( ! $session ) {
			return new \WP_REST_Response( array( 'message' => __( 'Import session not found or expired.', 'agency-manager' ) ), 404 );
		}

		$offset = absint( $request->get_param( 'offset' ) ?? 0 );
		$limit  = min( Importer::batch_size(), max( 1, absint( $request->get_param( 'limit' ) ?? Importer::batch_size() ) ) );

		return new \WP_REST_Response( Importer::preview( $session, $offset, $limit ) );
	}

	public function run( \WP_REST_Request $request ): \WP_REST_Response {
		$session = Import_Session::get( (string) $request->get_param( 'id' ) );
		if ( ! $session ) {
			return new \WP_REST_Response( array( 'message' => __( 'Import session not found or expired.', 'agency-manager' ) ), 404 );
		}
		if ( empty( $session['columnMap'] ) ) {
			return new \WP_REST_Response( array( 'message' => __( 'No column mapping has been saved for this import yet.', 'agency-manager' ) ), 400 );
		}

		$offset = absint( $request->get_param( 'offset' ) ?? 0 );
		$limit  = min( Importer::batch_size(), max( 1, absint( $request->get_param( 'limit' ) ?? Importer::batch_size() ) ) );

		$result = Importer::run( $session, $offset, $limit );

		if ( $offset + $limit >= $session['rowCount'] ) {
			Import_Session::delete( $session['id'] );
		}

		$result['done'] = $offset + $limit >= $session['rowCount'];

		return new \WP_REST_Response( $result );
	}

	private function public_session( array $session ): array {
		return array(
			'id'         => $session['id'],
			'type'       => $session['type'],
			'fileName'   => $session['fileName'],
			'columns'    => $session['header'],
			'rowCount'   => $session['rowCount'],
			'columnMap'  => $session['columnMap'],
			'options'    => $session['options'],
			'suggestedMap' => Column_Mapper::suggest( $session['header'], $session['type'] ),
		);
	}

	// ---- Saved mapping templates ----

	public function list_templates( \WP_REST_Request $request ): \WP_REST_Response {
		$type = $request->get_param( 'type' );
		$all  = Settings::all()['csv_mapping_templates'];
		if ( $type ) {
			$all = array_filter( $all, fn( $t ) => ( $t['type'] ?? '' ) === $type );
		}
		return new \WP_REST_Response( array_values( $all ) );
	}

	public function save_template( \WP_REST_Request $request ): \WP_REST_Response {
		$body = (array) $request->get_json_params();
		$name = sanitize_text_field( (string) ( $body['name'] ?? '' ) );
		$type = 'location' === ( $body['type'] ?? '' ) ? 'location' : 'talent';

		if ( '' === $name ) {
			return new \WP_REST_Response( array( 'message' => __( 'Please name this mapping.', 'agency-manager' ) ), 400 );
		}

		$column_map = array();
		foreach ( (array) ( $body['columnMap'] ?? array() ) as $csv_column => $target_key ) {
			$column_map[ sanitize_text_field( (string) $csv_column ) ] = $target_key ? sanitize_key( (string) $target_key ) : '';
		}

		$settings = Settings::all();
		$id       = 'm_' . wp_generate_password( 12, false, false );

		$settings['csv_mapping_templates'][ $id ] = array(
			'id'        => $id,
			'name'      => $name,
			'type'      => $type,
			'columnMap' => $column_map,
			'createdAt' => current_time( 'mysql' ),
		);

		Settings::update( $settings );

		return new \WP_REST_Response( $settings['csv_mapping_templates'][ $id ], 201 );
	}

	public function rename_template( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = (string) $request->get_param( 'id' );
		$body = (array) $request->get_json_params();
		$name = sanitize_text_field( (string) ( $body['name'] ?? '' ) );

		$settings = Settings::all();
		if ( ! isset( $settings['csv_mapping_templates'][ $id ] ) ) {
			return new \WP_REST_Response( array( 'message' => __( 'Mapping template not found.', 'agency-manager' ) ), 404 );
		}
		if ( '' === $name ) {
			return new \WP_REST_Response( array( 'message' => __( 'Please provide a name.', 'agency-manager' ) ), 400 );
		}

		$settings['csv_mapping_templates'][ $id ]['name'] = $name;
		Settings::update( $settings );

		return new \WP_REST_Response( $settings['csv_mapping_templates'][ $id ] );
	}

	public function delete_template( \WP_REST_Request $request ): \WP_REST_Response {
		$id       = (string) $request->get_param( 'id' );
		$settings = Settings::all();

		if ( isset( $settings['csv_mapping_templates'][ $id ] ) ) {
			unset( $settings['csv_mapping_templates'][ $id ] );
			Settings::update( $settings );
		}

		return new \WP_REST_Response( array( 'deleted' => true ) );
	}
}
