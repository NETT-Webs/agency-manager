<?php
namespace AgencyManager\Shortcodes;

use AgencyManager\Frontend\Carousel_Renderer;
use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The eight grid/featured/carousel/slider shortcodes, plus the two dedicated
 * scouting shortcodes. All eight standard shortcodes respect the relevant
 * Display Mode; *_featured additionally defaults to the Homepage Control
 * settings so the site owner never has to open Elementor to change the
 * homepage sections.
 *
 * Common attributes: limit (preferred) / count (alias — limit wins if both
 * are given), mode (inherit|hidden|scouting|live), columns, only_featured,
 * only_active, order (newest|oldest|random). Talent additionally accepts
 * category (talent_category slug) and group (talent_group slug); Location
 * accepts type (location_type slug).
 *
 * Appearance is entirely the active theme's responsibility (CSS variables
 * and/or a template override — see Frontend\Templates::locate()/
 * Frontend\Template_Loader) — no shortcode attribute selects a "style," and
 * none of these classes reference colours, fonts, or any other agency's
 * branding.
 *
 * [talent_scouting] / [location_scouting] always render the "Now Scouting"
 * placeholder cards, regardless of the global Display Mode or any per-widget
 * mode override — for placing a scouting section anywhere on demand. They
 * accept limit and columns only (a placeholder card has no taxonomy, active
 * flag, or sort order to filter by).
 */
class Shortcodes {

	private const ORDERS = array( 'newest', 'oldest', 'random' );

	public function register(): void {
		add_shortcode( 'talent_grid', array( $this, 'talent_grid' ) );
		add_shortcode( 'talent_featured', array( $this, 'talent_featured' ) );
		add_shortcode( 'talent_carousel', array( $this, 'talent_carousel' ) );
		add_shortcode( 'talent_slider', array( $this, 'talent_slider' ) );
		add_shortcode( 'talent_scouting', array( $this, 'talent_scouting' ) );
		add_shortcode( 'location_grid', array( $this, 'location_grid' ) );
		add_shortcode( 'location_featured', array( $this, 'location_featured' ) );
		add_shortcode( 'location_carousel', array( $this, 'location_carousel' ) );
		add_shortcode( 'location_slider', array( $this, 'location_slider' ) );
		add_shortcode( 'location_scouting', array( $this, 'location_scouting' ) );
	}

	public function talent_grid( $atts ): string {
		return $this->render( 'talent', 'grid', $atts, 12 );
	}

	public function talent_featured( $atts ): string {
		return $this->render_featured( 'talent', $atts );
	}

	public function talent_carousel( $atts ): string {
		return $this->render( 'talent', 'carousel', $atts, 9 );
	}

	public function talent_slider( $atts ): string {
		return $this->render( 'talent', 'slider', $atts, 6 );
	}

	public function talent_scouting( $atts ): string {
		return $this->render_scouting( 'talent', $atts );
	}

	public function location_grid( $atts ): string {
		return $this->render( 'location', 'grid', $atts, 12 );
	}

	public function location_featured( $atts ): string {
		return $this->render_featured( 'location', $atts );
	}

	public function location_carousel( $atts ): string {
		return $this->render( 'location', 'carousel', $atts, 9 );
	}

	public function location_slider( $atts ): string {
		return $this->render( 'location', 'slider', $atts, 6 );
	}

	public function location_scouting( $atts ): string {
		return $this->render_scouting( 'location', $atts );
	}

	/**
	 * @param array|string $atts
	 */
	private function render( string $type, string $layout, $atts, int $default_count ): string {
		$defaults = $this->common_defaults( $type, $default_count, 'inherit' );
		$atts     = shortcode_atts( $defaults, $atts, "{$type}_{$layout}" );

		$args = $this->build_args_from_atts( $type, $atts );

		return Carousel_Renderer::render( $type, $layout, $args, $this->sanitize_columns( $atts['columns'] ) );
	}

	/**
	 * @param array|string $atts
	 */
	private function render_featured( string $type, $atts ): string {
		$homepage_config = Settings::get_homepage_config( $type );

		$defaults = $this->common_defaults( $type, (int) $homepage_config['count'], $homepage_config['display_mode'] );
		$atts     = shortcode_atts( $defaults, $atts, "{$type}_featured" );

		$args                  = $this->build_args_from_atts( $type, $atts );
		$args['homepage_only'] = true;

		return Carousel_Renderer::render( $type, 'grid', $args, $this->sanitize_columns( $atts['columns'] ) );
	}

	/**
	 * [talent_scouting]/[location_scouting] — always renders placeholder
	 * cards via a forced 'scouting' display_mode, bypassing the global
	 * Display Mode and Query entirely (Carousel_Renderer short-circuits to
	 * Card_Renderer::render_placeholder_cards() before any WP_Query runs).
	 *
	 * @param array|string $atts
	 */
	private function render_scouting( string $type, $atts ): string {
		$placeholder_config = Settings::get_placeholder_config( $type );

		$atts = shortcode_atts(
			array(
				'limit'   => '',
				'count'   => (int) $placeholder_config['count'],
				'columns' => '',
			),
			$atts,
			"{$type}_scouting"
		);

		$args = array(
			'count'        => $this->resolve_count( $atts, (int) $placeholder_config['count'] ),
			'display_mode' => 'scouting',
		);

		return Carousel_Renderer::render( $type, 'grid', $args, $this->sanitize_columns( $atts['columns'] ) );
	}

	private function common_defaults( string $type, int $default_count, string $default_mode ): array {
		$defaults = array(
			'count'         => $default_count,
			'limit'         => '',
			'mode'          => $default_mode,
			'columns'       => '',
			'only_featured' => '',
			'only_active'   => '1',
			'order'         => 'newest',
		);

		if ( 'talent' === $type ) {
			$defaults['category'] = '';
			$defaults['group']    = '';
		} else {
			$defaults['type'] = '';
		}

		return $defaults;
	}

	/**
	 * `limit` is the preferred, documented attribute; `count` remains a
	 * working alias for backward compatibility. If both are supplied,
	 * `limit` wins.
	 */
	private function resolve_count( array $atts, int $default ): int {
		if ( isset( $atts['limit'] ) && '' !== $atts['limit'] ) {
			return max( 1, absint( $atts['limit'] ) );
		}

		if ( isset( $atts['count'] ) && '' !== $atts['count'] ) {
			return max( 1, absint( $atts['count'] ) );
		}

		return $default;
	}

	private function build_args_from_atts( string $type, array $atts ): array {
		$args = array(
			'count'         => $this->resolve_count( $atts, (int) $atts['count'] ),
			'only_active'   => $this->to_bool( $atts['only_active'], true ),
			'featured_only' => $this->to_bool( $atts['only_featured'], false ),
			'order'         => in_array( $atts['order'], self::ORDERS, true ) ? $atts['order'] : 'newest',
		);

		if ( 'talent' === $type ) {
			if ( ! empty( $atts['category'] ) ) {
				$args['category'] = sanitize_title( $atts['category'] );
			}
			if ( ! empty( $atts['group'] ) ) {
				$args['group'] = sanitize_title( $atts['group'] );
			}
		} elseif ( ! empty( $atts['type'] ) ) {
			$args['type'] = sanitize_title( $atts['type'] );
		}

		if ( 'inherit' !== $atts['mode'] ) {
			$args['display_mode'] = sanitize_key( $atts['mode'] );
		}

		return $args;
	}

	private function sanitize_columns( $value ): int {
		$columns = absint( $value );

		return ( $columns >= 1 && $columns <= 6 ) ? $columns : 0;
	}

	/**
	 * @param mixed $value
	 */
	private function to_bool( $value, bool $default ): bool {
		if ( '' === $value || null === $value ) {
			return $default;
		}

		return in_array( strtolower( (string) $value ), array( '1', 'yes', 'true' ), true );
	}
}
