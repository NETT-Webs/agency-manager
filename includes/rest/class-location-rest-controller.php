<?php
namespace AgencyManager\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRUD/preview/options for the Location React editor — field list mirrors
 * Cpt\Meta_Boxes::save_location() exactly, so the new UI and the classic
 * meta boxes stay compatible with the same records.
 */
class Location_Rest_Controller extends Profile_Rest_Controller {

	protected function post_type(): string {
		return 'location';
	}

	protected function route_base(): string {
		return 'locations';
	}

	protected function meta_fields(): array {
		return array(
			// See the identical note in Talent_Rest_Controller::meta_fields().
			'contact_email' => array( 'type' => 'text' ),
			'contact_phone' => array( 'type' => 'text' ),
			'notes'         => array( 'type' => 'textarea' ),
			'city'         => array( 'type' => 'text' ),
			'parking'      => array( 'type' => 'select', 'options' => array( 'available' => 'Available', 'limited' => 'Limited', 'none' => 'Not Available' ) ),
			'power'        => array( 'type' => 'select', 'options' => array( 'mains' => 'Mains Power', 'generator' => 'Generator Required', 'limited' => 'Limited' ) ),
			'amenities'    => array( 'type' => 'textarea' ),
			'availability' => array( 'type' => 'select', 'options' => array( 'available' => 'Available', 'booked' => 'Booked', 'seasonal' => 'Seasonal' ) ),
			'map_embed'    => array( 'type' => 'url' ),
		);
	}

	protected function taxonomies(): array {
		return array( 'location_type' );
	}
}
