<?php
namespace AgencyManager\Elementor;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the "Agency Manager" Elementor widget category and its ten
 * widgets. Guarded entirely behind `elementor/loaded` so this plugin never
 * fatals — or even loads any Elementor-touching code — on a site without
 * Elementor installed.
 */
class Elementor_Integration {

	public function register(): void {
		add_action( 'elementor/loaded', array( $this, 'init' ) );
	}

	public function init(): void {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
		( new Widget_Style_Presets() )->register();
	}

	/**
	 * Editor-only (never loaded on the public-facing site): powers the
	 * "Reset to Theme Defaults" button and the "Widget Style" preset
	 * Save/Load/Rename/Delete controls on every Agency Manager widget — see
	 * Widgets\Base_Grid_Widget::register_style_controls() for the controls
	 * that fire the events these scripts listen for. The full preset list
	 * (names + values) is localized once here rather than fetched via AJAX,
	 * so "Load" is instant and needs no server round-trip; Save/Rename/
	 * Delete still call Widget_Style_Presets' AJAX handlers to persist.
	 */
	public function enqueue_editor_scripts(): void {
		// Depends only on jquery — the elementor/editor/after_enqueue_scripts
		// hook itself already guarantees this runs within the editor
		// context; each script's own elementor:init listener is a further
		// safety net that defers all actual logic until Elementor's editor
		// app has finished bootstrapping, regardless of script load order.
		wp_enqueue_script(
			'am-reset-style-defaults',
			AM_PLUGIN_URL . 'assets/elementor/reset-style-defaults.js',
			array( 'jquery' ),
			AM_VERSION,
			true
		);

		wp_enqueue_script(
			'am-widget-style-presets',
			AM_PLUGIN_URL . 'assets/elementor/widget-style-presets.js',
			array( 'jquery' ),
			AM_VERSION,
			true
		);

		wp_localize_script(
			'am-widget-style-presets',
			'amWidgetStylePresets',
			array(
				'presets' => Settings::get_widget_style_presets(),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( Widget_Style_Presets::nonce_action() ),
				'i18n'    => array(
					'saved'        => __( 'Widget style preset saved.', 'agency-manager' ),
					'deleted'      => __( 'Widget style preset deleted.', 'agency-manager' ),
					'renamed'      => __( 'Widget style preset renamed.', 'agency-manager' ),
					'nameRequired' => __( 'Enter a preset name first.', 'agency-manager' ),
					'selectFirst'  => __( 'Select a preset first.', 'agency-manager' ),
					'confirmDelete' => __( 'Delete this widget style preset? This cannot be undone.', 'agency-manager' ),
					'error'        => __( 'Something went wrong — please try again.', 'agency-manager' ),
				),
			)
		);
	}

	/**
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_category( $elements_manager ): void {
		$elements_manager->add_category(
			'agency-manager',
			array(
				'title' => __( 'Agency Manager', 'agency-manager' ),
				'icon'  => 'fa fa-address-card',
			)
		);
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widgets( $widgets_manager ): void {
		$widgets_manager->register( new Widgets\Talent_Grid_Widget() );
		$widgets_manager->register( new Widgets\Talent_Featured_Widget() );
		$widgets_manager->register( new Widgets\Talent_Carousel_Widget() );
		$widgets_manager->register( new Widgets\Talent_Slider_Widget() );
		$widgets_manager->register( new Widgets\Location_Grid_Widget() );
		$widgets_manager->register( new Widgets\Location_Featured_Widget() );
		$widgets_manager->register( new Widgets\Location_Carousel_Widget() );
		$widgets_manager->register( new Widgets\Location_Slider_Widget() );
		$widgets_manager->register( new Widgets\Talent_Application_Form_Widget() );
		$widgets_manager->register( new Widgets\Location_Submission_Form_Widget() );
		$widgets_manager->register( new Widgets\Agency_Form_Widget() );
	}
}
