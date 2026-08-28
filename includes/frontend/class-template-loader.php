<?php
namespace AgencyManager\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides default archive/single templates for the talent/location post
 * types, via the standard `template_include` filter pattern (the same
 * approach WooCommerce/EDD use for their own CPTs). Only ever substitutes
 * the plugin's own template as a last resort — a theme's own
 * agency-manager/{name}.php override, or its own conventionally-named root
 * template (single-talent.php, archive-location.php, etc.), always wins via
 * Templates::locate_page_template(). On an install like Eden Cast, where the
 * active theme already provides its own single-talent.php/archive-talent.php
 * at the theme root, this filter changes nothing — Templates::locate_page_template()
 * resolves to that exact same file WordPress would have used anyway.
 */
class Template_Loader {

	private const MAP = array(
		'talent'   => array(
			'single'  => 'single-talent',
			'archive' => 'archive-talent',
		),
		'location' => array(
			'single'  => 'single-location',
			'archive' => 'archive-location',
		),
	);

	public function register(): void {
		add_filter( 'template_include', array( $this, 'maybe_override' ) );
	}

	public function maybe_override( string $template ): string {
		foreach ( self::MAP as $post_type => $names ) {
			if ( is_singular( $post_type ) ) {
				return Templates::locate_page_template( $names['single'] );
			}

			if ( is_post_type_archive( $post_type ) ) {
				return Templates::locate_page_template( $names['archive'] );
			}
		}

		return $template;
	}
}
