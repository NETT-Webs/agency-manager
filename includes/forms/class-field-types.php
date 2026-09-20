<?php
namespace AgencyManager\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Single source of truth for field types (rendering/sanitization/settings-
 * panel behaviour) and the Field Library (the palette of draggable items in
 * the Form Builder — many library items share one underlying TYPE with
 * different preset defaults, the same way "First Name" and "Last Name" are
 * both just a text field with a different suggested key/label). Previously
 * duplicated as two separate hardcoded `FIELD_TYPES` consts in
 * Form_Renderer and Forms_Page; both now read from here.
 */
class Field_Types {

	/**
	 * Canonical renderable/sanitizable types. Keys used throughout the
	 * plugin (stored in a field's `type`).
	 *
	 * @return array<string,array{label:string,group:string,supports:string[]}>
	 */
	public static function types(): array {
		return array(
			'text'           => array( 'label' => __( 'Single Line Text', 'nettwebs-talent-location-management' ), 'group' => 'text', 'supports' => array( 'length', 'placeholder', 'default' ) ),
			'textarea'       => array( 'label' => __( 'Paragraph Text', 'nettwebs-talent-location-management' ), 'group' => 'text', 'supports' => array( 'length', 'placeholder', 'default' ) ),
			'email'          => array( 'label' => __( 'Email', 'nettwebs-talent-location-management' ), 'group' => 'text', 'supports' => array( 'placeholder', 'default' ) ),
			'tel'            => array( 'label' => __( 'Phone', 'nettwebs-talent-location-management' ), 'group' => 'text', 'supports' => array( 'placeholder', 'default' ) ),
			'number'         => array( 'label' => __( 'Number', 'nettwebs-talent-location-management' ), 'group' => 'text', 'supports' => array( 'placeholder', 'default' ) ),
			'url'            => array( 'label' => __( 'URL', 'nettwebs-talent-location-management' ), 'group' => 'text', 'supports' => array( 'placeholder', 'default' ) ),
			'password'       => array( 'label' => __( 'Password', 'nettwebs-talent-location-management' ), 'group' => 'text', 'supports' => array( 'placeholder' ) ),
			'select'         => array( 'label' => __( 'Dropdown', 'nettwebs-talent-location-management' ), 'group' => 'choice', 'supports' => array( 'options', 'default' ) ),
			'radio'          => array( 'label' => __( 'Radio Buttons', 'nettwebs-talent-location-management' ), 'group' => 'choice', 'supports' => array( 'options', 'default' ) ),
			'checkbox_group' => array( 'label' => __( 'Checkboxes', 'nettwebs-talent-location-management' ), 'group' => 'choice', 'supports' => array( 'options' ) ),
			'multiselect'    => array( 'label' => __( 'Multi-Select', 'nettwebs-talent-location-management' ), 'group' => 'choice', 'supports' => array( 'options' ) ),
			'date'           => array( 'label' => __( 'Date', 'nettwebs-talent-location-management' ), 'group' => 'datetime', 'supports' => array( 'default' ) ),
			'time'           => array( 'label' => __( 'Time', 'nettwebs-talent-location-management' ), 'group' => 'datetime', 'supports' => array( 'default' ) ),
			'datetime'       => array( 'label' => __( 'Date & Time', 'nettwebs-talent-location-management' ), 'group' => 'datetime', 'supports' => array( 'default' ) ),
			'file'           => array( 'label' => __( 'File Upload', 'nettwebs-talent-location-management' ), 'group' => 'file', 'supports' => array( 'file' ) ),
			'files'          => array( 'label' => __( 'Multiple File Upload', 'nettwebs-talent-location-management' ), 'group' => 'file', 'supports' => array( 'file' ) ),
			'image'          => array( 'label' => __( 'Image Upload', 'nettwebs-talent-location-management' ), 'group' => 'file', 'supports' => array( 'file' ) ),
			'gallery'        => array( 'label' => __( 'Gallery Upload', 'nettwebs-talent-location-management' ), 'group' => 'file', 'supports' => array( 'file' ) ),
			'checkbox'       => array( 'label' => __( 'Single Checkbox', 'nettwebs-talent-location-management' ), 'group' => 'choice', 'supports' => array( 'default' ) ),
			'html'           => array( 'label' => __( 'HTML / Content', 'nettwebs-talent-location-management' ), 'group' => 'utility', 'supports' => array( 'content' ) ),
			'heading'        => array( 'label' => __( 'Heading', 'nettwebs-talent-location-management' ), 'group' => 'utility', 'supports' => array() ),
			'divider'        => array( 'label' => __( 'Divider', 'nettwebs-talent-location-management' ), 'group' => 'utility', 'supports' => array() ),
			'hidden'         => array( 'label' => __( 'Hidden Field', 'nettwebs-talent-location-management' ), 'group' => 'utility', 'supports' => array( 'default' ) ),
		);
	}

	public static function is_valid_type( string $type ): bool {
		return array_key_exists( $type, self::types() );
	}

	/** Types that submit no visitor-editable value at all. */
	public static function is_non_input_type( string $type ): bool {
		return in_array( $type, array( 'html', 'heading', 'divider' ), true );
	}

	public static function is_file_type( string $type ): bool {
		return in_array( $type, array( 'file', 'files', 'image', 'gallery' ), true );
	}

	public static function is_multi_file_type( string $type ): bool {
		return in_array( $type, array( 'files', 'gallery' ), true );
	}

	/**
	 * The Field Library shown in the Form Builder's left column, grouped
	 * exactly as requested: Basic, Choice, Date/Time, Files, Personal,
	 * Address, Agency/Talent, Location, Utility. Each entry is a *preset* of
	 * one of the types above (icon, suggested key/label/options) — dragging
	 * one onto the canvas creates a field of that underlying `type` with
	 * these defaults, all still freely editable afterward.
	 *
	 * Filterable (`am_field_library`) so the library stays extensible
	 * without editing this file, per the "extensible for later" requirement.
	 *
	 * @return array<string,array{label:string,items:array}>
	 */
	public static function library(): array {
		$library = array(
			'basic'    => array(
				'label' => __( 'Basic', 'nettwebs-talent-location-management' ),
				'items' => array(
					array( 'type' => 'text', 'key' => 'single_line_text', 'label' => __( 'Single Line Text', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-editor-textcolor' ),
					array( 'type' => 'textarea', 'key' => 'paragraph_text', 'label' => __( 'Paragraph Text', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-editor-alignleft' ),
					array( 'type' => 'email', 'key' => 'email', 'label' => __( 'Email', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-email' ),
					array( 'type' => 'tel', 'key' => 'phone', 'label' => __( 'Phone', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-phone' ),
					array( 'type' => 'number', 'key' => 'number', 'label' => __( 'Number', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-calculator' ),
					array( 'type' => 'url', 'key' => 'url', 'label' => __( 'URL', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-admin-links' ),
					array( 'type' => 'password', 'key' => 'password', 'label' => __( 'Password', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-lock' ),
				),
			),
			'choice'   => array(
				'label' => __( 'Choice', 'nettwebs-talent-location-management' ),
				'items' => array(
					array( 'type' => 'select', 'key' => 'dropdown', 'label' => __( 'Dropdown', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-menu' ),
					array( 'type' => 'radio', 'key' => 'radio_buttons', 'label' => __( 'Radio Buttons', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-marker' ),
					array( 'type' => 'checkbox_group', 'key' => 'checkboxes', 'label' => __( 'Checkboxes', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-forms' ),
					array( 'type' => 'multiselect', 'key' => 'multi_select', 'label' => __( 'Multi-Select', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-list-view' ),
				),
			),
			'datetime' => array(
				'label' => __( 'Date/Time', 'nettwebs-talent-location-management' ),
				'items' => array(
					array( 'type' => 'date', 'key' => 'date', 'label' => __( 'Date', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-calendar-alt' ),
					array( 'type' => 'time', 'key' => 'time', 'label' => __( 'Time', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-clock' ),
					array( 'type' => 'datetime', 'key' => 'date_time', 'label' => __( 'Date & Time', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-calendar' ),
				),
			),
			'files'    => array(
				'label' => __( 'Files', 'nettwebs-talent-location-management' ),
				'items' => array(
					array( 'type' => 'file', 'key' => 'file_upload', 'label' => __( 'File Upload', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-upload' ),
					array( 'type' => 'files', 'key' => 'multiple_file_upload', 'label' => __( 'Multiple File Upload', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-media-default' ),
					array( 'type' => 'image', 'key' => 'photo', 'label' => __( 'Image Upload', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-format-image' ),
					array( 'type' => 'gallery', 'key' => 'gallery_ids', 'label' => __( 'Gallery Upload', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-format-gallery' ),
				),
			),
			'personal' => array(
				'label' => __( 'Personal', 'nettwebs-talent-location-management' ),
				'items' => array(
					array( 'type' => 'text', 'key' => 'first_name', 'label' => __( 'First Name', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-admin-users' ),
					array( 'type' => 'text', 'key' => 'last_name', 'label' => __( 'Last Name', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-admin-users' ),
					array( 'type' => 'text', 'key' => 'full_name', 'label' => __( 'Full Name', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-admin-users' ),
					array( 'type' => 'email', 'key' => 'email', 'label' => __( 'Email', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-email' ),
					array( 'type' => 'tel', 'key' => 'phone', 'label' => __( 'Phone', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-phone' ),
				),
			),
			'address'  => array(
				'label' => __( 'Address', 'nettwebs-talent-location-management' ),
				'items' => array(
					array( 'type' => 'text', 'key' => 'address', 'label' => __( 'Address', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-location' ),
					array( 'type' => 'text', 'key' => 'city', 'label' => __( 'City', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-location' ),
					array( 'type' => 'text', 'key' => 'state', 'label' => __( 'Province/State', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-location' ),
					array( 'type' => 'text', 'key' => 'country', 'label' => __( 'Country', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-location' ),
					array( 'type' => 'text', 'key' => 'postal_code', 'label' => __( 'Postal Code', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-location' ),
				),
			),
			'talent'   => array(
				'label' => __( 'Agency/Talent Specific', 'nettwebs-talent-location-management' ),
				'items' => array(
					array(
						'type'    => 'select',
						'key'     => 'gender',
						'label'   => __( 'Gender', 'nettwebs-talent-location-management' ),
						'icon'    => 'dashicons-admin-users',
						'options' => array(
							array( 'label' => __( 'Female', 'nettwebs-talent-location-management' ), 'value' => 'female' ),
							array( 'label' => __( 'Male', 'nettwebs-talent-location-management' ), 'value' => 'male' ),
							array( 'label' => __( 'Non-binary', 'nettwebs-talent-location-management' ), 'value' => 'non-binary' ),
							array( 'label' => __( 'Prefer not to say', 'nettwebs-talent-location-management' ), 'value' => 'undisclosed' ),
						),
					),
					array( 'type' => 'date', 'key' => 'date_of_birth', 'label' => __( 'Date of Birth', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-calendar-alt' ),
					array( 'type' => 'text', 'key' => 'height', 'label' => __( 'Height', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-arrow-up-alt' ),
					array( 'type' => 'text', 'key' => 'weight', 'label' => __( 'Weight', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-chart-bar' ),
					array( 'type' => 'text', 'key' => 'shoe_size', 'label' => __( 'Shoe Size', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-admin-generic' ),
					array( 'type' => 'text', 'key' => 'clothing_size', 'label' => __( 'Clothing Size', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-admin-generic' ),
					array( 'type' => 'text', 'key' => 'hair_color', 'label' => __( 'Hair Colour', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-admin-appearance' ),
					array( 'type' => 'text', 'key' => 'eye_color', 'label' => __( 'Eye Colour', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-admin-appearance' ),
					array( 'type' => 'textarea', 'key' => 'experience', 'label' => __( 'Experience', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-portfolio' ),
					array( 'type' => 'textarea', 'key' => 'skills', 'label' => __( 'Skills', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-star-filled' ),
					array( 'type' => 'textarea', 'key' => 'languages', 'label' => __( 'Languages', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-translation' ),
					array( 'type' => 'text', 'key' => 'social_media', 'label' => __( 'Social Media', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-share' ),
					array( 'type' => 'url', 'key' => 'portfolio_url', 'label' => __( 'Portfolio URL', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-admin-links' ),
					array(
						'type'    => 'select',
						'key'     => 'availability',
						'label'   => __( 'Availability', 'nettwebs-talent-location-management' ),
						'icon'    => 'dashicons-clock',
						'options' => array(
							array( 'label' => __( 'Available', 'nettwebs-talent-location-management' ), 'value' => 'available' ),
							array( 'label' => __( 'Limited', 'nettwebs-talent-location-management' ), 'value' => 'limited' ),
							array( 'label' => __( 'Booked', 'nettwebs-talent-location-management' ), 'value' => 'booked' ),
						),
					),
					array( 'type' => 'text', 'key' => 'preferred_locations', 'label' => __( 'Preferred Locations', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-location-alt' ),
				),
			),
			'location' => array(
				'label' => __( 'Location Specific', 'nettwebs-talent-location-management' ),
				'items' => array(
					array( 'type' => 'text', 'key' => 'location_name', 'label' => __( 'Location Name', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-building' ),
					array( 'type' => 'text', 'key' => 'location_type', 'label' => __( 'Location Type', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-category' ),
					array( 'type' => 'text', 'key' => 'address', 'label' => __( 'Address', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-location' ),
					array( 'type' => 'text', 'key' => 'city', 'label' => __( 'City', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-location' ),
					array( 'type' => 'text', 'key' => 'state', 'label' => __( 'Province/State', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-location' ),
					array( 'type' => 'text', 'key' => 'country', 'label' => __( 'Country', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-location' ),
					array( 'type' => 'number', 'key' => 'capacity', 'label' => __( 'Capacity', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-groups' ),
					array( 'type' => 'textarea', 'key' => 'description', 'label' => __( 'Description', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-editor-alignleft' ),
					array( 'type' => 'url', 'key' => 'website', 'label' => __( 'Website', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-admin-links' ),
					array( 'type' => 'text', 'key' => 'contact_name', 'label' => __( 'Contact Name', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-admin-users' ),
					array( 'type' => 'email', 'key' => 'contact_email', 'label' => __( 'Contact Email', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-email' ),
					array( 'type' => 'tel', 'key' => 'contact_phone', 'label' => __( 'Contact Phone', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-phone' ),
					array( 'type' => 'image', 'key' => 'location_image', 'label' => __( 'Location Images', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-format-image' ),
					array( 'type' => 'gallery', 'key' => 'location_gallery', 'label' => __( 'Location Gallery', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-format-gallery' ),
				),
			),
			'utility'  => array(
				'label' => __( 'Utility', 'nettwebs-talent-location-management' ),
				'items' => array(
					array( 'type' => 'html', 'key' => 'content', 'label' => __( 'HTML / Content', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-editor-code' ),
					array( 'type' => 'heading', 'key' => 'heading', 'label' => __( 'Heading', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-heading' ),
					array( 'type' => 'divider', 'key' => 'divider', 'label' => __( 'Divider', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-minus' ),
					array( 'type' => 'hidden', 'key' => 'hidden_field', 'label' => __( 'Hidden Field', 'nettwebs-talent-location-management' ), 'icon' => 'dashicons-hidden' ),
					array(
						'type'     => 'checkbox',
						'key'      => 'consent',
						'label'    => __( 'I consent to the processing of my information.', 'nettwebs-talent-location-management' ),
						'icon'     => 'dashicons-yes-alt',
						'required' => true,
					),
				),
			),
		);

		/**
		 * Filters the Field Library shown in the Form Builder — lets other
		 * code add new groups/items without editing this file.
		 *
		 * @param array $library
		 */
		return apply_filters( 'am_field_library', $library );
	}
}
