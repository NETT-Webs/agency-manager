<?php
namespace AgencyManager\Rest;

use AgencyManager\Forms\Field_Types;
use AgencyManager\Forms\Form_Renderer;
use AgencyManager\Forms\Form_Schema;
use AgencyManager\Forms\Mapping_Targets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin REST wrapper around the form *definition* management Admin\Forms_Page
 * already implements (list/create/duplicate/trash) — same wp_insert_post()/
 * get_posts() calls, same meta keys, same "trash not force-delete" caution.
 * Submission review lives on Applications_Rest_Controller; this controller
 * only manages forms themselves.
 */
class Forms_Rest_Controller extends Rest_Controller {

	private const TYPE_LABELS = array(
		'talent'   => 'Talent Application',
		'location' => 'Location Application',
		'general'  => 'General / Contact',
	);

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE_V1, '/forms', array(
			array( 'methods' => 'GET', 'callback' => array( $this, 'list_forms' ), 'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'POST', 'callback' => array( $this, 'create_form' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );

		register_rest_route( self::NAMESPACE_V1, '/forms/templates', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_templates' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( self::NAMESPACE_V1, '/forms/(?P<id>\d+)/builder', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_builder_data' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( self::NAMESPACE_V1, '/forms/(?P<id>\d+)/duplicate', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'duplicate_form' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( self::NAMESPACE_V1, '/forms/(?P<id>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( $this, 'delete_form' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
	}

	public function list_forms(): \WP_REST_Response {
		$forms = get_posts( array( 'post_type' => 'am_form', 'post_status' => 'any', 'numberposts' => -1 ) );
		$renderer = new Form_Renderer();
		$rows = array();

		foreach ( $forms as $form ) {
			$field_count = count( $renderer->get_fields( $form->ID ) );
			$form_type   = get_post_meta( $form->ID, '_am_form_type', true );
			$form_type   = isset( self::TYPE_LABELS[ $form_type ] ) ? $form_type : 'talent';

			$sub_count = ( new \WP_Query( array(
				'post_type'      => 'am_submission',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_am_form_id',
				'meta_value'     => $form->ID,
			) ) )->found_posts;

			$rows[] = array(
				'id'              => $form->ID,
				'title'           => $form->post_title,
				'type'            => $form_type,
				'typeLabel'       => self::TYPE_LABELS[ $form_type ],
				'status'          => $form->post_status,
				'fieldCount'      => $field_count,
				'submissionCount' => $sub_count,
				'created'         => get_the_date( '', $form ),
				'updated'         => get_the_modified_date( '', $form ),
				'shortcode'       => '[agency_form id="' . $form->ID . '"]',
				'editUrl'         => admin_url( 'admin.php?page=agency-manager-forms&view=builder&form_id=' . $form->ID ),
				'previewUrl'      => admin_url( 'admin.php?page=agency-manager-forms&view=preview&form_id=' . $form->ID ),
				'applicationsUrl' => admin_url( 'admin.php?page=agency-manager-applications&type=' . ( 'location' === $form_type ? 'location' : 'talent' ) ),
			);
		}

		return new \WP_REST_Response( $rows );
	}

	public function get_templates(): \WP_REST_Response {
		$out = array();
		foreach ( Form_Schema::templates() as $key => $template ) {
			$out[] = array( 'key' => $key, 'label' => $template['label'], 'description' => $template['description'] ?? '', 'type' => $template['type'] ?? 'talent' );
		}
		return new \WP_REST_Response( $out );
	}

	/**
	 * Read-only initial-load payload for the React Form Builder screen —
	 * exactly the data Form_Builder_Page used to `wp_localize_script`, now
	 * served as JSON so the builder can be a normal React screen fetching
	 * from the REST API like every other migrated screen. Saving still goes
	 * through the existing Form_Builder_Ajax::ajax_save() admin-ajax
	 * endpoint unchanged — this route never writes anything.
	 */
	public function get_builder_data( \WP_REST_Request $request ): \WP_REST_Response {
		$form_id = (int) $request->get_param( 'id' );
		$form    = get_post( $form_id );

		if ( ! $form || 'am_form' !== $form->post_type ) {
			return new \WP_REST_Response( array( 'message' => __( 'Form not found.', 'agency-manager' ) ), 404 );
		}

		$type = get_post_meta( $form_id, '_am_form_type', true );
		$type = in_array( $type, array( 'talent', 'location', 'general' ), true ) ? $type : 'talent';

		return new \WP_REST_Response( array(
			'id'             => $form_id,
			'title'          => $form->post_title,
			'status'         => $form->post_status,
			'type'           => $type,
			'confirmation'   => get_post_meta( $form_id, '_am_form_confirmation_message', true ),
			'fields'         => ( new Form_Renderer() )->get_fields( $form_id ),
			'library'        => Field_Types::library(),
			'types'          => Field_Types::types(),
			'mappingTargets' => array(
				'talent'   => Mapping_Targets::get( 'talent' ),
				'location' => Mapping_Targets::get( 'location' ),
			),
			'shortcode'       => '[agency_form id="' . $form_id . '"]',
			'previewUrl'      => admin_url( 'admin.php?page=agency-manager-forms&view=preview&form_id=' . $form_id ),
			'applicationsUrl' => admin_url( 'admin.php?page=agency-manager-applications&type=' . ( 'location' === $type ? 'location' : 'talent' ) ),
		) );
	}

	public function create_form( \WP_REST_Request $request ): \WP_REST_Response {
		$title    = sanitize_text_field( (string) $request->get_param( 'title' ) );
		$title    = $title ? $title : __( 'Untitled Form', 'agency-manager' );
		$type     = sanitize_key( (string) $request->get_param( 'type' ) );
		$type     = in_array( $type, array( 'talent', 'location', 'general' ), true ) ? $type : 'talent';
		$template = sanitize_key( (string) $request->get_param( 'template' ) );

		$form_id = wp_insert_post( array( 'post_type' => 'am_form', 'post_title' => $title, 'post_status' => 'publish' ) );

		if ( ! $form_id || is_wp_error( $form_id ) ) {
			return new \WP_REST_Response( array( 'message' => __( 'Could not create form.', 'agency-manager' ) ), 500 );
		}

		$templates = Form_Schema::templates();
		$fields    = ( 'blank' !== $template && isset( $templates[ $template ] ) ) ? $templates[ $template ]['fields'] : array();

		update_post_meta( $form_id, '_am_form_type', $type );
		update_post_meta( $form_id, '_am_form_fields', wp_json_encode( Form_Schema::normalize_fields( $fields ) ) );

		return new \WP_REST_Response( array(
			'id'      => $form_id,
			'editUrl' => admin_url( 'admin.php?page=agency-manager-forms&view=builder&form_id=' . $form_id ),
		), 201 );
	}

	public function duplicate_form( \WP_REST_Request $request ): \WP_REST_Response {
		$source_id = (int) $request->get_param( 'id' );
		$source    = get_post( $source_id );

		if ( ! $source || 'am_form' !== $source->post_type ) {
			return new \WP_REST_Response( array( 'message' => __( 'Form not found.', 'agency-manager' ) ), 404 );
		}

		$new_id = wp_insert_post( array(
			'post_type'   => 'am_form',
			/* translators: %s: original form title */
			'post_title'  => sprintf( __( '%s (Copy)', 'agency-manager' ), $source->post_title ),
			'post_status' => 'publish',
		) );

		if ( ! $new_id || is_wp_error( $new_id ) ) {
			return new \WP_REST_Response( array( 'message' => __( 'Could not duplicate form.', 'agency-manager' ) ), 500 );
		}

		update_post_meta( $new_id, '_am_form_type', get_post_meta( $source_id, '_am_form_type', true ) );
		update_post_meta( $new_id, '_am_form_fields', get_post_meta( $source_id, '_am_form_fields', true ) );
		$confirmation = get_post_meta( $source_id, '_am_form_confirmation_message', true );
		if ( $confirmation ) {
			update_post_meta( $new_id, '_am_form_confirmation_message', $confirmation );
		}

		return new \WP_REST_Response( array( 'id' => $new_id ) );
	}

	public function delete_form( \WP_REST_Request $request ): \WP_REST_Response {
		$form_id = (int) $request->get_param( 'id' );
		$form    = get_post( $form_id );

		if ( ! $form || 'am_form' !== $form->post_type ) {
			return new \WP_REST_Response( array( 'message' => __( 'Form not found.', 'agency-manager' ) ), 404 );
		}

		// Trash, not force-delete — recoverable, matches the classic page's caution.
		wp_trash_post( $form_id );

		return new \WP_REST_Response( array( 'deleted' => true ) );
	}
}
