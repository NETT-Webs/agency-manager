<?php
namespace AgencyManager\Forms;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The single, shared list of "where can a value be written on a
 * Talent/Location record" destinations — this type's own `_am_*` meta
 * fields, the always-available core targets (Name, Featured Image,
 * Gallery, Contact Email/Phone, Notes, City), taxonomies, and any
 * admin-registered custom fields.
 *
 * Originally private to Admin\Form_Builder_Page (the Form Builder's field-
 * mapping dropdown); extracted here so the CSV Importer (Csv_Import\*)
 * offers the exact same destination list through the exact same code —
 * one field system, not two. Admin\Form_Builder_Page now delegates to this
 * class instead of keeping its own copy.
 */
class Mapping_Targets {

	/**
	 * @param string $type 'talent'|'location'
	 * @return array<int,array{key:string,label:string,kind:string,group:string}>
	 */
	public static function get( string $type ): array {
		$targets = array(
			array( 'key' => 'post_title', 'label' => __( 'Name', 'nettwebs-talent-location-management' ), 'kind' => 'post_title', 'group' => 'core' ),
			array( 'key' => 'featured_image', 'label' => __( 'Featured Image', 'nettwebs-talent-location-management' ), 'kind' => 'featured_image', 'group' => 'core' ),
			array( 'key' => 'gallery_ids', 'label' => __( 'Gallery', 'nettwebs-talent-location-management' ), 'kind' => 'gallery', 'group' => 'core' ),
			array( 'key' => 'contact_email', 'label' => __( 'Contact Email', 'nettwebs-talent-location-management' ), 'kind' => 'meta', 'group' => 'core' ),
			array( 'key' => 'contact_phone', 'label' => __( 'Contact Phone', 'nettwebs-talent-location-management' ), 'kind' => 'meta', 'group' => 'core' ),
			array( 'key' => 'notes', 'label' => __( 'Notes', 'nettwebs-talent-location-management' ), 'kind' => 'meta', 'group' => 'core' ),
			array( 'key' => 'city', 'label' => __( 'City', 'nettwebs-talent-location-management' ), 'kind' => 'meta', 'group' => 'core' ),
		);

		if ( 'talent' === $type ) {
			$talent_fields = array(
				'age'              => __( 'Age', 'nettwebs-talent-location-management' ),
				'availability'     => __( 'Availability', 'nettwebs-talent-location-management' ),
				'languages'        => __( 'Languages', 'nettwebs-talent-location-management' ),
				'skills'           => __( 'Skills', 'nettwebs-talent-location-management' ),
				'experience'       => __( 'Experience', 'nettwebs-talent-location-management' ),
				'video_url'        => __( 'Video URL', 'nettwebs-talent-location-management' ),
				'height'           => __( 'Height', 'nettwebs-talent-location-management' ),
				'body_type'        => __( 'Body Type', 'nettwebs-talent-location-management' ),
				'hair_color'       => __( 'Hair Colour', 'nettwebs-talent-location-management' ),
				'eye_color'        => __( 'Eye Colour', 'nettwebs-talent-location-management' ),
				'measurements'     => __( 'Measurements', 'nettwebs-talent-location-management' ),
				'social_instagram' => __( 'Social Links → Instagram', 'nettwebs-talent-location-management' ),
				'social_facebook'  => __( 'Social Links → Facebook', 'nettwebs-talent-location-management' ),
				'social_tiktok'    => __( 'Social Links → TikTok', 'nettwebs-talent-location-management' ),
				'social_website'   => __( 'Social Links → Website / Portfolio', 'nettwebs-talent-location-management' ),
			);
			foreach ( $talent_fields as $key => $label ) {
				$targets[] = array( 'key' => $key, 'label' => $label, 'kind' => 'meta', 'group' => 'talent' );
			}
			$targets[] = array( 'key' => 'talent_category', 'label' => __( 'Category', 'nettwebs-talent-location-management' ), 'kind' => 'taxonomy', 'group' => 'talent' );
			$targets[] = array( 'key' => 'talent_group', 'label' => __( 'Group', 'nettwebs-talent-location-management' ), 'kind' => 'taxonomy', 'group' => 'talent' );
		} else {
			$location_fields = array(
				'parking'   => __( 'Parking', 'nettwebs-talent-location-management' ),
				'power'     => __( 'Power', 'nettwebs-talent-location-management' ),
				'amenities' => __( 'Amenities', 'nettwebs-talent-location-management' ),
				'map_embed' => __( 'Map Embed URL', 'nettwebs-talent-location-management' ),
			);
			foreach ( $location_fields as $key => $label ) {
				$targets[] = array( 'key' => $key, 'label' => $label, 'kind' => 'meta', 'group' => 'location' );
			}
			$targets[] = array( 'key' => 'location_type', 'label' => __( 'Location Type', 'nettwebs-talent-location-management' ), 'kind' => 'taxonomy', 'group' => 'location' );
		}

		foreach ( Settings::get_custom_fields( $type ) as $key => $custom ) {
			$targets[] = array(
				'key'   => $key,
				'label' => ( $custom['label'] ?? $key ) . ' (' . __( 'custom', 'nettwebs-talent-location-management' ) . ')',
				'kind'  => 'meta',
				'group' => 'custom',
			);
		}

		return $targets;
	}
}
