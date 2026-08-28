<?php
namespace AgencyManager\Forms;

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

		if ( ! is_array( $field['mapping'] ) ) {
			$field['mapping'] = null;
		}
		if ( ! is_array( $field['conditional'] ) ) {
			$field['conditional'] = null;
		}

		return $field;
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
				'label'       => __( 'Talent Application', 'agency-manager' ),
				'type'        => 'talent',
				'description' => __( 'A full application for new talent to submit their details and photo.', 'agency-manager' ),
				'fields'      => array(
					self::field( 'full_name', __( 'Full Name', 'agency-manager' ), 'text', true, self::map_post_title() ),
					self::field( 'email', __( 'Email', 'agency-manager' ), 'email', true, self::map_meta( 'talent', 'contact_email' ) ),
					self::field( 'phone', __( 'Phone', 'agency-manager' ), 'tel', false, self::map_meta( 'talent', 'contact_phone' ) ),
					self::field( 'city', __( 'City', 'agency-manager' ), 'text', false, self::map_meta( 'talent', 'city' ) ),
					self::field( 'gender', __( 'Gender', 'agency-manager' ), 'select', false, self::map_custom( 'talent', 'gender' ), array( array( 'label' => __( 'Female', 'agency-manager' ), 'value' => 'female' ), array( 'label' => __( 'Male', 'agency-manager' ), 'value' => 'male' ), array( 'label' => __( 'Non-binary', 'agency-manager' ), 'value' => 'non-binary' ) ) ),
					self::field( 'date_of_birth', __( 'Date of Birth', 'agency-manager' ), 'date', false, self::map_custom( 'talent', 'date_of_birth' ) ),
					self::field( 'height', __( 'Height', 'agency-manager' ), 'text', false, self::map_meta( 'talent', 'height' ) ),
					self::field( 'weight', __( 'Weight', 'agency-manager' ), 'text', false, self::map_custom( 'talent', 'weight' ) ),
					self::field( 'experience', __( 'Experience', 'agency-manager' ), 'textarea', false, self::map_meta( 'talent', 'experience' ) ),
					self::field( 'skills', __( 'Skills', 'agency-manager' ), 'textarea', false, self::map_meta( 'talent', 'skills' ) ),
					self::field( 'languages', __( 'Languages', 'agency-manager' ), 'textarea', false, self::map_meta( 'talent', 'languages' ) ),
					self::field( 'instagram', __( 'Instagram', 'agency-manager' ), 'url', false, self::map_meta( 'talent', 'social_instagram' ) ),
					self::field( 'portfolio_url', __( 'Portfolio / Website', 'agency-manager' ), 'url', false, self::map_meta( 'talent', 'social_website' ) ),
					self::field( 'photo', __( 'Photo', 'agency-manager' ), 'image', true, self::map_featured_image( 'talent' ) ),
					self::field( 'message', __( 'Anything else you would like us to know?', 'agency-manager' ), 'textarea', false, self::map_meta( 'talent', 'notes' ) ),
					self::field( 'consent', __( 'I consent to the processing of my information for this application.', 'agency-manager' ), 'checkbox', true, self::map_none() ),
				),
			),
			'location-application' => array(
				'label'       => __( 'Location Application', 'agency-manager' ),
				'type'        => 'location',
				'description' => __( 'A full application for new locations/venues to be added to your portfolio.', 'agency-manager' ),
				'fields'      => array(
					self::field( 'location_name', __( 'Location Name', 'agency-manager' ), 'text', true, self::map_post_title() ),
					self::field( 'location_type', __( 'Location Type', 'agency-manager' ), 'text', false, self::map_taxonomy( 'location_type' ) ),
					self::field( 'address', __( 'Address', 'agency-manager' ), 'text', false, self::map_custom( 'location', 'address' ) ),
					self::field( 'city', __( 'City', 'agency-manager' ), 'text', false, self::map_meta( 'location', 'city' ) ),
					self::field( 'state', __( 'Province/State', 'agency-manager' ), 'text', false, self::map_custom( 'location', 'province' ) ),
					self::field( 'country', __( 'Country', 'agency-manager' ), 'text', false, self::map_custom( 'location', 'country' ) ),
					self::field( 'capacity', __( 'Capacity', 'agency-manager' ), 'number', false, self::map_custom( 'location', 'capacity' ) ),
					self::field( 'description', __( 'Description', 'agency-manager' ), 'textarea', false, self::map_meta( 'location', 'notes' ) ),
					self::field( 'website', __( 'Website', 'agency-manager' ), 'url', false, self::map_custom( 'location', 'website' ) ),
					self::field( 'contact_name', __( 'Contact Name', 'agency-manager' ), 'text', false, self::map_custom( 'location', 'contact_name' ) ),
					self::field( 'contact_email', __( 'Contact Email', 'agency-manager' ), 'email', true, self::map_meta( 'location', 'contact_email' ) ),
					self::field( 'contact_phone', __( 'Contact Phone', 'agency-manager' ), 'tel', false, self::map_meta( 'location', 'contact_phone' ) ),
					self::field( 'photo', __( 'Main Photo', 'agency-manager' ), 'image', false, self::map_featured_image( 'location' ) ),
					self::field( 'gallery', __( 'Gallery', 'agency-manager' ), 'gallery', false, self::map_gallery( 'location' ) ),
					self::field( 'consent', __( 'I consent to the processing of my information for this application.', 'agency-manager' ), 'checkbox', true, self::map_none() ),
				),
			),
			'general-contact'      => array(
				'label'       => __( 'General Contact', 'agency-manager' ),
				'type'        => 'general',
				'description' => __( 'A simple contact form — nothing is mapped to Talent/Location.', 'agency-manager' ),
				'fields'      => array(
					self::field( 'full_name', __( 'Full Name', 'agency-manager' ), 'text', true, self::map_none() ),
					self::field( 'email', __( 'Email', 'agency-manager' ), 'email', true, self::map_none() ),
					self::field( 'phone', __( 'Phone', 'agency-manager' ), 'tel', false, self::map_none() ),
					self::field( 'subject', __( 'Subject', 'agency-manager' ), 'text', false, self::map_none() ),
					self::field( 'message', __( 'Message', 'agency-manager' ), 'textarea', true, self::map_none() ),
				),
			),
			'talent-update'         => array(
				'label'       => __( 'Talent Update', 'agency-manager' ),
				'type'        => 'talent',
				'description' => __( 'A shorter form for existing talent to update their details.', 'agency-manager' ),
				'fields'      => array(
					self::field( 'full_name', __( 'Full Name', 'agency-manager' ), 'text', true, self::map_post_title() ),
					self::field( 'email', __( 'Email', 'agency-manager' ), 'email', true, self::map_meta( 'talent', 'contact_email' ) ),
					self::field( 'phone', __( 'Phone', 'agency-manager' ), 'tel', false, self::map_meta( 'talent', 'contact_phone' ) ),
					self::field( 'experience', __( 'Updated Experience', 'agency-manager' ), 'textarea', false, self::map_meta( 'talent', 'experience' ) ),
					self::field( 'photo', __( 'Updated Photo', 'agency-manager' ), 'image', false, self::map_featured_image( 'talent' ) ),
					self::field( 'message', __( 'What would you like to update?', 'agency-manager' ), 'textarea', false, self::map_meta( 'talent', 'notes' ) ),
				),
			),
			'location-submission'  => array(
				'label'       => __( 'Location Submission', 'agency-manager' ),
				'type'        => 'location',
				'description' => __( 'The original short location submission form.', 'agency-manager' ),
				'fields'      => array(
					self::field( 'location_name', __( 'Location Name', 'agency-manager' ), 'text', true, self::map_post_title() ),
					self::field( 'contact_email', __( 'Contact Email', 'agency-manager' ), 'email', true, self::map_meta( 'location', 'contact_email' ) ),
					self::field( 'contact_phone', __( 'Contact Phone', 'agency-manager' ), 'tel', false, self::map_meta( 'location', 'contact_phone' ) ),
					self::field( 'city', __( 'City', 'agency-manager' ), 'text', false, self::map_meta( 'location', 'city' ) ),
					self::field( 'description', __( 'Description', 'agency-manager' ), 'textarea', false, self::map_meta( 'location', 'notes' ) ),
					self::field( 'photo', __( 'Photo', 'agency-manager' ), 'image', false, self::map_featured_image( 'location' ) ),
				),
			),
		);

		/**
		 * Filters the built-in Form Builder templates.
		 *
		 * @param array $templates
		 */
		return apply_filters( 'am_form_templates', $templates );
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
