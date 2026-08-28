<?php
namespace AgencyManager\Cpt;

use AgencyManager\Compat\Registration_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Talent_Cpt {

	private Registration_Guard $guard;

	public function __construct( Registration_Guard $guard ) {
		$this->guard = $guard;
	}

	public function register(): void {
		// Priority 20: registers after anything a theme adds at the default
		// priority 10, so should_register_post_type() sees accurate state.
		add_action( 'init', array( $this, 'maybe_register_post_type' ), 20 );
	}

	public function maybe_register_post_type(): void {
		if ( ! $this->guard->should_register_post_type( 'talent' ) ) {
			return;
		}

		register_post_type(
			'talent',
			array(
				'labels'        => array(
					'name'               => __( 'Talent', 'agency-manager' ),
					'singular_name'      => __( 'Talent', 'agency-manager' ),
					'add_new_item'       => __( 'Add New Talent', 'agency-manager' ),
					'edit_item'          => __( 'Edit Talent', 'agency-manager' ),
					'new_item'           => __( 'New Talent', 'agency-manager' ),
					'view_item'          => __( 'View Talent', 'agency-manager' ),
					'search_items'       => __( 'Search Talent', 'agency-manager' ),
					'not_found'          => __( 'No talent found', 'agency-manager' ),
					'featured_image'     => __( 'Profile Photo', 'agency-manager' ),
					'set_featured_image' => __( 'Set profile photo', 'agency-manager' ),
				),
				'public'        => true,
				'has_archive'   => 'talent',
				'rewrite'       => array(
					'slug'       => 'talent',
					'with_front' => false,
				),
				'menu_icon'     => 'dashicons-groups',
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'show_in_rest'  => true,
				// Nests under the plugin's own top-level menu instead of
				// creating a separate one — only takes effect when this
				// class actually registers the CPT (see Registration_Guard).
				'show_in_menu'  => 'agency-manager',
			)
		);
	}
}
