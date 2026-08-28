<?php
namespace AgencyManager\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads a template file, checking the active theme first
 * (stylesheet-dir/agency-manager/{name}.php) so any theme can override the
 * markup without touching plugin files, then falling back to the plugin's
 * own templates/{name}.php. Also registers the shared frontend/carousel
 * assets (registered up front, enqueued on demand by whoever needs them).
 */
class Templates {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets(): void {
		wp_register_style( 'am-frontend', AM_PLUGIN_URL . 'assets/css/frontend.css', array(), AM_VERSION );
		wp_register_style( 'am-carousel', AM_PLUGIN_URL . 'assets/css/carousel.css', array( 'am-frontend' ), AM_VERSION );
		wp_register_script( 'am-carousel', AM_PLUGIN_URL . 'assets/js/carousel.js', array(), AM_VERSION, true );
		wp_register_script( 'am-form-conditional', AM_PLUGIN_URL . 'assets/js/form-conditional.js', array(), AM_VERSION, true );

		// Small and scoped to .am-* classes — safe to enqueue unconditionally
		// rather than trying to detect shortcode/widget presence up front.
		wp_enqueue_style( 'am-frontend' );
	}

	/**
	 * Partial templates (cards) — checks the active theme's override folder,
	 * then falls back to the plugin's own templates/{name}.php.
	 */
	public static function locate( string $name ): string {
		$theme_path = trailingslashit( get_stylesheet_directory() ) . 'agency-manager/' . $name . '.php';

		if ( file_exists( $theme_path ) ) {
			return $theme_path;
		}

		return AM_PLUGIN_DIR . 'templates/' . $name . '.php';
	}

	/**
	 * Full-page templates (archive/single) — same override folder takes
	 * priority; otherwise a theme's own conventionally-named root template
	 * (single-talent.php, archive-location.php, etc. — WordPress's own
	 * template-hierarchy naming) is respected via locate_template() before
	 * finally falling back to the plugin's own default under templates/.
	 * This means a theme that already has its own single-talent.php never
	 * needs an agency-manager/ override folder at all — nothing changes for
	 * a site like Eden Cast that already provides its own templates.
	 */
	public static function locate_page_template( string $name ): string {
		$theme_override = trailingslashit( get_stylesheet_directory() ) . 'agency-manager/' . $name . '.php';

		if ( file_exists( $theme_override ) ) {
			return $theme_override;
		}

		$native = locate_template( array( $name . '.php' ) );

		if ( $native ) {
			return $native;
		}

		return AM_PLUGIN_DIR . 'templates/' . $name . '.php';
	}

	public static function render( string $name, array $vars = array() ): string {
		$path = self::locate( $name );

		if ( ! file_exists( $path ) ) {
			return '';
		}

		// EXTR_SKIP protects $name/$path/$vars themselves from being
		// clobbered by an identically-named entry in $vars.
		extract( $vars, EXTR_SKIP );

		ob_start();
		include $path;

		return (string) ob_get_clean();
	}
}
