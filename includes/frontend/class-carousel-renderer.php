<?php
namespace AgencyManager\Frontend;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared rendering for every grid/carousel/slider shortcode and Elementor
 * widget. "Grid" is a static CSS grid; "Carousel" and "Slider" both wrap the
 * same card markup in one dependency-free JS component
 * (assets/js/carousel.js), differing only in the `layout` config passed
 * here — one implementation instead of four.
 */
class Carousel_Renderer {

	/**
	 * @param string $type    'talent'|'location'
	 * @param string $layout  'grid'|'carousel'|'slider'
	 * @param array  $args    Query::talent_args()/location_args()-compatible overrides, plus 'display_mode'.
	 * @param int    $columns Grid columns override (1-6); 0 = the default responsive auto-fill behaviour. Ignored for carousel/slider.
	 */
	public static function render( string $type, string $layout, array $args = array(), int $columns = 0 ): string {
		$mode = self::resolve_display_mode( $type, $args );
		unset( $args['display_mode'] );

		if ( 'hidden' === $mode ) {
			return '';
		}

		$count = isset( $args['count'] ) ? (int) $args['count'] : 12;

		$inner = 'scouting' === $mode
			? Card_Renderer::render_placeholder_cards( $type, $count )
			: self::render_real_cards( $type, $args );

		if ( '' === trim( $inner ) ) {
			return '';
		}

		$wrapper_class = 'talent' === $type ? 'am-talent-grid' : 'am-location-grid';
		$layout_class  = 'am-layout-' . $layout;

		if ( 'grid' === $layout ) {
			$inline_style = $columns > 0 ? sprintf( ' style="--am-columns: %d;"', $columns ) : '';

			return sprintf( '<div class="%1$s %2$s"%3$s>%4$s</div>', esc_attr( $wrapper_class ), esc_attr( $layout_class ), $inline_style, $inner );
		}

		wp_enqueue_script( 'am-carousel' );
		wp_enqueue_style( 'am-carousel' );

		return sprintf(
			'<div class="am-carousel %1$s %2$s" data-autoplay="%3$s"><div class="am-carousel__track">%4$s</div><button type="button" class="am-carousel__prev" aria-label="%5$s">&larr;</button><button type="button" class="am-carousel__next" aria-label="%6$s">&rarr;</button></div>',
			esc_attr( $wrapper_class ),
			esc_attr( $layout_class ),
			esc_attr( 'slider' === $layout ? '1' : '0' ),
			$inner,
			esc_attr__( 'Previous', 'agency-manager' ),
			esc_attr__( 'Next', 'agency-manager' )
		);
	}

	private static function resolve_display_mode( string $type, array $args ): string {
		if ( ! empty( $args['display_mode'] ) && 'inherit' !== $args['display_mode'] ) {
			return $args['display_mode'];
		}

		return Settings::get_display_mode( $type );
	}

	private static function render_real_cards( string $type, array $args ): string {
		$query_args = 'talent' === $type ? Query::talent_args( $args ) : Query::location_args( $args );
		$query      = new \WP_Query( $query_args );

		if ( ! $query->have_posts() ) {
			return '';
		}

		$html = '';
		foreach ( $query->posts as $post ) {
			$html .= 'talent' === $type ? Card_Renderer::render_talent_card( $post->ID ) : Card_Renderer::render_location_card( $post->ID );
		}
		wp_reset_postdata();

		return $html;
	}
}
