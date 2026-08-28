<?php
namespace AgencyManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin wrapper around the single `am_settings` option — the one place every
 * shortcode, widget, and admin screen reads/writes plugin configuration.
 * Kept as one option (rather than many) so Backup/Restore and the
 * "Plugin Settings" / "Display Settings" / "Homepage Settings" export
 * sections are all trivial slices of the same array.
 */
class Settings {

	private const OPTION_KEY = 'am_settings';

	public static function defaults(): array {
		$defaults = array(
			'agency_type'        => 'both',
			'notification_email' => get_option( 'admin_email' ),
			'display'            => array(
				'talent'   => 'scouting',
				'location' => 'scouting',
			),
			'placeholder'        => array(
				'talent'   => array(
					'badge'       => __( 'Now Scouting', 'agency-manager' ),
					'heading'     => __( 'Now Scouting Talent', 'agency-manager' ),
					'description' => __( "We're actively building our roster. New profiles are added regularly — if you're interested in representation, we'd love to hear from you.", 'agency-manager' ),
					'button_text' => __( 'Apply Now', 'agency-manager' ),
					'button_link' => '',
					'image_ids'   => array(),
					'count'       => 8,
				),
				'location' => array(
					'badge'       => __( 'Now Scouting', 'agency-manager' ),
					'heading'     => __( 'Now Scouting Locations', 'agency-manager' ),
					'description' => __( "We're actively expanding our portfolio of locations. If you own a unique space suitable for productions, we'd love to hear from you.", 'agency-manager' ),
					'button_text' => __( 'Register Location', 'agency-manager' ),
					'button_link' => '',
					'image_ids'   => array(),
					'count'       => 6,
				),
			),
			'homepage'           => array(
				'talent'   => array(
					'heading'      => __( 'Featured Talent', 'agency-manager' ),
					'subheading'   => '',
					'button_text'  => __( 'View All Talent', 'agency-manager' ),
					'button_link'  => '',
					'count'        => 4,
					'display_mode' => 'inherit',
					'card_style'   => 'elegant',
					'animation'    => 'none',
				),
				'location' => array(
					'heading'      => __( 'Featured Locations', 'agency-manager' ),
					'subheading'   => '',
					'button_text'  => __( 'View All Locations', 'agency-manager' ),
					'button_link'  => '',
					'count'        => 3,
					'display_mode' => 'inherit',
					'card_style'   => 'elegant',
					'animation'    => 'none',
				),
			),
			'widget_style_presets' => array(),
			'custom_fields'        => array(
				'talent'   => array(),
				'location' => array(),
			),
			// Saved CSV Import column-mapping templates (Csv_Import\*) — keyed
			// by a generated ID, same storage pattern as widget_style_presets
			// above: one slice of this single option, no new database table.
			'csv_mapping_templates' => array(),
		);

		/**
		 * Filters the plugin's default settings structure — e.g. so a
		 * theme can ship its own default placeholder copy. Add or change
		 * values only; removing a top-level key will break code that reads
		 * it unconditionally elsewhere in the plugin.
		 *
		 * @param array $defaults
		 */
		return apply_filters( 'am_settings_defaults', $defaults );
	}

	/**
	 * Request-scoped memoization: get_option() is itself already cached by
	 * WordPress, but a page with several shortcodes/widgets can easily call
	 * all() a dozen times, each re-running the recursive default-merge for
	 * no new information. Cleared on update() so a save is reflected
	 * immediately within the same request.
	 */
	private static ?array $cache = null;

	public static function all(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$stored      = get_option( self::OPTION_KEY, array() );
		self::$cache = self::merge_defaults( is_array( $stored ) ? $stored : array(), self::defaults() );

		return self::$cache;
	}

	public static function update( array $settings ): void {
		update_option( self::OPTION_KEY, self::merge_defaults( $settings, self::defaults() ) );
		self::$cache = null;
	}

	/**
	 * @param mixed $fallback
	 * @return mixed
	 */
	public static function get( string $path, $fallback = null ) {
		$value = self::all();

		foreach ( explode( '.', $path ) as $segment ) {
			if ( ! is_array( $value ) || ! array_key_exists( $segment, $value ) ) {
				return $fallback;
			}
			$value = $value[ $segment ];
		}

		return $value;
	}

	/**
	 * @param string $type 'talent'|'location'
	 */
	public static function get_display_mode( string $type ): string {
		$mode = self::get( "display.$type", 'scouting' );

		return in_array( $mode, array( 'hidden', 'scouting', 'live' ), true ) ? $mode : 'scouting';
	}

	public static function get_placeholder_config( string $type ): array {
		$defaults = self::defaults();

		return self::get( "placeholder.$type", $defaults['placeholder'][ $type ] );
	}

	public static function get_homepage_config( string $type ): array {
		$defaults = self::defaults();

		return self::get( "homepage.$type", $defaults['homepage'][ $type ] );
	}

	/**
	 * @return array<string,array<string,mixed>> preset name => (Style-tab control name => value)
	 */
	public static function get_widget_style_presets(): array {
		$presets = self::get( 'widget_style_presets', array() );

		return is_array( $presets ) ? $presets : array();
	}

	/**
	 * Custom Talent/Location fields registered via a Form Builder field
	 * mapping's "Create New Custom Field" option — lets that field be
	 * offered as an "Existing Field" choice when mapping other forms,
	 * instead of being silently recreated (and possibly retyped/mistyped)
	 * per form. Storage mirrors the existing `widget_style_presets` pattern:
	 * a slice of the single `am_settings` option, keyed by field key.
	 *
	 * @param string $type 'talent'|'location'
	 * @return array<string,array{label:string,type:string}>
	 */
	public static function get_custom_fields( string $type ): array {
		$fields = self::get( "custom_fields.$type", array() );

		return is_array( $fields ) ? $fields : array();
	}

	/**
	 * @param string $type  'talent'|'location'
	 * @param string $key   Already-sanitized field key (no `_am_custom_` prefix).
	 * @param string $label
	 * @param string $field_type One of Forms\Field_Types::types()' keys.
	 */
	public static function register_custom_field( string $type, string $key, string $label, string $field_type ): void {
		if ( ! in_array( $type, array( 'talent', 'location' ), true ) || '' === $key ) {
			return;
		}

		$settings = self::all();

		// Preserve the first-registered label/type — a later form re-using
		// this same custom field key shouldn't silently rename it.
		if ( isset( $settings['custom_fields'][ $type ][ $key ] ) ) {
			return;
		}

		$settings['custom_fields'][ $type ][ $key ] = array(
			'label' => $label,
			'type'  => $field_type,
		);

		self::update( $settings );
	}

	/**
	 * Recursively merges stored values over defaults so newly introduced
	 * settings keys (future plugin versions) always have a value, even for
	 * a site whose `am_settings` option predates them.
	 */
	private static function merge_defaults( array $stored, array $defaults ): array {
		foreach ( $defaults as $key => $default_value ) {
			if ( ! array_key_exists( $key, $stored ) ) {
				$stored[ $key ] = $default_value;
				continue;
			}
			if ( is_array( $default_value ) && is_array( $stored[ $key ] ) ) {
				$stored[ $key ] = self::merge_defaults( $stored[ $key ], $default_value );
			}
		}

		return $stored;
	}
}
