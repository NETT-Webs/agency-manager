<?php
namespace AgencyManager\Cpt;

use AgencyManager\Compat\Registration_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Location_Cpt {

	private Registration_Guard $guard;

	public function __construct( Registration_Guard $guard ) {
		$this->guard = $guard;
	}

	public function register(): void {
		add_action( 'init', array( $this, 'maybe_register_post_type' ), 20 );
	}

	public function maybe_register_post_type(): void {
		if ( ! $this->guard->should_register_post_type( 'location' ) ) {
			return;
		}

		register_post_type(
			'location',
			array(
				'labels'        => array(
					'name'               => __( 'Locations', 'agency-manager' ),
					'singular_name'      => __( 'Location', 'agency-manager' ),
					'add_new_item'       => __( 'Add New Location', 'agency-manager' ),
					'edit_item'          => __( 'Edit Location', 'agency-manager' ),
					'new_item'           => __( 'New Location', 'agency-manager' ),
					'view_item'          => __( 'View Location', 'agency-manager' ),
					'search_items'       => __( 'Search Locations', 'agency-manager' ),
					'not_found'          => __( 'No locations found', 'agency-manager' ),
					'featured_image'     => __( 'Hero Photo', 'agency-manager' ),
					'set_featured_image' => __( 'Set hero photo', 'agency-manager' ),
				),
				'public'        => true,
				'has_archive'   => 'locations',
				'rewrite'       => array(
					'slug'       => 'locations',
					'with_front' => false,
				),
				'menu_icon'     => 'dashicons-camera',
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'show_in_rest'  => true,
				'show_in_menu'  => 'agency-manager',
			)
		);
	}
}
