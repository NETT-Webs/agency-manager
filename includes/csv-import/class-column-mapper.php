<?php
namespace AgencyManager\Csv_Import;

use AgencyManager\Forms\Mapping_Targets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Suggests a CSV column → Mapping_Targets destination for each detected
 * column header, by normalized name/label match or a small alias table —
 * never auto-imports anything, only pre-fills the mapping step the user
 * reviews and can override (see Mailchimp-style requirement in the spec).
 */
class Column_Mapper {

	/**
	 * @var array<string,string[]> target key => extra normalized aliases beyond its own key/label.
	 */
	private const ALIASES = array(
		'post_title'       => array( 'name', 'fullname', 'talentname', 'locationname' ),
		'contact_email'    => array( 'email', 'emailaddress' ),
		'contact_phone'    => array( 'phone', 'mobile', 'cell', 'telephone', 'tel' ),
		'city'             => array( 'town', 'location', 'basedin' ),
		'notes'            => array( 'bio', 'about', 'description', 'summary' ),
		'social_instagram' => array( 'instagram', 'ig', 'instagramurl' ),
		'social_facebook'  => array( 'facebook', 'fb', 'facebookurl' ),
		'social_tiktok'    => array( 'tiktok', 'tiktokurl' ),
		'social_website'   => array( 'website', 'portfolio', 'url', 'websiteurl' ),
		'talent_category'  => array( 'category', 'categories' ),
		'talent_group'     => array( 'group', 'groups' ),
		'location_type'    => array( 'type', 'locationtype' ),
		'featured_image'   => array( 'image', 'photo', 'headshot', 'profilephoto', 'featuredimage' ),
		'gallery_ids'      => array( 'gallery', 'photos', 'images' ),
		'video_url'        => array( 'video', 'videourl', 'reel' ),
		'map_embed'        => array( 'map', 'mapurl', 'googlemaps' ),
	);

	/**
	 * @param string[] $columns Raw CSV header cells.
	 * @param string   $type    'talent'|'location'
	 * @return array<string,string|null> CSV column (as given) => suggested target key, or null if no confident match.
	 */
	public static function suggest( array $columns, string $type ): array {
		$targets = Mapping_Targets::get( $type );

		// Build a normalized lookup: normalized alias/key/label => target key.
		$lookup = array();
		foreach ( $targets as $target ) {
			$lookup[ self::normalize( $target['key'] ) ]   = $target['key'];
			$lookup[ self::normalize( $target['label'] ) ] = $target['key'];
		}
		foreach ( self::ALIASES as $target_key => $aliases ) {
			// Only offer an alias for a target that's actually available for this type (e.g. don't suggest talent_category for a Location import).
			if ( ! isset( array_column( $targets, null, 'key' )[ $target_key ] ) ) {
				continue;
			}
			foreach ( $aliases as $alias ) {
				$lookup[ self::normalize( $alias ) ] = $target_key;
			}
		}

		$suggestions = array();
		foreach ( $columns as $column ) {
			$normalized = self::normalize( $column );
			$suggestions[ $column ] = $lookup[ $normalized ] ?? null;
		}

		return $suggestions;
	}

	/** Lowercase, strip everything but letters/digits — "First Name", "first_name", "FIRSTNAME" all collapse to "firstname". */
	private static function normalize( string $value ): string {
		return preg_replace( '/[^a-z0-9]/', '', strtolower( $value ) ) ?? '';
	}
}
