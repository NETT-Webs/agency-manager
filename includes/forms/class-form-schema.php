<?php
namespace Nettalo\TalentLocationManagement\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalizes a stored field definition to its full shape (filling defaults,
 * generating a stable `id` if missing) and provides the built-in form
 * templates. The `id` is new as of the Form Builder — it decouples the
 * canvas/settings-panel's notion of "which field is this" from `key` (the
 * sanitized machine name used as the `_am_field_values` array key and the
 * `am_field_{key}` input name), since renaming a field's label/key must
 * never orphan already-stored submission data or an existing mapping.
 */
class Form_Schema {

	/**
	 * @param array $raw   A stored (or client-submitted) field definition, possibly partial.
	 * @param int   $index Position in the field list — used as a fallback `order`.
	 */
	public static function normalize_field( array $raw, int $index = 0 ): array {
		$type = isset( $raw['type'] ) && Field_Types::is_valid_type( (string) $raw['type'] ) ? (string) $raw['type'] : 'text';

		$defaults = array(
			'id'            => '',
			'key'           => '',
			'label'         => '',
			'type'          => $type,
			'required'      => false,
			'order'         => $index,
			'description'   => '',
			'placeholder'   => '',
			'default'       => '',
			'css_class'     => '',
			'admin_label'   => '',
			'options'       => array(),
			'min_length'    => null,
			'max_length'    => null,
			'file_types'    => '',
			'max_file_size' => null,
			'max_files'     => null,
			'conditional'   => null,
			'mapping'       => null,
		);

		$field = array_merge( $defaults, $raw );

		if ( empty( $field['id'] ) ) {
			$field['id'] = 'f_' . substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 10 );
		}

		$field['key']      = $field['key'] ? sanitize_key( $field['key'] ) : ( $field['id'] );
		$field['label']    = sanitize_text_field( (string) $field['label'] );
		$field['type']     = $type;
		$field['required'] = (bool) $field['required'];
		$field['order']    = (int) $field['order'];
		$field['options']  = self::normalize_options( (array) $field['options'] );

		$field['mapping']     = is_array( $field['mapping'] ) ? self::normalize_mapping( $field['mapping'] ) : null;
		$field['conditional'] = is_array( $field['conditional'] ) ? self::normalize_conditional( $field['conditional'] ) : null;

		return $field;
	}

	/**
	 * Client-submitted (Form Builder canvas) or stored mapping shape,
	 * reduced to exactly its 4 known keys, each validated against its own
	 * expected type/enum rather than passed through verbatim — the actual
	 * writer (Field_Mapper::write()) is already defensive via a
	 * switch/default, but the schema itself should reject/normalize
	 * unexpected shapes at the boundary, not rely solely on the consumer.
	 */
	private static function normalize_mapping( array $mapping ): ?array {
		$destination = (string) ( $mapping['destination'] ?? 'none' );
		if ( ! in_array( $destination, array( 'talent', 'location', 'both', 'none' ), true ) ) {
			$destination = 'none';
		}

		$target = (string) ( $mapping['target'] ?? 'existing' );
		if ( ! in_array( $target, array( 'existing', 'custom' ), true ) ) {
			$target = 'existing';
		}

		$target_kind = (string) ( $mapping['target_kind'] ?? 'meta' );
		if ( ! in_array( $target_kind, array( 'meta', 'post_title', 'featured_image', 'gallery', 'taxonomy' ), true ) ) {
			$target_kind = 'meta';
		}

		$target_key = sanitize_key( (string) ( $mapping['target_key'] ?? '' ) );

		if ( 'none' === $destination ) {
			return array( 'destination' => 'none', 'target' => 'existing', 'target_key' => '', 'target_kind' => 'meta' );
		}

		return array(
			'destination' => $destination,
			'target'      => $target,
			'target_key'  => $target_key,
			'target_kind' => $target_kind,
		);
	}

	/**
	 * Same treatment for the conditional-visibility shape — {field_id,
	 * operator, value}. The renderer (class-form-renderer.php) already
	 * whitelists `operator` and escapes `value` at output time; validating
	 * here too means malformed/unexpected shapes never reach storage in
	 * the first place.
	 */
	private static function normalize_conditional( array $conditional ): ?array {
		$field_id = sanitize_text_field( (string) ( $conditional['field_id'] ?? '' ) );
		if ( '' === $field_id ) {
			return null;
		}

		$operator = (string) ( $conditional['operator'] ?? 'is' );
		if ( ! in_array( $operator, array( 'is', 'is_not' ), true ) ) {
			$operator = 'is';
		}

		return array(
			'field_id' => $field_id,
			'operator' => $operator,
			'value'    => sanitize_text_field( (string) ( $conditional['value'] ?? '' ) ),
		);
	}

	/**
	 * @param array $fields Raw field list (any subset of keys per field).
	 * @return array Normalized, order-sorted field list.
	 */
	public static function normalize_fields( array $fields ): array {
		$normalized = array();
		foreach ( array_values( $fields ) as $i => $field ) {
			if ( is_array( $field ) ) {
				$normalized[] = self::normalize_field( $field, $i );
			}
		}

		usort(
			$normalized,
			static function ( $a, $b ) {
				return ( $a['order'] ?? 0 ) <=> ( $b['order'] ?? 0 );
			}
		);

		return $normalized;
	}

	/**
	 * Options are stored as [{label, value}, ...] so a field can have a
	 * dropdown label distinct from its stored value (e.g. "R5,000 - R10,000"
	 * label with a plain "range_1" value) — normalizes legacy plain-string
	 * option lists (old select fields stored `['A','B']`) into this shape.
	 */
	private static function normalize_options( array $options ): array {
		$normalized = array();

		foreach ( $options as $option ) {
			if ( is_array( $option ) ) {
				$label = sanitize_text_field( (string) ( $option['label'] ?? $option['value'] ?? '' ) );
				$value = sanitize_text_field( (string) ( $option['value'] ?? $option['label'] ?? '' ) );
			} else {
				$label = sanitize_text_field( (string) $option );
				$value = $label;
			}

			if ( '' !== $label ) {
				$normalized[] = array( 'label' => $label, 'value' => $value );
			}
		}

		return $normalized;
	}

	/**
	 * Built-in templates — "start from template" in the Forms admin screen.
	 * Field mapping defaults here are pre-filled suggestions only; every
	 * field remains fully editable (including its mapping) once a template
	 * is copied into a real form, per the "do not make templates static"
	 * requirement.
	 *
	 * @return array<string,array{label:string,type:string,description:string,fields:array}>
	 */
	public static function templates(): array {
		$templates = array(
			'talent-application'  => array(
				'label'       => __( 'Talent Application', 'nettwebs-talent-location-management' ),
				'type'        => 'talent',
				'description' => __( 'A full application for new talent to submit their details and photo.', 'nettwebs-talent-location-management' ),
				'fields'      => array(
					self::field( 'full_name', __( 'Full Name', 'nettwebs-talent-location-management' ), 'text', true, self::map_post_title() ),
					self::field( 'email', __( 'Email', 'nettwebs-talent-location-management' ), 'email', true, self::map_meta( 'talent', 'contact_email' ) ),
					self::field( 'phone', __( 'Phone', 'nettwebs-talent-location-management' ), 'tel', false, self::map_meta( 'talent', 'contact_phone' ) ),
					self::field( 'city', __( 'City', 'nettwebs-talent-location-management' ), 'text', false, self::map_meta( 'talent', 'city' ) ),
					self::field( 'gender', __( 'Gender', 'nettwebs-talent-location-management' ), 'select', false, self::map_custom( 'talent', 'gender' ), array( array( 'label' => __( 'Female', 'nettwebs-talent-location-management' ), 'value' => 'female' ), array( 'label' => __( 'Male', 'nettwebs-talent-location-management' ), 'value' => 'male' ), array( 'label' => __( 'Non-binary', 'nettwebs-talent-location-management' ), 'value' => 'non-binary' ) ) ),
					self::field( 'date_of_birth', __( 'Date of Birth', 'nettwebs-talent-location-management' ), 'date', false, self::map_custom( 'talent', 'date_of_birth' ) ),
					self::field( 'height', __( 'Height', 'nettwebs-talent-location-management' ), 'text', false, self::map_meta( 'talent', 'height' ) ),
					self::field( 'weight', __( 'Weight', 'nettwebs-talent-location-management' ), 'text', false, self::map_custom( 'talent', 'weight' ) ),
					self::field( 'experience', __( 'Experience', 'nettwebs-talent-location-management' ), 'textarea', false, self::map_meta( 'talent', 'experience' ) ),
					self::field( 'skills', __( 'Skills', 'nettwebs-talent-location-management' ), 'textarea', false, self::map_meta( 'talent', 'skills' ) ),
					self::field( 'languages', __( 'Languages', 'nettwebs-talent-location-management' ), 'textarea', false, self::map_meta( 'talent', 'languages' ) ),
					self::field( 'instagram', __( 'Instagram', 'nettwebs-talent-location-management' ), 'url', false, self::map_meta( 'talent', 'social_instagram' ) ),
					self::field( 'portfolio_url', __( 'Portfolio / Website', 'nettwebs-talent-location-management' ), 'url', false, self::map_meta( 'talent', 'social_website' ) ),
					self::field( 'photo', __( 'Photo', 'nettwebs-talent-location-management' ), 'image', true, self::map_featured_image( 'talent' ) ),
					self::field( 'message', __( 'Anything else you would like us to know?', 'nettwebs-talent-location-management' ), 'textarea', false, self::map_meta( 'talent', 'notes' ) ),
					self::field( 'consent', __( 'I consent to the processing of my information for this application.', 'nettwebs-talent-location-management' ), 'checkbox', true, self::map_none() ),
				),
			),
			'location-application' => array(
				'label'       => __( 'Location Application', 'nettwebs-talent-location-management' ),
				'type'        => 'location',
				'description' => __( 'A full application for new locations/venues to be added to your portfolio.', 'nettwebs-talent-location-management' ),
				'fields'      => array(
					self::field( 'location_name', __( 'Location Name', 'nettwebs-talent-location-management' ), 'text', true, self::map_post_title() ),
					self::field( 'location_type', __( 'Location Type', 'nettwebs-talent-location-management' ), 'text', false, self::map_taxonomy( 'location_type' ) ),
					self::field( 'address', __( 'Address', 'nettwebs-talent-location-management' ), 'text', false, self::map_custom( 'location', 'address' ) ),
					self::field( 'city', __( 'City', 'nettwebs-talent-location-management' ), 'text', false, self::map_meta( 'location', 'city' ) ),
					self::field( 'state', __( 'Province/State', 'nettwebs-talent-location-management' ), 'text', false, self::map_custom( 'location', 'province' ) ),
					self::field( 'country', __( 'Country', 'nettwebs-talent-location-management' ), 'text', false, self::map_custom( 'location', 'country' ) ),
					self::field( 'capacity', __( 'Capacity', 'nettwebs-talent-location-management' ), 'number', false, self::map_custom( 'location', 'capacity' ) ),
					self::field( 'description', __( 'Description', 'nettwebs-talent-location-management' ), 'textarea', false, self::map_meta( 'location', 'notes' ) ),
					self::field( 'website', __( 'Website', 'nettwebs-talent-location-management' ), 'url', false, self::map_custom( 'location', 'website' ) ),
					self::field( 'contact_name', __( 'Contact Name', 'nettwebs-talent-location-management' ), 'text', false, self::map_custom( 'location', 'contact_name' ) ),
					self::field( 'contact_email', __( 'Contact Email', 'nettwebs-talent-location-management' ), 'email', true, self::map_meta( 'location', 'contact_email' ) ),
					self::field( 'contact_phone', __( 'Contact Phone', 'nettwebs-talent-location-management' ), 'tel', false, self::map_meta( 'location', 'contact_phone' ) ),
					self::field( 'photo', __( 'Main Photo', 'nettwebs-talent-location-management' ), 'image', false, self::map_featured_image( 'location' ) ),
					self::field( 'gallery', __( 'Gallery', 'nettwebs-talent-location-management' ), 'gallery', false, self::map_gallery( 'location' ) ),
					self::field( 'consent', __( 'I consent to the processing of my information for this application.', 'nettwebs-talent-location-management' ), 'checkbox', true, self::map_none() ),
				),
			),
			'general-contact'      => array(
				'label'       => __( 'General Contact', 'nettwebs-talent-location-management' ),
				'type'        => 'general',
				'description' => __( 'A simple contact form — nothing is mapped to Talent/Location.', 'nettwebs-talent-location-management' ),
				'fields'      => array(
					self::field( 'full_name', __( 'Full Name', 'nettwebs-talent-location-management' ), 'text', true, self::map_none() ),
					self::field( 'email', __( 'Email', 'nettwebs-talent-location-management' ), 'email', true, self::map_none() ),
					self::field( 'phone', __( 'Phone', 'nettwebs-talent-location-management' ), 'tel', false, self::map_none() ),
					self::field( 'subject', __( 'Subject', 'nettwebs-talent-location-management' ), 'text', false, self::map_none() ),
					self::field( 'message', __( 'Message', 'nettwebs-talent-location-management' ), 'textarea', true, self::map_none() ),
				),
			),
			'talent-update'         => array(
				'label'       => __( 'Talent Update', 'nettwebs-talent-location-management' ),
				'type'        => 'talent',
				'description' => __( 'A shorter form for existing talent to update their details.', 'nettwebs-talent-location-management' ),
				'fields'      => array(
					self::field( 'full_name', __( 'Full Name', 'nettwebs-talent-location-management' ), 'text', true, self::map_post_title() ),
					self::field( 'email', __( 'Email', 'nettwebs-talent-location-management' ), 'email', true, self::map_meta( 'talent', 'contact_email' ) ),
					self::field( 'phone', __( 'Phone', 'nettwebs-talent-location-management' ), 'tel', false, self::map_meta( 'talent', 'contact_phone' ) ),
					self::field( 'experience', __( 'Updated Experience', 'nettwebs-talent-location-management' ), 'textarea', false, self::map_meta( 'talent', 'experience' ) ),
					self::field( 'photo', __( 'Updated Photo', 'nettwebs-talent-location-management' ), 'image', false, self::map_featured_image( 'talent' ) ),
					self::field( 'message', __( 'What would you like to update?', 'nettwebs-talent-location-management' ), 'textarea', false, self::map_meta( 'talent', 'notes' ) ),
				),
			),
			'location-submission'  => array(
				'label'       => __( 'Location Submission', 'nettwebs-talent-location-management' ),
				'type'        => 'location',
				'description' => __( 'The original short location submission form.', 'nettwebs-talent-location-management' ),
				'fields'      => array(
					self::field( 'location_name', __( 'Location Name', 'nettwebs-talent-location-management' ), 'text', true, self::map_post_title() ),
					self::field( 'contact_email', __( 'Contact Email', 'nettwebs-talent-location-management' ), 'email', true, self::map_meta( 'location', 'contact_email' ) ),
					self::field( 'contact_phone', __( 'Contact Phone', 'nettwebs-talent-location-management' ), 'tel', false, self::map_meta( 'location', 'contact_phone' ) ),
					self::field( 'city', __( 'City', 'nettwebs-talent-location-management' ), 'text', false, self::map_meta( 'location', 'city' ) ),
					self::field( 'description', __( 'Description', 'nettwebs-talent-location-management' ), 'textarea', false, self::map_meta( 'location', 'notes' ) ),
					self::field( 'photo', __( 'Photo', 'nettwebs-talent-location-management' ), 'image', false, self::map_featured_image( 'location' ) ),
				),
			),
		);

		/**
		 * Filters the built-in Form Builder templates.
		 *
		 * @param array $templates
		 */
		$templates = apply_filters( 'nettalo_form_templates', $templates );

		// Legacy alias — kept working for any integration still hooking the
		// pre-1.7.0 filter name; see docs/REBRAND.md.
		return apply_filters_deprecated( 'am_form_templates', array( $templates ), '1.7.0', 'nettalo_form_templates' );
	}

	private static function field( string $key, string $label, string $type, bool $required, ?array $mapping, array $options = array() ): array {
		return array(
			'key'      => $key,
			'label'    => $label,
			'type'     => $type,
			'required' => $required,
			'mapping'  => $mapping,
			'options'  => $options,
		);
	}

	private static function map_meta( string $destination, string $target_key ): array {
		return array( 'destination' => $destination, 'target' => 'existing', 'target_key' => $target_key, 'target_kind' => 'meta' );
	}

	private static function map_custom( string $destination, string $target_key ): array {
		return array( 'destination' => $destination, 'target' => 'custom', 'target_key' => $target_key, 'target_kind' => 'meta' );
	}

	private static function map_taxonomy( string $taxonomy ): array {
		return array( 'destination' => 'location', 'target' => 'existing', 'target_key' => $taxonomy, 'target_kind' => 'taxonomy' );
	}

	private static function map_gallery( string $destination ): array {
		return array( 'destination' => $destination, 'target' => 'existing', 'target_key' => 'gallery_ids', 'target_kind' => 'gallery' );
	}

	private static function map_featured_image( string $destination ): array {
		return array( 'destination' => $destination, 'target' => 'existing', 'target_key' => 'featured_image', 'target_kind' => 'featured_image' );
	}

	private static function map_post_title(): array {
		return array( 'destination' => 'both', 'target' => 'existing', 'target_key' => 'post_title', 'target_kind' => 'post_title' );
	}

	private static function map_none(): array {
		return array( 'destination' => 'none', 'target' => 'existing', 'target_key' => '', 'target_kind' => 'meta' );
	}
}
