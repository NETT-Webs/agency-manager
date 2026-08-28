<?php
namespace AgencyManager\Forms;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Notifications {

	public function register(): void {
		add_action( 'am_submission_created', array( $this, 'notify' ) );
	}

	public function notify( int $submission_id ): void {
		$to = Settings::get( 'notification_email', get_option( 'admin_email' ) );

		/**
		 * Filters the recipient of the new-submission notification email.
		 *
		 * @param string $to            Recipient email address.
		 * @param int    $submission_id
		 */
		$to = apply_filters( 'am_notification_recipient', $to, $submission_id );

		if ( ! $to ) {
			return;
		}

		$form_id = (int) get_post_meta( $submission_id, '_am_form_id', true );
		$form    = $form_id ? get_post( $form_id ) : null;
		$values  = json_decode( (string) get_post_meta( $submission_id, '_am_field_values', true ), true );
		$values  = is_array( $values ) ? $values : array();

		$subject = sprintf(
			/* translators: 1: site name, 2: form title */
			__( '[%1$s] New submission — %2$s', 'agency-manager' ),
			get_bloginfo( 'name' ),
			$form ? $form->post_title : __( 'Application', 'agency-manager' )
		);

		$lines = array();
		foreach ( $values as $key => $value ) {
			if ( is_numeric( $value ) && get_post( (int) $value ) ) {
				continue; // Skip raw attachment IDs (uploaded files) in the plain-text summary.
			}
			$lines[] = ucwords( str_replace( '_', ' ', (string) $key ) ) . ': ' . $value;
		}

		$type = 'location' === get_post_meta( $submission_id, '_am_type', true ) ? 'location' : 'talent';
		$body = implode( "\n", $lines ) . "\n\n" . admin_url( 'admin.php?page=agency-manager-applications&type=' . $type );

		wp_mail( $to, $subject, $body );
	}
}
