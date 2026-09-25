<?php
namespace Nettalo\TalentLocationManagement\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the "Agency Manager" top-level admin menu. Talent/Locations
 * submenus only appear when this plugin itself owns those CPTs — WordPress
 * nests them automatically via each CPT's own 'show_in_menu' => 'agency-manager'
 * argument, so on a site where the Registration_Guard deferred (e.g. Eden
 * Cast today), those two items are simply absent and the admin only ever
 * sees the theme's own existing menu items for Talent/Locations.
 *
 * "Applications" has one entry point in the sidebar (WordPress admin menus
 * are only two levels deep); the Talent/Location split happens as in-page
 * tabs on that screen — see Applications_Page::render_type_tabs().
 */
class Admin {

	private Dashboard_Page $dashboard_page;
	private Admin_App_Page $admin_app_page;
	private Applications_Page $applications_page;
	private Website_Display_Page $website_display_page;
	private Settings_Page $settings_page;
	private Forms_Page $forms_page;
	private Import_Export_Page $import_export_page;

	public function __construct() {
		$this->dashboard_page       = new Dashboard_Page();
		$this->admin_app_page       = new Admin_App_Page();
		$this->applications_page    = new Applications_Page();
		$this->website_display_page = new Website_Display_Page();
		$this->settings_page        = new Settings_Page();
		$this->forms_page           = new Forms_Page();
		$this->import_export_page   = new Import_Export_Page();
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		$this->dashboard_page->register();
		$this->admin_app_page->register();
		$this->applications_page->register();
		$this->website_display_page->register();
		$this->settings_page->register();
		$this->forms_page->register();
		$this->import_export_page->register();
	}

	public function add_menu(): void {
		add_menu_page(
			__( 'NettWebs Talent & Location Management', 'nettwebs-talent-location-management' ),
			__( 'NettWebs Talent & Location Management', 'nettwebs-talent-location-management' ),
			'manage_options',
			'agency-manager',
			array( $this, 'render_dashboard' ),
			'dashicons-groups',
			// 58.5 sits just below WordPress core's own menu block (Comments
			// is 25; core's own separator is 59) instead of colliding with
			// it — WordPress.org review flagged the previous position (25)
			// for competing with core admin positions.
			58.5
		);

		// Every screen below (except Forms in builder view, and the two
		// classic-PHP pages still under maintenance-only status) is now the
		// React app, with a distinct screen key so the client knows which
		// page component to mount inside the shared Shell — see
		// Admin_App_Page and src/admin-app/app.js. Dashboard_Page/
		// Applications_Page/Website_Display_Page/Import_Export_Page/
		// Settings_Page classes are left in place, unused, as zero-risk
		// fallback references; their admin_init side effects (nonce
		// handlers, redirects) are still registered from Admin::register()
		// and are exactly what the REST controllers now call into.
		add_submenu_page( 'agency-manager', __( 'Dashboard', 'nettwebs-talent-location-management' ), __( 'Dashboard', 'nettwebs-talent-location-management' ), 'manage_options', 'agency-manager', array( $this, 'render_dashboard' ) );
		add_submenu_page( 'agency-manager', __( 'Talent', 'nettwebs-talent-location-management' ), __( 'Talent', 'nettwebs-talent-location-management' ), 'manage_options', 'agency-manager-talent', array( $this, 'render_talent' ) );
		add_submenu_page( 'agency-manager', __( 'Locations', 'nettwebs-talent-location-management' ), __( 'Locations', 'nettwebs-talent-location-management' ), 'manage_options', 'agency-manager-locations', array( $this, 'render_locations' ) );
		add_submenu_page( 'agency-manager', __( 'Applications', 'nettwebs-talent-location-management' ), __( 'Applications', 'nettwebs-talent-location-management' ), 'manage_options', 'agency-manager-applications', array( $this, 'render_applications' ) );
		add_submenu_page( 'agency-manager', __( 'Forms', 'nettwebs-talent-location-management' ), __( 'Forms', 'nettwebs-talent-location-management' ), 'manage_options', 'agency-manager-forms', array( $this, 'render_forms' ) );
		add_submenu_page( 'agency-manager', __( 'Website Display', 'nettwebs-talent-location-management' ), __( 'Website Display', 'nettwebs-talent-location-management' ), 'manage_options', 'agency-manager-display', array( $this, 'render_display' ) );
		add_submenu_page( 'agency-manager', __( 'Import / Export', 'nettwebs-talent-location-management' ), __( 'Import / Export', 'nettwebs-talent-location-management' ), 'manage_options', 'agency-manager-import-export', array( $this, 'render_import_export' ) );
		add_submenu_page( 'agency-manager', __( 'Settings', 'nettwebs-talent-location-management' ), __( 'Settings', 'nettwebs-talent-location-management' ), 'manage_options', 'agency-manager-settings', array( $this, 'render_settings' ) );
	}

	public function render_dashboard(): void {
		$this->admin_app_page->render( 'dashboard' );
	}

	/**
	 * Add/Edit Talent is now a React screen over the existing `talent` CPT
	 * (see Rest\Talent_Rest_Controller and pages/talent-editor.jsx) instead
	 * of WordPress's native post editor — same `view`/`id` query-arg
	 * pattern Forms already uses for its own sub-view (view=builder).
	 */
	public function render_talent(): void {
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view switch, no state change.

		$this->admin_app_page->render( in_array( $view, array( 'add', 'edit' ), true ) ? 'talent-edit' : 'talent' );
	}

	public function render_locations(): void {
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->admin_app_page->render( in_array( $view, array( 'add', 'edit' ), true ) ? 'location-edit' : 'locations' );
	}

	public function render_applications(): void {
		$this->admin_app_page->render( 'applications' );
	}

	/**
	 * The forms *list* and the Form Builder (drag-and-drop field editor,
	 * view=builder) are both the React app now — same shell, same sidebar,
	 * no second page style. The public-form preview (view=preview) still
	 * renders through the original Forms_Page -> Form_Renderer, unchanged,
	 * since that view intentionally shows the real front-end form output,
	 * not an admin screen.
	 */
	public function render_forms(): void {
		$view    = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view switch, no state change.
		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'builder' === $view && $form_id ) {
			$this->admin_app_page->render( 'form-builder' );
			return;
		}

		if ( 'preview' === $view && $form_id ) {
			$this->forms_page->render();
			return;
		}

		$this->admin_app_page->render( 'forms' );
	}

	public function render_display(): void {
		$this->admin_app_page->render( 'display' );
	}

	/**
	 * The CSV Import wizard (Csv_Import\*) is its own screen, reached as
	 * `view=csv` on this same menu item — same view-switch pattern Forms
	 * and Talent/Locations already use.
	 */
	public function render_import_export(): void {
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view switch, no state change.

		$this->admin_app_page->render( 'csv' === $view ? 'csv-import' : 'import-export' );
	}

	public function render_settings(): void {
		$this->admin_app_page->render( 'settings' );
	}

	public function enqueue_assets( string $hook ): void {
		if ( false === strpos( (string) $hook, 'nettwebs-talent-location-management' ) ) {
			return;
		}

		wp_enqueue_style( 'am-admin', NETTALO_PLUGIN_URL . 'assets/admin/admin.css', array(), NETTALO_VERSION );
	}
}
