<?php
namespace AgencyManager\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared base for Agency Manager's REST API (`agency-manager/v1`) — the
 * bridge the new React admin UI talks to. Every route here is a thin
 * wrapper around existing PHP classes (Dashboard_Data, Shortcode_Reference,
 * Settings, ...); no business logic is duplicated or rewritten, only
 * exposed as JSON. Same `manage_options` capability gate used by every
 * other admin surface in this plugin (Website_Display_Page,
 * Widget_Style_Presets, Form_Builder_Ajax, ...) — this is an admin-only
 * API, never intended for logged-out/public use.
 */
abstract class Rest_Controller {

	public const NAMESPACE_V1 = 'agency-manager/v1';

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	abstract public function register_routes(): void;

	/**
	 * @param \WP_REST_Request $request Unused — capability is the same regardless of route/params.
	 */
	public function check_permission( \WP_REST_Request $request ): bool { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedFunctionParameter
		return current_user_can( 'manage_options' );
	}
}
