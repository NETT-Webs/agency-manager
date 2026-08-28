<?php
namespace AgencyManager\Cpt;

use AgencyManager\Compat\Registration_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Taxonomies {

	private Registration_Guard $guard;

	public function __construct( Registration_Guard $guard ) {
		$this->guard = $guard;
	}

	public function register(): void {
		add_action( 'init', array( $this, 'maybe_register_taxonomies' ), 20 );
	}

	public function maybe_register_taxonomies(): void {
		$this->maybe_register(
			'talent_category',
			'talent',
			array(
				'hierarchical'      => true,
				'labels'            => array(
					'name'          => __( 'Talent Categories', 'agency-manager' ),
					'singular_name' => __( 'Talent Category', 'agency-manager' ),
				),
				'public'            => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'talent-category' ),
				'show_in_rest'      => true,
			)
		);

		$this->maybe_register(
			'talent_group',
			'talent',
			array(
				'hierarchical'      => true,
				'labels'            => array(
					'name'          => __( 'Talent Groups', 'agency-manager' ),
					'singular_name' => __( 'Talent Group', 'agency-manager' ),
				),
				'public'            => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'talent-group' ),
				'show_in_rest'      => true,
			)
		);

		$this->maybe_register(
			'location_type',
			'location',
			array(
				'hierarchical'      => true,
				// Admin-facing label is "Categories" (matches the Locations
				// submenu wording) even though the taxonomy key itself stays
				// `location_type` — renaming the key would mean a data
				// migration for zero benefit, this is a display-only choice.
				'labels'            => array(
					'name'          => __( 'Categories', 'agency-manager' ),
					'singular_name' => __( 'Category', 'agency-manager' ),
					'menu_name'     => __( 'Categories', 'agency-manager' ),
				),
				'public'            => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'location-type' ),
				'show_in_rest'      => true,
			)
		);
	}

	private function maybe_register( string $taxonomy, string $post_type, array $args ): void {
		if ( ! $this->guard->should_register_taxonomy( $taxonomy ) ) {
			return;
		}

		register_taxonomy( $taxonomy, $post_type, $args );
	}
}
