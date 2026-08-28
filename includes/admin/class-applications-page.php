<?php
namespace AgencyManager\Admin;

use AgencyManager\Forms\Workflow;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submissions across every form of one type (talent|location), driving the
 * Workflow status machine. This is a different *listing* onto the same
 * am_submission data Forms\Workflow already manages — no new data model,
 * no new nonce action (still `am_change_submission_status`).
 */
class Applications_Page {

	private const NONCE_STATUS_ACTION = 'am_change_submission_status';

	public function register(): void {
		add_action( 'admin_init', array( $this, 'maybe_change_status' ) );
	}

	public function maybe_change_status(): void {
		if ( ! isset( $_GET['am_status_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['am_status_nonce'] ) ), self::NONCE_STATUS_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$submission_id = isset( $_GET['submission_id'] ) ? absint( $_GET['submission_id'] ) : 0;
		$status_action = isset( $_GET['status_action'] ) ? sanitize_key( wp_unslash( $_GET['status_action'] ) ) : '';

		if ( ! $submission_id || ! $status_action ) {
			return;
		}

		$workflow = new Workflow();

		if ( 'publish' === $status_action ) {
			$workflow->publish_submission( $submission_id );
		} else {
			$workflow->set_status( $submission_id, $status_action );
		}

		wp_safe_redirect( remove_query_arg( array( 'am_status_nonce', 'status_action', 'submission_id' ) ) );
		exit;
	}

	public function render(): void {
		$type  = isset( $_GET['type'] ) && 'location' === $_GET['type'] ? 'location' : 'talent';
		$title = 'location' === $type ? __( 'Location Applications', 'agency-manager' ) : __( 'Talent Applications', 'agency-manager' );

		echo '<div class="wrap am-admin-page"><h1>' . esc_html( $title ) . '</h1>';

		$this->render_type_tabs( $type );
		$this->render_csv_links( $type );
		$this->render_submissions( $type );

		echo '<h2>' . esc_html__( 'Shortcodes', 'agency-manager' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Applications are created when a visitor submits one of these public forms.', 'agency-manager' ) . '</p>';
		Shortcode_Reference::render_panel( array( 'forms' ), false );

		echo '</div>';
	}

	private function render_type_tabs( string $current ): void {
		$tabs = array(
			'talent'   => __( 'Talent Applications', 'agency-manager' ),
			'location' => __( 'Location Applications', 'agency-manager' ),
		);

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $type => $label ) {
			$class = $type === $current ? 'nav-tab nav-tab-active' : 'nav-tab';
			echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( admin_url( 'admin.php?page=agency-manager-applications&type=' . $type ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</h2>';
	}

	private function render_csv_links( string $type ): void {
		$forms = get_posts(
			array(
				'post_type'   => 'am_form',
				'post_status' => 'any',
				'numberposts' => -1,
				'meta_key'    => '_am_form_type',
				'meta_value'  => $type,
			)
		);

		if ( empty( $forms ) ) {
			return;
		}

		$links = array();
		foreach ( $forms as $form ) {
			$csv_url = wp_nonce_url( admin_url( 'admin-post.php?action=am_export_submissions_csv&form_id=' . $form->ID ), 'am_export_csv' );
			$links[] = '<a class="button" href="' . esc_url( $csv_url ) . '">' . esc_html(
				sprintf(
					/* translators: %s: form title */
					__( 'Export CSV — %s', 'agency-manager' ),
					$form->post_title
				)
			) . '</a>';
		}

		echo '<p style="margin-top:12px;">' . implode( ' ', $links ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_url()/esc_html() output above.
	}

	private function render_submissions( string $type ): void {
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

		echo '<table class="widefat striped" style="margin-top:12px;"><thead><tr><th>' . esc_html__( 'Form', 'agency-manager' ) . '</th><th>' . esc_html__( 'Submitted', 'agency-manager' ) . '</th><th>' . esc_html__( 'Summary', 'agency-manager' ) . '</th><th>' . esc_html__( 'Status', 'agency-manager' ) . '</th><th>' . esc_html__( 'Actions', 'agency-manager' ) . '</th></tr></thead><tbody>';

		if ( empty( $submissions ) ) {
			echo '<tr><td colspan="5">' . esc_html__( 'No applications yet.', 'agency-manager' ) . '</td></tr>';
		}

		$workflow = new Workflow();

		foreach ( $submissions as $submission ) {
			$values  = json_decode( (string) get_post_meta( $submission->ID, '_am_field_values', true ), true );
			$values  = is_array( $values ) ? $values : array();
			$status  = (string) get_post_meta( $submission->ID, '_am_status', true );
			$name    = $values['full_name'] ?? ( $values['location_name'] ?? ( $values['contact_name'] ?? '—' ) );
			$form_id = (int) get_post_meta( $submission->ID, '_am_form_id', true );
			$form    = $form_id ? get_post( $form_id ) : null;

			echo '<tr>';
			echo '<td>' . esc_html( $form ? $form->post_title : '—' ) . '</td>';
			echo '<td>' . esc_html( get_the_date( '', $submission ) ) . '</td>';
			echo '<td>' . esc_html( $name ) . ( ! empty( $values['email'] ) ? ' &mdash; ' . esc_html( $values['email'] ) : '' ) . '</td>';
			echo '<td>' . esc_html( ucfirst( $status ) ) . '</td>';
			echo '<td>' . $this->render_row_actions( $type, $submission->ID, $status ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_url()'d links.
			echo '</tr>';

			echo '<tr><td colspan="5" style="padding:0;border-top:none;">';
			$this->render_data_preview( $submission->ID, $values, $workflow );
			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * "FORM DATA" (raw submitted values) side by side with "MAPPED
	 * TALENT/LOCATION DATA" (what Workflow::publish_submission() would
	 * write) — so an admin can see exactly what will happen before
	 * approving/publishing, per the Form Builder's field-mapping design.
	 * Collapsed by default (<details>) to keep the submissions table
	 * scannable; no JS required.
	 */
	private function render_data_preview( int $submission_id, array $values, Workflow $workflow ): void {
		if ( empty( $values ) ) {
			return;
		}

		$mapped = $workflow->preview_mapping( $submission_id );
		?>
		<details style="margin:0 0 8px;padding:0 8px;">
			<summary style="cursor:pointer;padding:6px 0;color:#2271b1;"><?php esc_html_e( 'View submitted data', 'agency-manager' ); ?></summary>
			<div style="display:flex;gap:24px;flex-wrap:wrap;padding:4px 0 12px;">
				<div style="flex:1;min-width:220px;">
					<strong><?php esc_html_e( 'Form Data', 'agency-manager' ); ?></strong>
					<table class="widefat" style="margin-top:4px;">
						<tbody>
							<?php foreach ( $values as $key => $value ) : ?>
								<tr>
									<td style="width:40%;"><code><?php echo esc_html( $key ); ?></code></td>
									<td><?php echo esc_html( is_array( $value ) ? implode( ', ', array_map( 'strval', $value ) ) : (string) $value ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div style="flex:1;min-width:220px;">
					<strong><?php esc_html_e( 'Mapped Talent/Location Data', 'agency-manager' ); ?></strong>
					<?php if ( empty( $mapped ) ) : ?>
						<p class="description"><?php esc_html_e( 'No fields on this form are mapped to Talent/Location yet — nothing will be written on Publish beyond the title/photo.', 'agency-manager' ); ?></p>
					<?php else : ?>
						<table class="widefat" style="margin-top:4px;">
							<tbody>
								<?php foreach ( $mapped as $row ) : ?>
									<tr>
										<td style="width:40%;"><?php echo esc_html( $row['target'] ); ?></td>
										<td><?php echo esc_html( is_array( $row['value'] ) ? implode( ', ', array_map( 'strval', $row['value'] ) ) : (string) $row['value'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</details>
		<?php
	}

	private function render_row_actions( string $type, int $submission_id, string $status ): string {
		$actions = array();

		$next_by_status = array(
			'submitted' => array( 'review' => __( 'Move to Review', 'agency-manager' ) ),
			'review'    => array(
				'approved' => __( 'Approve', 'agency-manager' ),
				'rejected' => __( 'Reject', 'agency-manager' ),
			),
			'approved'  => array(
				'publish'  => __( 'Publish', 'agency-manager' ),
				'rejected' => __( 'Reject', 'agency-manager' ),
			),
		);

		foreach ( $next_by_status[ $status ] ?? array() as $action => $label ) {
			$actions[] = '<a href="' . esc_url( $this->status_action_url( $type, $submission_id, $action ) ) . '">' . esc_html( $label ) . '</a>';
		}

		if ( ! in_array( $status, array( 'published', 'archived' ), true ) ) {
			$actions[] = '<a href="' . esc_url( $this->status_action_url( $type, $submission_id, 'archived' ) ) . '">' . esc_html__( 'Archive', 'agency-manager' ) . '</a>';
		}

		if ( 'published' === $status ) {
			$post_id = (int) get_post_meta( $submission_id, '_am_published_post_id', true );
			if ( $post_id ) {
				$actions[] = '<a href="' . esc_url( (string) get_edit_post_link( $post_id ) ) . '">' . esc_html__( 'Edit Profile', 'agency-manager' ) . '</a>';
			}
		}

		return implode( ' | ', $actions );
	}

	private function status_action_url( string $type, int $submission_id, string $action ): string {
		return wp_nonce_url(
			admin_url( 'admin.php?page=agency-manager-applications&type=' . $type . '&submission_id=' . $submission_id . '&status_action=' . $action ),
			self::NONCE_STATUS_ACTION,
			'am_status_nonce'
		);
	}
}
