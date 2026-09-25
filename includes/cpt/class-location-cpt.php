<?php
namespace Nettalo\TalentLocationManagement\Cpt;

use Nettalo\TalentLocationManagement\Compat\Registration_Guard;

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
					'name'               => __( 'Locations', 'nettwebs-talent-location-management' ),
					'singular_name'      => __( 'Location', 'nettwebs-talent-location-management' ),
					'add_new_item'       => __( 'Add New Location', 'nettwebs-talent-location-management' ),
					'edit_item'          => __( 'Edit Location', 'nettwebs-talent-location-management' ),
					'new_item'           => __( 'New Location', 'nettwebs-talent-location-management' ),
					'view_item'          => __( 'View Location', 'nettwebs-talent-location-management' ),
					'search_items'       => __( 'Search Locations', 'nettwebs-talent-location-management' ),
					'not_found'          => __( 'No locations found', 'nettwebs-talent-location-management' ),
					'featured_image'     => __( 'Hero Photo', 'nettwebs-talent-location-management' ),
					'set_featured_image' => __( 'Set hero photo', 'nettwebs-talent-location-management' ),
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
