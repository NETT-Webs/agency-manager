<?php
namespace AgencyManager\Admin;

use AgencyManager\Forms\Form_Renderer;
use AgencyManager\Forms\Form_Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Forms list -> Form Builder (drag-and-drop field editor, see
 * Form_Builder_Page). This page itself only handles the list, and creating/
 * duplicating/deleting whole forms — the old row-based field table editor
 * (render_fields_editor()) has been replaced entirely by the builder.
 * Submission review/workflow lives on the Applications screen
 * (Admin\Applications_Page) — this page manages form *definitions* only.
 */
class Forms_Page {

	private const NONCE_CREATE_ACTION    = 'am_create_form';
	private const NONCE_DUPLICATE_ACTION = 'am_duplicate_form';
	private const NONCE_DELETE_ACTION    = 'am_delete_form';

	public function register(): void {
		add_action( 'admin_init', array( $this, 'maybe_create_form' ) );
		add_action( 'admin_init', array( $this, 'maybe_duplicate_form' ) );
		add_action( 'admin_init', array( $this, 'maybe_delete_form' ) );
	}

	public function maybe_create_form(): void {
		if ( ! isset( $_POST['am_create_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['am_create_form_nonce'] ) ), self::NONCE_CREATE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$title    = isset( $_POST['form_title'] ) ? sanitize_text_field( wp_unslash( $_POST['form_title'] ) ) : '';
		$title    = $title ? $title : __( 'Untitled Form', 'agency-manager' );
		$type     = isset( $_POST['form_type'] ) ? sanitize_key( wp_unslash( $_POST['form_type'] ) ) : 'talent';
		$type     = in_array( $type, array( 'talent', 'location', 'general' ), true ) ? $type : 'talent';
		$template = isset( $_POST['form_template'] ) ? sanitize_key( wp_unslash( $_POST['form_template'] ) ) : 'blank';

		$form_id = wp_insert_post(
			array(
				'post_type'   => 'am_form',
				'post_title'  => $title,
				'post_status' => 'publish',
			)
		);

		if ( ! $form_id || is_wp_error( $form_id ) ) {
			return;
		}

		$templates = Form_Schema::templates();
		$fields    = ( 'blank' !== $template && isset( $templates[ $template ] ) ) ? $templates[ $template ]['fields'] : array();

		update_post_meta( $form_id, '_am_form_type', $type );
		update_post_meta( $form_id, '_am_form_fields', wp_json_encode( Form_Schema::normalize_fields( $fields ) ) );

		wp_safe_redirect( admin_url( 'admin.php?page=agency-manager-forms&view=builder&form_id=' . $form_id ) );
		exit;
	}

	public function maybe_duplicate_form(): void {
		if ( ! isset( $_GET['am_duplicate_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['am_duplicate_nonce'] ) ), self::NONCE_DUPLICATE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$source_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
		$source    = $source_id ? get_post( $source_id ) : null;
		if ( ! $source || 'am_form' !== $source->post_type ) {
			return;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'   => 'am_form',
				/* translators: %s: original form title */
				'post_title'  => sprintf( __( '%s (Copy)', 'agency-manager' ), $source->post_title ),
				'post_status' => 'publish',
			)
		);

		if ( $new_id && ! is_wp_error( $new_id ) ) {
			update_post_meta( $new_id, '_am_form_type', get_post_meta( $source_id, '_am_form_type', true ) );
			update_post_meta( $new_id, '_am_form_fields', get_post_meta( $source_id, '_am_form_fields', true ) );
			$confirmation = get_post_meta( $source_id, '_am_form_confirmation_message', true );
			if ( $confirmation ) {
				update_post_meta( $new_id, '_am_form_confirmation_message', $confirmation );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=agency-manager-forms' ) );
		exit;
	}

	public function maybe_delete_form(): void {
		if ( ! isset( $_GET['am_delete_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['am_delete_nonce'] ) ), self::NONCE_DELETE_ACTION ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
		if ( $form_id ) {
			// Trash, not force-delete — recoverable, matches "do not delete
			// existing Forms" caution; an admin can still permanently delete
			// from Trash if they explicitly want to.
			wp_trash_post( $form_id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=agency-manager-forms' ) );
		exit;
	}

	public function render(): void {
		$view    = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list';
		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;

		if ( 'builder' === $view && $form_id ) {
			( new Form_Builder_Page() )->render( $form_id );
			return;
		}

		echo '<div class="wrap am-admin-page"><h1>' . esc_html__( 'Forms', 'agency-manager' ) . '</h1>';

		if ( 'preview' === $view && $form_id ) {
			$this->render_preview( $form_id );
		} else {
			$this->render_create_panel();
			$this->render_forms_list();
		}

		echo '<h2>' . esc_html__( 'Shortcodes', 'agency-manager' ) . '</h2>';
		Shortcode_Reference::render_panel( array( 'forms' ), false );

		echo '</div>';
	}

	private function render_create_panel(): void {
		$templates = Form_Schema::templates();
		?>
		<div class="am-card am-forms-create">
			<h2><?php esc_html_e( 'Create a New Form', 'agency-manager' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( self::NONCE_CREATE_ACTION, 'am_create_form_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="am_new_form_title"><?php esc_html_e( 'Form Name', 'agency-manager' ); ?></label></th>
						<td><input type="text" id="am_new_form_title" name="form_title" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Talent Application', 'agency-manager' ); ?>"></td>
					</tr>
					<tr>
						<th><label for="am_new_form_type"><?php esc_html_e( 'Form Type', 'agency-manager' ); ?></label></th>
						<td>
							<select id="am_new_form_type" name="form_type">
								<option value="talent"><?php esc_html_e( 'Talent Application', 'agency-manager' ); ?></option>
								<option value="location"><?php esc_html_e( 'Location Application', 'agency-manager' ); ?></option>
								<option value="general"><?php esc_html_e( 'General / Contact', 'agency-manager' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Controls which Applications tab submissions appear under, and which mapping targets are offered.', 'agency-manager' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="am_new_form_template"><?php esc_html_e( 'Start From', 'agency-manager' ); ?></label></th>
						<td>
							<select id="am_new_form_template" name="form_template">
								<option value="blank"><?php esc_html_e( 'Blank Form', 'agency-manager' ); ?></option>
								<?php foreach ( $templates as $key => $template ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $template['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Templates create a normal, fully editable form — every field, label, and mapping can be changed afterward.', 'agency-manager' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Create Form', 'agency-manager' ) ); ?>
			</form>
		</div>
		<?php
	}

	private function render_forms_list(): void {
		$forms = get_posts(
			array(
				'post_type'   => 'am_form',
				'post_status' => 'any',
				'numberposts' => -1,
			)
		);

		echo '<table class="widefat striped am-forms-table"><thead><tr>';
		echo '<th>' . esc_html__( 'Form', 'agency-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Type', 'agency-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'agency-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Fields', 'agency-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Submissions', 'agency-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Created', 'agency-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Last Updated', 'agency-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Shortcode', 'agency-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'agency-manager' ) . '</th>';
		echo '</tr></thead><tbody>';

		if ( empty( $forms ) ) {
			echo '<tr><td colspan="9">' . esc_html__( 'No forms yet — create one above, or start from a template.', 'agency-manager' ) . '</td></tr>';
		}

		$renderer = new Form_Renderer();

		foreach ( $forms as $form ) {
			$field_count = count( $renderer->get_fields( $form->ID ) );
			$form_type   = get_post_meta( $form->ID, '_am_form_type', true );
			$form_type   = in_array( $form_type, array( 'talent', 'location', 'general' ), true ) ? $form_type : 'talent';
			$sub_count   = ( new \WP_Query(
				array(
					'post_type'      => 'am_submission',
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => '_am_form_id',
					'meta_value'     => $form->ID,
				)
			) )->found_posts;

			$shortcode = '[agency_form id="' . $form->ID . '"]';
			$type_labels = array( 'talent' => __( 'Talent Application', 'agency-manager' ), 'location' => __( 'Location Application', 'agency-manager' ), 'general' => __( 'General / Contact', 'agency-manager' ) );

			echo '<tr>';
			echo '<td><strong>' . esc_html( $form->post_title ) . '</strong></td>';
			echo '<td>' . esc_html( $type_labels[ $form_type ] ) . '</td>';
			echo '<td>' . esc_html( ucfirst( $form->post_status ) ) . '</td>';
			echo '<td>' . esc_html( (string) $field_count ) . '</td>';
			echo '<td>' . esc_html( (string) $sub_count ) . ' &mdash; <a href="' . esc_url( admin_url( 'admin.php?page=agency-manager-applications&type=' . ( 'location' === $form_type ? 'location' : 'talent' ) ) ) . '">' . esc_html__( 'View', 'agency-manager' ) . '</a></td>';
			echo '<td>' . esc_html( get_the_date( '', $form ) ) . '</td>';
			echo '<td>' . esc_html( get_the_modified_date( '', $form ) ) . '</td>';
			echo '<td><code class="am-shortcode-copy" data-shortcode="' . esc_attr( $shortcode ) . '" title="' . esc_attr__( 'Click to copy', 'agency-manager' ) . '">' . esc_html( $shortcode ) . '</code></td>';
			echo '<td>' . $this->render_row_actions( $form->ID ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built entirely from esc_url()/esc_html() output.
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	private function render_row_actions( int $form_id ): string {
		$edit      = admin_url( 'admin.php?page=agency-manager-forms&view=builder&form_id=' . $form_id );
		$preview   = admin_url( 'admin.php?page=agency-manager-forms&view=preview&form_id=' . $form_id );
		$duplicate = wp_nonce_url( admin_url( 'admin.php?page=agency-manager-forms&form_id=' . $form_id ), self::NONCE_DUPLICATE_ACTION, 'am_duplicate_nonce' );
		$delete    = wp_nonce_url( admin_url( 'admin.php?page=agency-manager-forms&form_id=' . $form_id ), self::NONCE_DELETE_ACTION, 'am_delete_nonce' );

		$actions   = array();
		$actions[] = '<a href="' . esc_url( $edit ) . '">' . esc_html__( 'Edit', 'agency-manager' ) . '</a>';
		$actions[] = '<a href="' . esc_url( $preview ) . '" target="_blank" rel="noopener">' . esc_html__( 'Preview', 'agency-manager' ) . '</a>';
		$actions[] = '<a href="' . esc_url( $duplicate ) . '">' . esc_html__( 'Duplicate', 'agency-manager' ) . '</a>';
		$actions[] = '<a href="' . esc_url( $delete ) . '" onclick="return confirm(\'' . esc_js( __( 'Move this form to Trash?', 'agency-manager' ) ) . '\');" class="am-delete-link">' . esc_html__( 'Delete', 'agency-manager' ) . '</a>';

		return implode( ' | ', $actions );
	}

	private function render_preview( int $form_id ): void {
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=agency-manager-forms' ) ) . '">&larr; ' . esc_html__( 'All Forms', 'agency-manager' ) . '</a></p>';
		echo '<div class="am-form-preview" style="max-width:640px;">';
		echo ( new Form_Renderer() )->render_form_by_id( $form_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Form_Renderer escapes every dynamic value it outputs internally.
		echo '</div>';
	}
}
