<?php
namespace AgencyManager\Admin;

use AgencyManager\Forms\Field_Types;
use AgencyManager\Forms\Form_Renderer;
use AgencyManager\Forms\Mapping_Targets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The three-column drag-and-drop Form Builder: Field Library (left) / Form
 * Canvas (center) / Selected Field Settings (right). All editing happens
 * client-side (assets/admin/form-builder.js) against the field list
 * localized here; "Save" posts the whole list to
 * Form_Builder_Ajax::ajax_save() in one request. Reached from
 * Agency Manager -> Forms ("Edit" on any form).
 */
class Form_Builder_Page {

	public function enqueue(): void {
		Media_Picker_Assets::enqueue();

		wp_enqueue_style( 'am-form-builder', AM_PLUGIN_URL . 'assets/admin/form-builder.css', array( 'am-admin' ), AM_VERSION );
		wp_enqueue_script( 'am-form-builder', AM_PLUGIN_URL . 'assets/admin/form-builder.js', array( 'jquery' ), AM_VERSION, true );
	}

	public function render( int $form_id ): void {
		$form = get_post( $form_id );

		if ( ! $form || 'am_form' !== $form->post_type ) {
			echo '<div class="wrap am-admin-page"><p>' . esc_html__( 'Form not found.', 'agency-manager' ) . '</p></div>';
			return;
		}

		$this->enqueue();

		$type       = get_post_meta( $form_id, '_am_form_type', true );
		$type       = in_array( $type, array( 'talent', 'location', 'general' ), true ) ? $type : 'talent';
		$fields     = ( new Form_Renderer() )->get_fields( $form_id );
		$shortcode  = '[agency_form id="' . $form_id . '"]';
		$confirm    = get_post_meta( $form_id, '_am_form_confirmation_message', true );

		wp_localize_script(
			'am-form-builder',
			'amFormBuilder',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( Form_Builder_Ajax::NONCE_ACTION ),
				'formId'       => $form_id,
				'formTitle'    => $form->post_title,
				'formType'     => $type,
				'confirmation' => $confirm ? $confirm : __( 'Thank you — your submission has been received.', 'agency-manager' ),
				'fields'       => $fields,
				'library'      => Field_Types::library(),
				'types'        => Field_Types::types(),
				'mappingTargets' => array(
					'talent'   => Mapping_Targets::get( 'talent' ),
					'location' => Mapping_Targets::get( 'location' ),
				),
				'i18n'         => array(
					'saved'            => __( 'Form saved.', 'agency-manager' ),
					'error'            => __( 'Something went wrong — please try again.', 'agency-manager' ),
					'unsavedChanges'   => __( 'You have unsaved changes. Leave this page anyway?', 'agency-manager' ),
					'confirmDeleteField' => __( 'Remove this field from the form?', 'agency-manager' ),
					'noFieldSelected'  => __( 'Select a field on the canvas to edit its settings.', 'agency-manager' ),
					'emptyCanvas'      => __( 'Drag a field from the library on the left to start building your form.', 'agency-manager' ),
					'copied'           => __( 'Shortcode copied.', 'agency-manager' ),
				),
			)
		);
		?>
		<div class="wrap am-admin-page am-form-builder-page">
			<h1>
				<?php
				printf(
					/* translators: %s: form title */
					esc_html__( 'Edit Form: %s', 'agency-manager' ),
					esc_html( $form->post_title )
				);
				?>
			</h1>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=agency-manager-forms' ) ); ?>">&larr; <?php esc_html_e( 'All Forms', 'agency-manager' ); ?></a>
				&nbsp;&middot;&nbsp;
				<?php esc_html_e( 'Shortcode:', 'agency-manager' ); ?>
				<code id="am-fb-shortcode"><?php echo esc_html( $shortcode ); ?></code>
				<button type="button" class="button button-small" id="am-fb-copy-shortcode" data-shortcode="<?php echo esc_attr( $shortcode ); ?>"><?php esc_html_e( 'Copy', 'agency-manager' ); ?></button>
			</p>

			<div id="am-form-builder-root" class="am-form-builder" data-form-id="<?php echo esc_attr( $form_id ); ?>">
				<p><?php esc_html_e( 'Loading builder…', 'agency-manager' ); ?></p>
			</div>
		</div>
		<?php
	}
}
