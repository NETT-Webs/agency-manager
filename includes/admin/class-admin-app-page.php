<?php
namespace AgencyManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mounts the one React admin application bundle across every Agency Manager
 * screen. Each `add_submenu_page()` entry in `Admin::add_menu()` calls
 * `render( $screen )` with its own screen key (e.g. 'applications',
 * 'forms'); the same `build/index.js` is enqueued every time, and the
 * localized `amAdminApp.screen` value tells the client-side app (see
 * src/admin-app/app.js) which page component to mount inside the shared
 * Shell. There is no client-side router — every screen is still a full
 * WordPress admin-menu navigation, matching how wp-admin already works.
 *
 * The Form Builder is mounted here too ('form-builder' screen) — it loads
 * its field data from the REST API like every other screen, but still
 * *saves* through the original Form_Builder_Ajax admin-ajax endpoint
 * unchanged, so its nonce is localized alongside the REST nonce below.
 * Native Talent/Location post editors are NOT mounted here — they keep
 * WordPress's own edit-post screens, since Registration_Guard means this
 * plugin doesn't own that UI on this install.
 */
class Admin_App_Page {

	public function register(): void {
		// No hooks of its own — menu wiring lives in Admin::add_menu(), same as Dashboard_Page.
	}

	public function enqueue( string $screen ): void {
		$asset_file = AM_PLUGIN_DIR . 'build/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		// The Website Display screen's Scouting Images picker, and the
		// Talent/Location editors' Featured Image + Gallery pickers, all
		// call the core wp.media API directly from React — same media
		// modal every other admin screen in this plugin already uses.
		if ( in_array( $screen, array( 'display', 'talent-edit', 'location-edit' ), true ) ) {
			wp_enqueue_media();
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'am-admin-app',
			AM_PLUGIN_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		// wp-scripts names the extracted stylesheet after the webpack chunk
		// ("style-index.css"), not after the entry file ("index.css").
		if ( file_exists( AM_PLUGIN_DIR . 'build/style-index.css' ) ) {
			wp_enqueue_style( 'am-admin-app', AM_PLUGIN_URL . 'build/style-index.css', array(), $asset['version'] );
		}

		$current_user = wp_get_current_user();
		$record_id    = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, selects which record the editor screen loads.
		$form_id      = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, selects which form the builder screen loads.

		wp_localize_script(
			'am-admin-app',
			'amAdminApp',
			array(
				'screen'      => $screen,
				'recordId'    => $record_id,
				'restUrl'     => esc_url_raw( rest_url() ),
				'restNonce'   => wp_create_nonce( 'wp_rest' ),
				'importExport' => array(
					'adminPostUrl'  => admin_url( 'admin-post.php' ),
					'exportNonce'   => wp_create_nonce( 'am_export' ),
					'importNonce'   => wp_create_nonce( 'am_import' ),
				),
				'formBuilder' => array(
					'formId'  => $form_id,
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( Form_Builder_Ajax::NONCE_ACTION ),
				),
				'currentUser' => array(
					'name'      => $current_user->display_name,
					'avatarUrl' => get_avatar_url( $current_user->ID, array( 'size' => 48 ) ),
				),
				'links' => array(
					'dashboard'          => admin_url( 'admin.php?page=agency-manager' ),
					'talent'             => admin_url( 'admin.php?page=agency-manager-talent' ),
					'talentList'         => admin_url( 'edit.php?post_type=talent' ),
					'talentAdd'          => admin_url( 'admin.php?page=agency-manager-talent&view=add' ),
					'talentEdit'         => admin_url( 'admin.php?page=agency-manager-talent&view=edit&id=' ),
					'talentCategories'   => admin_url( 'edit-tags.php?taxonomy=talent_category&post_type=talent' ),
					'talentGroups'       => admin_url( 'edit-tags.php?taxonomy=talent_group&post_type=talent' ),
					'locations'          => admin_url( 'admin.php?page=agency-manager-locations' ),
					'locationList'       => admin_url( 'edit.php?post_type=location' ),
					'locationAdd'        => admin_url( 'admin.php?page=agency-manager-locations&view=add' ),
					'locationEdit'       => admin_url( 'admin.php?page=agency-manager-locations&view=edit&id=' ),
					'locationCategories' => admin_url( 'edit-tags.php?taxonomy=location_type&post_type=location' ),
					'applications'       => admin_url( 'admin.php?page=agency-manager-applications' ),
					'forms'              => admin_url( 'admin.php?page=agency-manager-forms' ),
					'formBuilder'        => admin_url( 'admin.php?page=agency-manager-forms&view=builder&form_id=' ),
					'display'            => admin_url( 'admin.php?page=agency-manager-display' ),
					'importExport'       => admin_url( 'admin.php?page=agency-manager-import-export' ),
					'csvImport'          => admin_url( 'admin.php?page=agency-manager-import-export&view=csv' ),
					'settings'           => admin_url( 'admin.php?page=agency-manager-settings' ),
					'profile'            => admin_url( 'profile.php' ),
				),
			)
		);
	}

	public function render( string $screen = 'dashboard' ): void {
		$this->enqueue( $screen );
		echo '<div id="agency-manager-root" class="am-admin-app-root">';
		echo '<noscript>' . esc_html__( 'Agency Manager requires JavaScript to be enabled.', 'agency-manager' ) . '</noscript>';
		echo '</div>';
	}
}
