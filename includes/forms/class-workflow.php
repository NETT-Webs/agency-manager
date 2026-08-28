<?php
namespace AgencyManager\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submitted -> Review -> Approved -> Published, or -> Rejected/Archived at
 * any point before Published. Approve is purely a status change (a human
 * checkpoint — nothing goes public automatically); Publish is the one place
 * a submission's field values get mapped onto a real talent/location post.
 */
class Workflow {

	public const STATUSES = array( 'submitted', 'review', 'approved', 'published', 'rejected', 'archived' );

	public function set_status( int $submission_id, string $status ): bool {
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return false;
		}

		$old_status = (string) get_post_meta( $submission_id, '_am_status', true );

		update_post_meta( $submission_id, '_am_status', $status );

		/**
		 * Fires whenever a submission's workflow status changes (including
		 * to 'published', from publish_submission() below).
		 *
		 * @param int    $submission_id
		 * @param string $status     The new status.
		 * @param string $old_status The previous status.
		 */
		do_action( 'am_submission_status_changed', $submission_id, $status, $old_status );

		return true;
	}

	/**
	 * Maps an Approved submission's field values onto a new talent/location
	 * post and marks the submission Published. Returns the new post ID, or
	 * 0 if the submission wasn't Approved or the post couldn't be created.
	 */
	public function publish_submission( int $submission_id ): int {
		$status = get_post_meta( $submission_id, '_am_status', true );
		if ( 'approved' !== $status ) {
			return 0;
		}

		$type   = get_post_meta( $submission_id, '_am_type', true );
		$values = json_decode( (string) get_post_meta( $submission_id, '_am_field_values', true ), true );
		$values = is_array( $values ) ? $values : array();

		$post_type = 'location' === $type ? 'location' : 'talent';
		$title     = $values['full_name'] ?? ( $values['location_name'] ?? __( 'Untitled', 'agency-manager' ) );

		$post_id = wp_insert_post(
			array(
				'post_type'   => $post_type,
				'post_title'  => sanitize_text_field( $title ),
				// The application itself was already reviewed by a human;
				// the resulting profile still starts as a draft so the
				// agency can finish it (more photos, bio copy) before it
				// goes live — Publish here means "became a real profile",
				// not "went live on the site".
				'post_status' => 'draft',
			)
		);

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return 0;
		}

		$field_defs = $this->get_mapped_fields( $submission_id );

		Field_Mapper::apply( $post_id, $post_type, $field_defs, $values );

		update_post_meta( $post_id, '_am_active', 1 );
		update_post_meta( $post_id, '_am_source_submission', $submission_id );

		update_post_meta( $submission_id, '_am_published_post_id', $post_id );
		$this->set_status( $submission_id, 'published' );

		/**
		 * Fires after a submission is turned into a real talent/location
		 * post. The post is still a draft at this point (see the status
		 * comment above) — this is the hook to, for example, notify the
		 * agency that a profile is ready to finish and publish.
		 *
		 * @param int $post_id       The new talent/location post ID.
		 * @param int $submission_id The source submission ID.
		 */
		do_action( 'am_submission_published', $post_id, $submission_id );

		return (int) $post_id;
	}

	/**
	 * Preview of what publish_submission() would write, without writing —
	 * used by the Applications review screen to show "Mapped Talent/Location
	 * Data" alongside the raw submitted values before an admin approves.
	 *
	 * @return array<int,array{label:string,target:string,value:mixed}>
	 */
	public function preview_mapping( int $submission_id ): array {
		$type      = get_post_meta( $submission_id, '_am_type', true );
		$post_type = 'location' === $type ? 'location' : 'talent';
		$values    = json_decode( (string) get_post_meta( $submission_id, '_am_field_values', true ), true );
		$values    = is_array( $values ) ? $values : array();

		return Field_Mapper::preview( $post_type, $this->get_mapped_fields( $submission_id ), $values );
	}

	/**
	 * The submitting form's field definitions, with a one-time backward-
	 * compatibility backfill applied (and persisted) if the form predates
	 * the Form Builder's mapping system — see
	 * Field_Mapper::backfill_legacy_defaults().
	 */
	private function get_mapped_fields( int $submission_id ): array {
		$form_id = (int) get_post_meta( $submission_id, '_am_form_id', true );
		if ( ! $form_id ) {
			return array();
		}

		$renderer = new Form_Renderer();
		$fields   = $renderer->get_fields( $form_id );

		$backfilled = Field_Mapper::backfill_legacy_defaults( $fields );

		if ( $backfilled['changed'] ) {
			update_post_meta( $form_id, '_am_form_fields', wp_json_encode( $backfilled['fields'] ) );
		}

		return $backfilled['fields'];
	}
}
