<?php
namespace AgencyManager\Elementor;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side storage for named "Widget Style Presets" — a full snapshot of
 * every Elementor Style-tab control's value, saved under a name (e.g.
 * "Luxury Cards") so it can be loaded onto any other Agency Manager widget
 * instance, on this site or, via Import/Export, on another installation.
 * Stored as one slice of the existing `am_settings` option (keyed by name,
 * so create-or-update and Import/Export both work by the same simple
 * upsert-by-key merge every other named entity in this plugin already uses).
 *
 * The three AJAX actions here are the only server-side logic this feature
 * needs — "Load" never touches the server at all, since the full preset
 * list (values included) is localized to the Elementor editor script once
 * per page load; see Elementor_Integration::enqueue_editor_scripts() and
 * assets/elementor/widget-style-presets.js.
 */
class Widget_Style_Presets {

	private const NONCE_ACTION = 'am_widget_style_presets';

	public function register(): void {
		add_action( 'wp_ajax_am_save_widget_style_preset', array( $this, 'ajax_save' ) );
		add_action( 'wp_ajax_am_delete_widget_style_preset', array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_am_rename_widget_style_preset', array( $this, 'ajax_rename' ) );
	}

	/**
	 * @return array<string,array<string,mixed>> preset name => (Style-tab control name => value)
	 */
	public static function all(): array {
		return Settings::get_widget_style_presets();
	}

	public static function nonce_action(): string {
		return self::NONCE_ACTION;
	}

	private function verify_request(): void {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::NONCE_ACTION ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'agency-manager' ) ), 403 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'agency-manager' ) ), 403 );
		}
	}

	public function ajax_save(): void {
		$this->verify_request();

		$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$values_raw = isset( $_POST['values'] ) ? wp_unslash( $_POST['values'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- json_decode()d below, then every value sanitized individually.
		$values     = json_decode( (string) $values_raw, true );

		if ( '' === $name || ! is_array( $values ) ) {
			wp_send_json_error( array( 'message' => __( 'A preset name and style values are required.', 'agency-manager' ) ), 400 );
		}

		$settings = Settings::all();
		$settings['widget_style_presets'][ $name ] = self::sanitize_values( $values );
		Settings::update( $settings );

		wp_send_json_success( array( 'presets' => Settings::get_widget_style_presets() ) );
	}

	public function ajax_delete(): void {
		$this->verify_request();

		$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$settings = Settings::all();
		unset( $settings['widget_style_presets'][ $name ] );
		Settings::update( $settings );

		wp_send_json_success( array( 'presets' => Settings::get_widget_style_presets() ) );
	}

	public function ajax_rename(): void {
		$this->verify_request();

		$old_name = isset( $_POST['old_name'] ) ? sanitize_text_field( wp_unslash( $_POST['old_name'] ) ) : '';
		$new_name = isset( $_POST['new_name'] ) ? sanitize_text_field( wp_unslash( $_POST['new_name'] ) ) : '';

		if ( '' === $old_name || '' === $new_name ) {
			wp_send_json_error( array( 'message' => __( 'Both the existing and new preset name are required.', 'agency-manager' ) ), 400 );
		}

		$settings = Settings::all();
		$presets  = $settings['widget_style_presets'] ?? array();

		if ( ! isset( $presets[ $old_name ] ) ) {
			wp_send_json_error( array( 'message' => __( 'That preset no longer exists.', 'agency-manager' ) ), 404 );
		}

		$values = $presets[ $old_name ];
		unset( $presets[ $old_name ] );
		$presets[ $new_name ]              = $values;
		$settings['widget_style_presets'] = $presets;
		Settings::update( $settings );

		wp_send_json_success( array( 'presets' => Settings::get_widget_style_presets() ) );
	}

	/**
	 * Style-tab control values are colours, dimension/slider objects
	 * ({size, unit} or {top,right,bottom,left,unit}), and Typography group
	 * sub-fields — a recursive sanitizer that preserves numeric leaves and
	 * runs sanitize_text_field() on string leaves handles every shape
	 * Elementor's own controls produce, without needing to special-case
	 * each control type.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private static function sanitize_values( $value ) {
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $key => $item ) {
				$out[ sanitize_key( (string) $key ) ] = self::sanitize_values( $item );
			}
			return $out;
		}

		if ( is_numeric( $value ) ) {
			return $value;
		}

		return sanitize_text_field( (string) $value );
	}
}
