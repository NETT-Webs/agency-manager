<?php
namespace Nettalo\TalentLocationManagement\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One post per submitted application. Managed through the Forms admin
 * screen's own submissions table (Workflow status + row actions), not the
 * native post-list UI.
 */
class Submission_Cpt {

	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	public function register_post_type(): void {
		register_post_type(
			'am_submission',
			array(
				'labels'       => array(
					'name'          => __( 'Submissions', 'nettwebs-talent-location-management' ),
					'singular_name' => __( 'Submission', 'nettwebs-talent-location-management' ),
				),
				'public'       => false,
				'show_ui'      => false,
				'show_in_rest' => false,
				'supports'     => array( 'title' ),
			)
		);
	}
}
