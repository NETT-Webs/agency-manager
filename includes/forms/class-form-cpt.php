<?php
namespace AgencyManager\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One post per form definition (Talent Application, Location Submission, or
 * a custom one). Never guarded — nothing on any site this plugin might join
 * registers anything equivalent. Managed entirely through the plugin's own
 * Forms admin screen, not the native post-list UI.
 */
class Form_Cpt {

	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	public function register_post_type(): void {
		register_post_type(
			'am_form',
			array(
				'labels'       => array(
					'name'          => __( 'Forms', 'agency-manager' ),
					'singular_name' => __( 'Form', 'agency-manager' ),
				),
				'public'       => false,
				'show_ui'      => false,
				'show_in_rest' => false,
				'supports'     => array( 'title' ),
			)
		);
	}
}
