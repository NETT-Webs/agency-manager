<?php
namespace AgencyManager\Frontend;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Card_Renderer {

	/**
	 * Card/hero image: first gallery image if a gallery is set, else the
	 * featured image, else none (caller renders a placeholder block).
	 *
	 * @param string $type 'talent'|'location' — needed only to resolve the
	 *                     gallery field through Meta_Resolver's fallback map.
	 */
	public static function get_card_image_id( int $post_id, string $type = 'talent' ): int {
		$gallery_ids = Meta_Resolver::get( $post_id, $type, 'gallery_ids' );

		if ( $gallery_ids ) {
			$ids = array_filter( array_map( 'trim', explode( ',', $gallery_ids ) ) );
			if ( ! empty( $ids ) ) {
				return (int) $ids[0];
			}
		}

		if ( has_post_thumbnail( $post_id ) ) {
			return (int) get_post_thumbnail_id( $post_id );
		}

		return 0;
	}

	public static function render_talent_card( int $post_id ): string {
		return Templates::render( 'talent-card', array( 'post_id' => $post_id ) );
	}

	public static function render_location_card( int $post_id ): string {
		return Templates::render( 'location-card', array( 'post_id' => $post_id ) );
	}

	/**
	 * @param string $type 'talent'|'location'
	 */
	public static function render_placeholder_cards( string $type, int $count ): string {
		$config   = Settings::get_placeholder_config( $type );
		$template = "{$type}-placeholder-card";
		$images   = array_values( array_filter( array_map( 'absint', (array) ( $config['image_ids'] ?? array() ) ) ) );
		$html     = '';

		for ( $i = 0; $i < $count; $i++ ) {
			$card_config = $config;

			// Multiple Scouting Images: cycle through the configured images
			// (image 1, 2, 3... wrapping back to the start once every image
			// has been used) so a handful of images can cover any number of
			// placeholder cards; a single image repeats on every card, and
			// zero images falls back to the plain placeholder block.
			if ( ! empty( $images ) ) {
				$card_config['image_id'] = $images[ $i % count( $images ) ];
			}

			$html .= Templates::render( $template, array( 'config' => $card_config ) );
		}

		return $html;
	}
}
