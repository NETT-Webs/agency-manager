<?php
namespace AgencyManager\Admin;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Dashboard's stats/recent-activity queries — extracted verbatim from
 * the original `Dashboard_Page` (same `WP_Query`/`wp_count_posts`/meta_query
 * logic, unchanged) so both the classic PHP page and the new REST endpoint
 * (`Rest\Dashboard_Rest_Controller`) read from one source of truth instead
 * of duplicating the counting logic.
 */
class Dashboard_Data {

	public static function get_stats(): array {
		$talent_counts   = (array) wp_count_posts( 'talent' );
		$location_counts = (array) wp_count_posts( 'location' );

		return array(
			'talent'       => array(
				'total'        => (int) ( $talent_counts['publish'] ?? 0 ),
				'featured'     => self::count_by_meta( 'talent', '_am_featured', '1' ),
				'active'       => self::count_active( 'talent' ),
				'display_mode' => Settings::get_display_mode( 'talent' ),
			),
			'locations'    => array(
				'total'        => (int) ( $location_counts['publish'] ?? 0 ),
				'featured'     => self::count_by_meta( 'location', '_am_featured', '1' ),
				'active'       => self::count_active( 'location' ),
				'display_mode' => Settings::get_display_mode( 'location' ),
			),
			'applications' => array(
				'pending'  => self::count_submissions_by_status( array( 'submitted', 'review' ) ),
				'approved' => self::count_submissions_by_status( array( 'approved' ) ),
			),
		);
	}

	private static function count_by_meta( string $post_type, string $meta_key, string $meta_value ): int {
		$query = new \WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => $meta_key,
				'meta_value'     => $meta_value,
			)
		);

		return (int) $query->found_posts;
	}

	private static function count_active( string $post_type ): int {
		$query = new \WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_am_active',
						'value'   => '1',
						'compare' => '=',
					),
					array(
						'key'     => '_am_active',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	private static function count_submissions_by_status( array $statuses ): int {
		$query = new \WP_Query(
			array(
				'post_type'      => 'am_submission',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_am_status',
						'value'   => $statuses,
						'compare' => 'IN',
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	public static function get_recent_activity(): array {
		$items = array();

		$recent = new \WP_Query(
			array(
				'post_type'      => array( 'talent', 'location', 'am_submission' ),
				'post_status'    => 'any',
				'posts_per_page' => 8,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ( $recent->posts as $post ) {
			$items[] = array(
				'title' => $post->post_title ? $post->post_title : __( '(no title)', 'agency-manager' ),
				'type'  => $post->post_type,
				'date'  => get_the_date( '', $post ),
				'url'   => 'am_submission' === $post->post_type
					? admin_url( 'admin.php?page=agency-manager-applications&type=' . ( 'location' === get_post_meta( $post->ID, '_am_type', true ) ? 'location' : 'talent' ) )
					: (string) get_edit_post_link( $post->ID ),
			);
		}

		return $items;
	}
}
