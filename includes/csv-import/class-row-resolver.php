<?php
namespace AgencyManager\Csv_Import;

use AgencyManager\Forms\Mapping_Targets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves one raw CSV row (csv column => raw string) against the current
 * column mapping into a validated, typed set of Talent/Location field
 * values — the exact same resolution the Preview step and the actual
 * Import step both run, so what the user previewed is what gets written.
 * Never writes anything itself.
 */
class Row_Resolver {

	/** Extra destinations only CSV import offers (not Form Builder mapping targets, but real existing backend fields — see the class doc on why). */
	public static function extra_targets(): array {
		return array(
			array( 'key' => 'description', 'label' => __( 'Description', 'agency-manager' ), 'kind' => 'post_content', 'group' => 'core' ),
			array( 'key' => 'featured', 'label' => __( 'Featured (yes/no)', 'agency-manager' ), 'kind' => 'flag', 'group' => 'core' ),
			array( 'key' => 'active', 'label' => __( 'Active (yes/no)', 'agency-manager' ), 'kind' => 'flag', 'group' => 'core' ),
		);
	}

	/** @return array<int,array{key:string,label:string,kind:string,group:string}> Every field a CSV column can be mapped to, "Don't import" excluded (the client adds that option itself). */
	public static function available_targets( string $type ): array {
		return array_merge( Mapping_Targets::get( $type ), self::extra_targets() );
	}

	/**
	 * @param array<string,string> $row          csv column => raw value, from Csv_Parser::read_rows().
	 * @param array<string,string> $column_map   csv column => target key ('' / null = Don't import).
	 * @param string                $type        'talent'|'location'
	 * @return array{payload:array,warnings:string[],errors:string[],matchValue:string}
	 */
	public static function resolve( array $row, array $column_map, string $type ): array {
		$targets_by_key = array();
		foreach ( self::available_targets( $type ) as $t ) {
			$targets_by_key[ $t['key'] ] = $t;
		}

		$payload  = array( 'meta' => array(), 'customFields' => array(), 'terms' => array() );
		$warnings = array();
		$errors   = array();

		foreach ( $column_map as $csv_column => $target_key ) {
			if ( ! $target_key || ! isset( $targets_by_key[ $target_key ] ) ) {
				continue;
			}
			$raw    = trim( (string) ( $row[ $csv_column ] ?? '' ) );
			$target = $targets_by_key[ $target_key ];

			if ( '' === $raw ) {
				continue; // Blank cell: field simply isn't set from this row — never treated as "clear this value" here (see Csv_Importer's own clearBlanks handling for update mode).
			}

			switch ( $target['kind'] ) {
				case 'post_title':
					$payload['title'] = sanitize_text_field( $raw );
					break;

				case 'post_content':
					$payload['description'] = wp_kses_post( $raw );
					break;

				case 'featured_image':
					$payload['_featuredImageUrl'] = $raw;
					break;

				case 'gallery':
					$payload['_galleryUrls'] = array_values( array_filter( array_map( 'trim', preg_split( '/[|,]/', $raw ) ) ) );
					break;

				case 'flag':
					$payload[ $target_key ] = in_array( strtolower( $raw ), array( '1', 'yes', 'y', 'true' ), true );
					break;

				case 'taxonomy':
					$taxonomy = 'talent_category' === $target_key ? 'talent_category' : ( 'talent_group' === $target_key ? 'talent_group' : 'location_type' );
					$payload['terms'][ $taxonomy ] = array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );
					break;

				case 'meta':
				default:
					if ( 'custom' === $target['group'] ) {
						$payload['customFields'][ $target_key ] = sanitize_textarea_field( $raw );
					} else {
						if ( 'contact_email' === $target_key && ! is_email( $raw ) ) {
							$errors[] = sprintf( /* translators: %s: the invalid value */ __( 'Invalid email: "%s"', 'agency-manager' ), $raw );
							break;
						}
						if ( in_array( $target_key, array( 'video_url', 'map_embed', 'social_instagram', 'social_facebook', 'social_tiktok', 'social_website' ), true ) && ! filter_var( $raw, FILTER_VALIDATE_URL ) ) {
							$warnings[] = sprintf( /* translators: 1: field label, 2: the value */ __( '%1$s doesn\'t look like a valid URL: "%2$s"', 'agency-manager' ), $target['label'], $raw );
						}
						$payload['meta'][ $target_key ] = sanitize_text_field( $raw );
					}
					break;
			}
		}

		if ( empty( $payload['title'] ) ) {
			$errors[] = __( 'Name is missing.', 'agency-manager' );
		}

		return array(
			'payload'  => $payload,
			'warnings' => $warnings,
			'errors'   => $errors,
		);
	}
}
