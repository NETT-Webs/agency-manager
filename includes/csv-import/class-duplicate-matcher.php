<?php
namespace AgencyManager\Csv_Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Finds an existing Talent/Location record a CSV row might already
 * correspond to — deliberately never matches on name alone (the spec's
 * "do not rely solely on name" requirement); the three supported
 * strategies are an explicit WordPress post ID, contact email, or an
 * explicit "match by name" opt-in the user chooses knowingly in the import
 * options (not a silent default).
 */
class Duplicate_Matcher {

	/**
	 * @param string $post_type 'talent'|'location'
	 * @param string $match_field 'id'|'email'|'title'
	 * @param string $value The CSV row's value for whichever column is configured for matching.
	 */
	public static function find( string $post_type, string $match_field, string $value ): int {
		$value = trim( $value );
		if ( '' === $value ) {
			return 0;
		}

		switch ( $match_field ) {
			case 'id':
				$id   = absint( $value );
				$post = $id ? get_post( $id ) : null;
				return ( $post && $post_type === $post->post_type && 'trash' !== $post->post_status ) ? $id : 0;

			case 'email':
				$query = new \WP_Query( array(
					'post_type'      => $post_type,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one-off admin-triggered lookup, not a hot path.
						array( 'key' => '_am_contact_email', 'value' => $value, 'compare' => '=' ),
					),
				) );
				return $query->have_posts() ? (int) $query->posts[0] : 0;

			case 'title':
				$query = new \WP_Query( array(
					'post_type'      => $post_type,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'title'          => $value,
				) );
				return $query->have_posts() ? (int) $query->posts[0] : 0;

			default:
				return 0;
		}
	}
}
