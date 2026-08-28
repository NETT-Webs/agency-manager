<?php
namespace AgencyManager\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads an Agency Manager `_am_*` field, falling back to another system's
 * meta key when the plugin doesn't own the post's data (e.g. a theme with
 * its own pre-existing Talent/Location records and its own meta prefix).
 *
 * Agency Manager ships zero fallback mappings itself — it has no knowledge
 * of any specific theme or plugin. A theme/plugin declares its own mapping
 * via the `am_meta_fallback_map` filter, and nothing changes for installs
 * where nothing hooks it: `_am_*` is read first and used whenever present,
 * so this is purely additive.
 */
class Meta_Resolver {

	/**
	 * @param string $type  'talent'|'location'
	 * @param string $field Field name without the `_am_` prefix, e.g. 'city'.
	 */
	public static function get( int $post_id, string $type, string $field, string $default = '' ): string {
		$value = get_post_meta( $post_id, "_am_{$field}", true );

		if ( '' !== $value && false !== $value ) {
			return (string) $value;
		}

		$map = self::fallback_map( $type, $post_id );

		if ( empty( $map[ $field ] ) ) {
			return $default;
		}

		$fallback_value = get_post_meta( $post_id, $map[ $field ], true );

		return ( '' !== $fallback_value && false !== $fallback_value ) ? (string) $fallback_value : $default;
	}

	/**
	 * @return array<string,string> field name => meta key to fall back to.
	 */
	private static function fallback_map( string $type, int $post_id ): array {
		/**
		 * Filters the field => meta-key fallback map used when the matching
		 * `_am_*` value is empty. Empty by default — a theme or integration
		 * plugin adds its own entries (e.g. `'city' => '_ec_city'`) without
		 * Agency Manager ever referencing that system by name.
		 *
		 * @param array  $map     field name => meta key.
		 * @param string $type    'talent'|'location'.
		 * @param int    $post_id
		 */
		return (array) apply_filters( 'am_meta_fallback_map', array(), $type, $post_id );
	}
}
