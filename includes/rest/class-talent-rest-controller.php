<?php
namespace AgencyManager\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRUD/preview/options for the Talent React editor — field list mirrors
 * Cpt\Meta_Boxes::save_talent() exactly, so the new UI and the classic meta
 * boxes stay compatible with the same records.
 */
class Talent_Rest_Controller extends Profile_Rest_Controller {

	protected function post_type(): string {
		return 'talent';
	}

	protected function route_base(): string {
		return 'talent';
	}

	protected function meta_fields(): array {
		return array(
			// "Core" fields shared with Forms\Mapping_Targets (the Form
			// Builder's own mapping destinations) — not yet surfaced as
			// their own inputs in the Talent editor UI, but readable/
			// writable through this REST layer so the CSV importer (and
			// anything else using insert_from_payload()/apply_payload())
			// can populate exactly what a Talent Application publish
			// already writes to, via the same code path.
			'contact_email'    => array( 'type' => 'text' ),
			'contact_phone'    => array( 'type' => 'text' ),
			'notes'            => array( 'type' => 'textarea' ),
			'city'             => array( 'type' => 'text' ),
			'age'              => array( 'type' => 'text' ),
			'availability'     => array( 'type' => 'select', 'options' => array( 'available' => 'Available', 'limited' => 'Limited', 'booked' => 'Booked' ) ),
			'languages'        => array( 'type' => 'textarea' ),
			'skills'           => array( 'type' => 'textarea' ),
			'experience'       => array( 'type' => 'textarea' ),
			'video_url'        => array( 'type' => 'url' ),
			'height'           => array( 'type' => 'text' ),
			'body_type'        => array( 'type' => 'select', 'options' => array( 'straight-size' => 'Straight Size', 'plus-size' => 'Plus Size', 'athletic' => 'Athletic', 'petite' => 'Petite', 'tall' => 'Tall' ) ),
			'hair_color'       => array( 'type' => 'text' ),
			'eye_color'        => array( 'type' => 'text' ),
			'measurements'     => array( 'type' => 'textarea' ),
			'social_instagram' => array( 'type' => 'url' ),
			'social_facebook'  => array( 'type' => 'url' ),
			'social_tiktok'    => array( 'type' => 'url' ),
			'social_website'   => array( 'type' => 'url' ),
		);
	}

	protected function taxonomies(): array {
		return array( 'talent_category', 'talent_group' );
	}
}
