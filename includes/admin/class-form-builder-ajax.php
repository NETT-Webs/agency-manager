<?php
namespace Nettalo\TalentLocationManagement\Admin;

use Nettalo\TalentLocationManagement\Forms\Form_Schema;
use Nettalo\TalentLocationManagement\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Saves the Form Builder canvas's current state. One endpoint, one payload
 * (the whole field list + form-level settings) rather than one call per
 * field edit — simplest and most robust for a client-side canvas that
 * mutates freely (drag/reorder/edit) and only talks to the server on
 * "Save". Same nonce+capability pattern as the plugin's one other AJAX
 * feature, Elementor\Widget_Style_Presets.
 */
class Form_Builder_Ajax {

	public const NONCE_ACTION = 'am_form_builder';

	public function register(): void {
		add_action( 'wp_ajax_am_save_form_schema', array( $this, 'ajax_save' ) );
	}

	public function ajax_save(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::NONCE_ACTION ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'nettwebs-talent-location-management' ) ), 403 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'nettwebs-talent-location-management' ) ), 403 );
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$form    = $form_id ? get_post( $form_id ) : null;

		if ( ! $form || 'am_form' !== $form->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Form not found.', 'nettwebs-talent-location-management' ) ), 404 );
		}

		if ( ! isset( $_POST['fields'] ) || ! is_string( $_POST['fields'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing field data.', 'nettwebs-talent-location-management' ) ), 400 );
		}

		// This is a JSON payload, not a text field — sanitize_text_field()
		// would corrupt valid JSON. It is instead decoded, checked with
		// json_last_error(), confirmed to be an array, then every field is
		// whitelisted by key (sanitize_raw_fields()) and every value
		// sanitized per its type (Form_Schema::normalize_fields()) below
		// before anything is stored.
		$raw_fields = json_decode( wp_unslash( $_POST['fields'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $raw_fields ) ) {
			wp_send_json_error( array( 'message' => __( 'Malformed field data.', 'nettwebs-talent-location-management' ) ), 400 );
		}

		$fields = Form_Schema::normalize_fields( $this->sanitize_raw_fields( $raw_fields ) );

		foreach ( $fields as $i => $field ) {
			$fields[ $i ]['order'] = $i;
			if ( is_array( $field['mapping'] ) && 'custom' === ( $field['mapping']['target'] ?? '' ) && ! empty( $field['mapping']['target_key'] ) ) {
				$destination = 'both' === $field['mapping']['destination'] ? 'talent' : $field['mapping']['destination'];
				if ( in_array( $destination, array( 'talent', 'location' ), true ) ) {
					Settings::register_custom_field( $destination, sanitize_key( (string) $field['mapping']['target_key'] ), $field['label'], $field['type'] );
				}
			}
		}

		update_post_meta( $form_id, '_am_form_fields', wp_json_encode( $fields ) );

		$update = array( 'ID' => $form_id );
		if ( isset( $_POST['title'] ) ) {
			$update['post_title'] = sanitize_text_field( wp_unslash( $_POST['title'] ) );
		}
		if ( count( $update ) > 1 ) {
			wp_update_post( $update );
		}

		if ( isset( $_POST['form_type'] ) ) {
			$type = sanitize_key( wp_unslash( $_POST['form_type'] ) );
			update_post_meta( $form_id, '_am_form_type', in_array( $type, array( 'talent', 'location', 'general' ), true ) ? $type : 'talent' );
		}

		if ( isset( $_POST['confirmation_message'] ) ) {
			update_post_meta( $form_id, '_am_form_confirmation_message', sanitize_textarea_field( wp_unslash( $_POST['confirmation_message'] ) ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Form saved.', 'nettwebs-talent-location-management' ),
				'fields'  => $fields,
			)
		);
	}

	/**
	 * Strips anything that isn't a recognized field key before normalizing —
	 * client JS builds this payload, so it's treated as untrusted input,
	 * never trusted field names/values.
	 */
	private function sanitize_raw_fields( array $raw_fields ): array {
		$allowed = array( 'id', 'key', 'label', 'type', 'required', 'order', 'description', 'placeholder', 'default', 'css_class', 'admin_label', 'options', 'min_length', 'max_length', 'file_types', 'max_file_size', 'max_files', 'conditional', 'mapping' );

		$clean = array();
		foreach ( $raw_fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$clean[] = array_intersect_key( $field, array_flip( $allowed ) );
		}

		return $clean;
	}
}
