<?php
namespace AgencyManager\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Query arg builders for talent/location listings. Every listing filters
 * to `_am_active` by default (missing meta counts as active, since existing
 * posts predate the field) unless `only_active` is explicitly false;
 * `featured_only`/`homepage_only` layer additional meta filters on top for
 * the *_featured shortcodes/widgets and the homepage query.
 *
 * Talent can filter by `category` (talent_category) and `group`
 * (talent_group) simultaneously; Location filters by `type` (location_type).
 */
class Query {

	private const ORDER_MAP = array(
		'newest' => array(
			'orderby' => 'date',
			'order'   => 'DESC',
		),
		'oldest' => array(
			'orderby' => 'date',
			'order'   => 'ASC',
		),
		'random' => array(
			'orderby' => 'rand',
		),
	);

	public static function talent_args( array $args = array() ): array {
		$tax_filters = array();

		if ( ! empty( $args['category'] ) ) {
			$tax_filters[] = array(
				'taxonomy' => 'talent_category',
				'field'    => 'slug',
				'terms'    => $args['category'],
			);
		}

		if ( ! empty( $args['group'] ) ) {
			$tax_filters[] = array(
				'taxonomy' => 'talent_group',
				'field'    => 'slug',
				'terms'    => $args['group'],
			);
		}

		unset( $args['category'], $args['group'] );

		/**
		 * Filters the final WP_Query args for any talent listing (shortcode,
		 * widget, or the homepage query).
		 *
		 * @param array $query_args
		 * @param array $args Args passed in before defaults were applied.
		 */
		return apply_filters( 'am_talent_query_args', self::build_args( 'talent', $tax_filters, $args ), $args );
	}

	public static function location_args( array $args = array() ): array {
		$tax_filters = array();

		if ( ! empty( $args['type'] ) ) {
			$tax_filters[] = array(
				'taxonomy' => 'location_type',
				'field'    => 'slug',
				'terms'    => $args['type'],
			);
		}

		unset( $args['type'] );

		/**
		 * Filters the final WP_Query args for any location listing.
		 *
		 * @param array $query_args
		 * @param array $args Args passed in before defaults were applied.
		 */
		return apply_filters( 'am_location_query_args', self::build_args( 'location', $tax_filters, $args ), $args );
	}

	private static function build_args( string $post_type, array $tax_filters, array $args ): array {
		$meta_clauses = array();

		$only_active = ! isset( $args['only_active'] ) || $args['only_active'];
		if ( $only_active ) {
			$meta_clauses[] = array(
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
			);
		}

		if ( ! empty( $args['featured_only'] ) ) {
			$meta_clauses[] = array(
				'key'     => '_am_featured',
				'value'   => '1',
				'compare' => '=',
			);
		}

		if ( ! empty( $args['homepage_only'] ) ) {
			$meta_clauses[] = array(
				'key'     => '_am_homepage',
				'value'   => '1',
				'compare' => '=',
			);
		}

		unset( $args['only_active'], $args['featured_only'], $args['homepage_only'] );

		$order_key = isset( $args['order'], self::ORDER_MAP[ $args['order'] ] ) ? $args['order'] : 'newest';
		unset( $args['order'] );

		$defaults = array_merge(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => isset( $args['count'] ) ? (int) $args['count'] : 12,
				'paged'          => isset( $args['paged'] ) ? (int) $args['paged'] : 1,
			),
			self::ORDER_MAP[ $order_key ]
		);

		unset( $args['count'], $args['paged'] );

		if ( $meta_clauses ) {
			$defaults['meta_query'] = count( $meta_clauses ) > 1
				? array_merge( array( 'relation' => 'AND' ), $meta_clauses )
				: $meta_clauses;
		}

		if ( $tax_filters ) {
			$defaults['tax_query'] = count( $tax_filters ) > 1
				? array_merge( array( 'relation' => 'AND' ), $tax_filters )
				: $tax_filters;
		}

		return array_merge( $defaults, $args );
	}
}
