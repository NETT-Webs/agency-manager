<?php
namespace AgencyManager\Rest;

use AgencyManager\Forms\Workflow;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin REST wrapper around the exact same submission listing/status-change
 * logic Admin\Applications_Page and Forms\Workflow already implement — no
 * new data model, no new business rule. `set_status`/`publish` here do
 * precisely what the nonce'd GET links on the classic page did (see
 * Applications_Page::maybe_change_status()), just over POST + a REST nonce
 * instead of a query-string nonce.
 */
class Applications_Rest_Controller extends Rest_Controller {

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE_V1,
			'/applications',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_applications' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/applications/(?P<id>\d+)/status',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'set_status' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'status' => array( 'required' => true, 'type' => 'string' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/applications/(?P<id>\d+)/publish',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'publish' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	public function get_applications( \WP_REST_Request $request ): \WP_REST_Response {
		$type = 'location' === $request->get_param( 'type' ) ? 'location' : 'talent';

		$submissions = get_posts(
			array(
				'post_type'   => 'am_submission',
				'post_status' => 'any',
				'numberposts' => -1,
				'meta_key'    => '_am_type',
				'meta_value'  => $type,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);

		$workflow = new Workflow();
		$rows     = array();

		foreach ( $submissions as $submission ) {
			$values  = json_decode( (string) get_post_meta( $submission->ID, '_am_field_values', true ), true );
			$values  = is_array( $values ) ? $values : array();
			$status  = (string) get_post_meta( $submission->ID, '_am_status', true );
			$name    = $values['full_name'] ?? ( $values['location_name'] ?? ( $values['contact_name'] ?? '' ) );
			$form_id = (int) get_post_meta( $submission->ID, '_am_form_id', true );
			$form    = $form_id ? get_post( $form_id ) : null;

			$published_post_id = 'published' === $status ? (int) get_post_meta( $submission->ID, '_am_published_post_id', true ) : 0;

			$rows[] = array(
				'id'              => $submission->ID,
				'formTitle'       => $form ? $form->post_title : '',
				'date'            => get_the_date( '', $submission ),
				'name'            => $name ?: __( 'Untitled', 'agency-manager' ),
				'email'           => $values['email'] ?? '',
				'status'          => $status ?: 'submitted',
				'values'          => $values,
				'mapped'          => $workflow->preview_mapping( $submission->ID ),
				'publishedPostId' => $published_post_id,
				'publishedEditUrl' => $published_post_id ? (string) get_edit_post_link( $published_post_id, 'raw' ) : '',
			);
		}

		return new \WP_REST_Response( $rows );
	}

	public function set_status( \WP_REST_Request $request ): \WP_REST_Response {
		$id     = (int) $request->get_param( 'id' );
		$status = sanitize_key( (string) $request->get_param( 'status' ) );

		if ( ! in_array( $status, Workflow::STATUSES, true ) ) {
			return new \WP_REST_Response( array( 'message' => __( 'Invalid status.', 'agency-manager' ) ), 400 );
		}

		$submission = get_post( $id );
		if ( ! $submission || 'am_submission' !== $submission->post_type ) {
			return new \WP_REST_Response( array( 'message' => __( 'Application not found.', 'agency-manager' ) ), 404 );
		}

		( new Workflow() )->set_status( $id, $status );

		return new \WP_REST_Response( array( 'status' => $status ) );
	}

	public function publish( \WP_REST_Request $request ): \WP_REST_Response {
		$id = (int) $request->get_param( 'id' );

		$submission = get_post( $id );
		if ( ! $submission || 'am_submission' !== $submission->post_type ) {
			return new \WP_REST_Response( array( 'message' => __( 'Application not found.', 'agency-manager' ) ), 404 );
		}

		$post_id = ( new Workflow() )->publish_submission( $id );

		if ( ! $post_id ) {
			return new \WP_REST_Response( array( 'message' => __( 'This application must be Approved before it can be published.', 'agency-manager' ) ), 400 );
		}

		return new \WP_REST_Response(
			array(
				'postId'  => $post_id,
				'editUrl' => (string) get_edit_post_link( $post_id, 'raw' ),
			)
		);
	}
}
