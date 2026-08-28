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
			array( 'key' => 'post_title', 'label' => __( 'Name', 'agency-manager' ), 'kind' => 'post_title', 'group' => 'core' ),
			array( 'key' => 'featured_image', 'label' => __( 'Featured Image', 'agency-manager' ), 'kind' => 'featured_image', 'group' => 'core' ),
			array( 'key' => 'gallery_ids', 'label' => __( 'Gallery', 'agency-manager' ), 'kind' => 'gallery', 'group' => 'core' ),
			array( 'key' => 'contact_email', 'label' => __( 'Contact Email', 'agency-manager' ), 'kind' => 'meta', 'group' => 'core' ),
			array( 'key' => 'contact_phone', 'label' => __( 'Contact Phone', 'agency-manager' ), 'kind' => 'meta', 'group' => 'core' ),
			array( 'key' => 'notes', 'label' => __( 'Notes', 'agency-manager' ), 'kind' => 'meta', 'group' => 'core' ),
			array( 'key' => 'city', 'label' => __( 'City', 'agency-manager' ), 'kind' => 'meta', 'group' => 'core' ),
		);

		if ( 'talent' === $type ) {
			$talent_fields = array(
				'age'              => __( 'Age', 'agency-manager' ),
				'availability'     => __( 'Availability', 'agency-manager' ),
				'languages'        => __( 'Languages', 'agency-manager' ),
				'skills'           => __( 'Skills', 'agency-manager' ),
				'experience'       => __( 'Experience', 'agency-manager' ),
				'video_url'        => __( 'Video URL', 'agency-manager' ),
				'height'           => __( 'Height', 'agency-manager' ),
				'body_type'        => __( 'Body Type', 'agency-manager' ),
				'hair_color'       => __( 'Hair Colour', 'agency-manager' ),
				'eye_color'        => __( 'Eye Colour', 'agency-manager' ),
				'measurements'     => __( 'Measurements', 'agency-manager' ),
				'social_instagram' => __( 'Social Links → Instagram', 'agency-manager' ),
				'social_facebook'  => __( 'Social Links → Facebook', 'agency-manager' ),
				'social_tiktok'    => __( 'Social Links → TikTok', 'agency-manager' ),
				'social_website'   => __( 'Social Links → Website / Portfolio', 'agency-manager' ),
			);
			foreach ( $talent_fields as $key => $label ) {
				$targets[] = array( 'key' => $key, 'label' => $label, 'kind' => 'meta', 'group' => 'talent' );
			}
			$targets[] = array( 'key' => 'talent_category', 'label' => __( 'Category', 'agency-manager' ), 'kind' => 'taxonomy', 'group' => 'talent' );
			$targets[] = array( 'key' => 'talent_group', 'label' => __( 'Group', 'agency-manager' ), 'kind' => 'taxonomy', 'group' => 'talent' );
		} else {
			$location_fields = array(
				'parking'   => __( 'Parking', 'agency-manager' ),
				'power'     => __( 'Power', 'agency-manager' ),
				'amenities' => __( 'Amenities', 'agency-manager' ),
				'map_embed' => __( 'Map Embed URL', 'agency-manager' ),
			);
			foreach ( $location_fields as $key => $label ) {
				$targets[] = array( 'key' => $key, 'label' => $label, 'kind' => 'meta', 'group' => 'location' );
			}
			$targets[] = array( 'key' => 'location_type', 'label' => __( 'Location Type', 'agency-manager' ), 'kind' => 'taxonomy', 'group' => 'location' );
		}

		foreach ( Settings::get_custom_fields( $type ) as $key => $custom ) {
			$targets[] = array(
				'key'   => $key,
				'label' => ( $custom['label'] ?? $key ) . ' (' . __( 'custom', 'agency-manager' ) . ')',
				'kind'  => 'meta',
				'group' => 'custom',
			);
		}

		return $targets;
	}
}
