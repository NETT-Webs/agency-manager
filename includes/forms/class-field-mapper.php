<?php
namespace AgencyManager\Forms;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies a form's per-field mapping configuration to a Talent/Location
 * post — the configurable replacement for Workflow's old hardcoded 5-entry
 * `$field_to_meta` array. Every field with a `mapping` whose `destination`
 * matches the target post type (or `both`, for fields like the name that
 * always apply) is written; fields mapped to `none` ("Application Only")
 * are never touched. This is the single chokepoint submission data passes
 * through on its way into a real Talent/Location record.
 */
class Field_Mapper {

	/**
	 * Backward compatibility for forms created before the Form Builder
	 * existed (the original two shipped forms, or any custom form someone
	 * already built by hand): if NONE of a form's fields carry a `mapping`
	 * yet, this fills in the exact mappings Workflow's old hardcoded
	 * `$field_to_meta` table used to apply, keyed by the well-known field
	 * keys those original forms shipped with. A form that already has any
	 * mapping configured (built or edited via the new builder) is left
	 * completely untouched — this only fires once, for genuinely
	 * un-migrated data.
	 *
	 * @return array{fields:array,changed:bool}
	 */
	public static function backfill_legacy_defaults( array $fields ): array {
		foreach ( $fields as $field ) {
			if ( ! empty( $field['mapping'] ) ) {
				return array( 'fields' => $fields, 'changed' => false );
			}
		}

		$legacy = array(
			'full_name'     => array( 'destination' => 'both', 'target' => 'existing', 'target_key' => 'post_title', 'target_kind' => 'post_title' ),
			'location_name' => array( 'destination' => 'both', 'target' => 'existing', 'target_key' => 'post_title', 'target_kind' => 'post_title' ),
			'email'         => array( 'destination' => 'both', 'target' => 'existing', 'target_key' => 'contact_email', 'target_kind' => 'meta' ),
			'phone'         => array( 'destination' => 'both', 'target' => 'existing', 'target_key' => 'contact_phone', 'target_kind' => 'meta' ),
			'city'          => array( 'destination' => 'both', 'target' => 'existing', 'target_key' => 'city', 'target_kind' => 'meta' ),
			'message'       => array( 'destination' => 'both', 'target' => 'existing', 'target_key' => 'notes', 'target_kind' => 'meta' ),
			'description'   => array( 'destination' => 'both', 'target' => 'existing', 'target_key' => 'notes', 'target_kind' => 'meta' ),
			'photo'         => array( 'destination' => 'both', 'target' => 'existing', 'target_key' => 'featured_image', 'target_kind' => 'featured_image' ),
		);

		$changed = false;
		foreach ( $fields as &$field ) {
			if ( isset( $legacy[ $field['key'] ] ) ) {
				$field['mapping'] = $legacy[ $field['key'] ];
				$changed          = true;
			}
		}
		unset( $field );

		return array( 'fields' => $fields, 'changed' => $changed );
	}

	/**
	 * @param string $post_type 'talent'|'location'
	 * @param array  $field_defs Normalized field definitions (see Form_Schema::normalize_fields()).
	 * @param array  $values     Submitted values, keyed by field `key`.
	 */
	public static function apply( int $post_id, string $post_type, array $field_defs, array $values ): void {
		foreach ( self::resolve( $post_type, $field_defs, $values ) as $resolution ) {
			self::write( $post_id, $resolution );
		}
	}

	/**
	 * Dry-run: resolves what apply() would write, without writing anything —
	 * used by the Applications review screen to show "Mapped Talent/Location
	 * Data" before an admin approves/publishes.
	 *
	 * @return array<int,array{label:string,value:mixed}> Human-readable target label => resolved value.
	 */
	public static function preview( string $post_type, array $field_defs, array $values ): array {
		$out = array();

		foreach ( self::resolve( $post_type, $field_defs, $values ) as $resolution ) {
			$out[] = array(
				'label' => $resolution['field']['label'],
				'target' => self::describe_target( $resolution ),
				'value' => $resolution['value'],
			);
		}

		return $out;
	}

	/**
	 * @return array<int,array{field:array,mapping:array,value:mixed}>
	 */
	private static function resolve( string $post_type, array $field_defs, array $values ): array {
		$resolved = array();

		foreach ( $field_defs as $field ) {
			$mapping = $field['mapping'] ?? null;
			if ( ! is_array( $mapping ) || empty( $mapping['destination'] ) || 'none' === $mapping['destination'] ) {
				continue;
			}

			if ( 'both' !== $mapping['destination'] && $mapping['destination'] !== $post_type ) {
				continue;
			}

			if ( ! array_key_exists( $field['key'], $values ) ) {
				continue;
			}

			$value = $values[ $field['key'] ];
			if ( '' === $value || null === $value || array() === $value ) {
				continue;
			}

			$resolved[] = array(
				'field'   => $field,
				'mapping' => $mapping,
				'value'   => $value,
			);
		}

		return $resolved;
	}

	/**
	 * @param array{field:array,mapping:array,value:mixed} $resolution
	 */
	private static function write( int $post_id, array $resolution ): void {
		$mapping = $resolution['mapping'];
		$value   = $resolution['value'];
		$kind    = $mapping['target_kind'] ?? 'meta';

		switch ( $kind ) {
			case 'post_title':
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => sanitize_text_field( is_array( $value ) ? reset( $value ) : (string) $value ),
					)
				);
				break;

			case 'featured_image':
				$attachment_id = is_array( $value ) ? (int) reset( $value ) : (int) $value;
				if ( $attachment_id ) {
					set_post_thumbnail( $post_id, $attachment_id );
				}
				break;

			case 'gallery':
				self::append_gallery( $post_id, $value );
				break;

			case 'taxonomy':
				self::assign_taxonomy( $post_id, (string) $mapping['target_key'], $value );
				break;

			case 'meta':
			default:
				$meta_key = self::meta_key( $mapping );
				if ( $meta_key ) {
					update_post_meta( $post_id, $meta_key, self::flatten_meta_value( $value ) );
					if ( 'custom' === ( $mapping['target'] ?? '' ) ) {
						self::ensure_custom_field_registered( $resolution );
					}
				}
				break;
		}
	}

	private static function meta_key( array $mapping ): string {
		$target_key = sanitize_key( (string) ( $mapping['target_key'] ?? '' ) );
		if ( '' === $target_key ) {
			return '';
		}

		return 'custom' === ( $mapping['target'] ?? '' ) ? "_am_custom_{$target_key}" : "_am_{$target_key}";
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	private static function flatten_meta_value( $value ): string {
		if ( is_array( $value ) ) {
			return implode( ', ', array_map( 'sanitize_text_field', array_map( 'strval', $value ) ) );
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * @param mixed $value One attachment ID, or an array of them.
	 */
	private static function append_gallery( int $post_id, $value ): void {
		$new_ids = array_filter( array_map( 'absint', is_array( $value ) ? $value : array( $value ) ) );
		if ( ! $new_ids ) {
			return;
		}

		$existing = get_post_meta( $post_id, '_am_gallery_ids', true );
		$existing_ids = $existing ? array_filter( array_map( 'absint', explode( ',', (string) $existing ) ) ) : array();

		$merged = array_values( array_unique( array_merge( $existing_ids, $new_ids ) ) );

		update_post_meta( $post_id, '_am_gallery_ids', implode( ',', $merged ) );
	}

	/**
	 * @param mixed $value A term name/slug, or an array of them (e.g. a checkbox-group field).
	 */
	private static function assign_taxonomy( int $post_id, string $taxonomy, $value ): void {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$terms = array_filter( array_map( 'sanitize_text_field', is_array( $value ) ? $value : array( $value ) ) );
		if ( ! $terms ) {
			return;
		}

		wp_set_object_terms( $post_id, array_values( $terms ), $taxonomy, false );
	}

	/**
	 * Keeps Settings::custom_fields() in sync so a custom field created via
	 * one form's mapping UI is immediately offered as an "Existing Field"
	 * option when mapping another form, instead of being recreated (and
	 * possibly mistyped) every time.
	 */
	private static function ensure_custom_field_registered( array $resolution ): void {
		$mapping    = $resolution['mapping'];
		$destination = 'both' === $mapping['destination'] ? 'talent' : $mapping['destination'];
		if ( ! in_array( $destination, array( 'talent', 'location' ), true ) ) {
			return;
		}

		Settings::register_custom_field( $destination, sanitize_key( (string) $mapping['target_key'] ), $resolution['field']['label'] ?? '', $resolution['field']['type'] ?? 'text' );
	}

	private static function describe_target( array $resolution ): string {
		$mapping = $resolution['mapping'];
		$kind    = $mapping['target_kind'] ?? 'meta';
		$dest    = 'both' === $mapping['destination'] ? __( 'Talent/Location', 'agency-manager' ) : ucfirst( $mapping['destination'] );

		switch ( $kind ) {
			case 'post_title':
				return sprintf( '%s → %s', $dest, __( 'Name', 'agency-manager' ) );
			case 'featured_image':
				return sprintf( '%s → %s', $dest, __( 'Featured Image', 'agency-manager' ) );
			case 'gallery':
				return sprintf( '%s → %s', $dest, __( 'Gallery', 'agency-manager' ) );
			case 'taxonomy':
				return sprintf( '%s → %s (%s)', $dest, __( 'Category', 'agency-manager' ), $mapping['target_key'] );
			default:
				$label = 'custom' === ( $mapping['target'] ?? '' )
					? sprintf( '%s (%s)', $mapping['target_key'], __( 'custom field', 'agency-manager' ) )
					: $mapping['target_key'];
				return sprintf( '%s → %s', $dest, $label );
		}
	}
}
