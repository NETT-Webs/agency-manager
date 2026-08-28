<?php
namespace AgencyManager\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Csv_Exporter {

	public function register(): void {
		add_action( 'admin_post_am_export_submissions_csv', array( $this, 'export' ) );
	}

	public function export(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'agency-manager' ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'am_export_csv' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'agency-manager' ) );
		}

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;

		$submissions = get_posts(
			array(
				'post_type'   => 'am_submission',
				'post_status' => 'any',
				'numberposts' => -1,
				'meta_key'    => '_am_form_id',
				'meta_value'  => $form_id,
			)
		);

		$field_keys = wp_list_pluck( ( new Form_Renderer() )->get_fields( $form_id ), 'key' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="submissions-' . $form_id . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array_map( array( $this, 'defuse_formula' ), array_merge( array( __( 'Submitted', 'agency-manager' ), __( 'Status', 'agency-manager' ) ), $field_keys ) ) );

		foreach ( $submissions as $submission ) {
			$values = json_decode( (string) get_post_meta( $submission->ID, '_am_field_values', true ), true );
			$values = is_array( $values ) ? $values : array();
			$status = get_post_meta( $submission->ID, '_am_status', true );

			$row = array( get_the_date( '', $submission ), $status );
			foreach ( $field_keys as $key ) {
				$row[] = $values[ $key ] ?? '';
			}

			fputcsv( $out, array_map( array( $this, 'defuse_formula' ), $row ) );
		}

		fclose( $out );
		exit;
	}

	/**
	 * Prevents CSV formula injection ("Excel/Sheets formula injection"): a
	 * cell that starts with =, +, -, or @ can be interpreted as a formula
	 * by spreadsheet software when the exported file is opened, letting a
	 * public form submission (this data originates from unauthenticated
	 * visitors) execute attacker-controlled logic on the admin's machine.
	 * Prefixing with a single quote is the standard OWASP-recommended
	 * defusal — spreadsheet apps then render it as inert text.
	 */
	private function defuse_formula( $value ): string {
		$value = (string) $value;

		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@' ), true ) ) {
			return "'" . $value;
		}

		return $value;
	}
}
