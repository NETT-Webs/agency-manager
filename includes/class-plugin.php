<?php
namespace AgencyManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central bootstrap. Every subsystem is wired onto its own WordPress hook here;
 * nothing below does real work until that hook actually fires, so instantiating
 * this class at plugin-load time is safe and side-effect-free.
 */
class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Registration guard decides, per data type, whether this plugin or the
		// active theme owns CPT/taxonomy/meta-box registration. It is a plain
		// query object (no hooks of its own), shared with every CPT subsystem.
		$guard = new Compat\Registration_Guard();

		( new Cpt\Talent_Cpt( $guard ) )->register();
		( new Cpt\Location_Cpt( $guard ) )->register();
		( new Cpt\Taxonomies( $guard ) )->register();
		( new Cpt\Meta_Boxes( $guard ) )->register();
		( new Cpt\Term_Meta( $guard ) )->register();

		( new Forms\Form_Cpt() )->register();
		( new Forms\Submission_Cpt() )->register();
		( new Forms\Form_Renderer() )->register();
		( new Forms\Notifications() )->register();
		( new Forms\Csv_Exporter() )->register();

		( new Frontend\Templates() )->register();
		( new Frontend\Template_Loader() )->register();

		( new Shortcodes\Shortcodes() )->register();

		( new Elementor\Elementor_Integration() )->register();

		( new Export_Import\Exporter() )->register();
		( new Export_Import\Importer() )->register();

		// REST routes must register regardless of is_admin() — a
		// /wp-json/ request isn't admin context — but every route is
		// still gated by its own manage_options permission_callback.
		( new Rest\Dashboard_Rest_Controller() )->register();
		( new Rest\Applications_Rest_Controller() )->register();
		( new Rest\Forms_Rest_Controller() )->register();
		( new Rest\Content_Rest_Controller() )->register();
		( new Rest\Display_Rest_Controller() )->register();
		( new Rest\Import_Export_Rest_Controller() )->register();
		( new Rest\Settings_Rest_Controller() )->register();
		( new Rest\Talent_Rest_Controller() )->register();
		( new Rest\Location_Rest_Controller() )->register();
		( new Rest\Csv_Import_Rest_Controller() )->register();

		if ( is_admin() ) {
			( new Admin\Admin() )->register();
			( new Admin\Shortcode_Reference() )->register();
			( new Admin\Form_Builder_Ajax() )->register();
			( new Wizard\Setup_Wizard() )->register();
		}
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'agency-manager', false, dirname( AM_PLUGIN_BASENAME ) . '/languages' );
	}
}
