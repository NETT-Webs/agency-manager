<?php
namespace AgencyManager\Rest;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read-only listing endpoints behind the Talent/Locations management
 * screens (search, filter, status/featured/active at a glance). This is a
 * view onto the existing `talent`/`location` CPTs, taxonomies, and
 * `_am_active`/`_am_featured` meta (the same fields Frontend\Query already
 * reads) — it creates no data and changes no registration. Each row's
 * `editUrl` points at the React editor screens (see Talent_Rest_Controller/
 * Location_Rest_Controller for the actual record CRUD).
 */
class Content_Rest_Controller extends Rest_Controller {

	public function register_routes(): void {
		register_rest_route( self::NAMESPACE_V1, '/talent', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'list_talent' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( self::NAMESPACE_V1, '/locations', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'list_locations' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
	}

	public function list_talent(): \WP_REST_Response {
		return new \WP_REST_Response( $this->list_posts( 'talent', array( 'talent_category', 'talent_group' ), 'agency-manager-talent' ) );
	}

	public function list_locations(): \WP_REST_Response {
		return new \WP_REST_Response( $this->list_posts( 'location', array( 'location_type' ), 'agency-manager-locations' ) );
	}

	private function list_posts( string $post_type, array $taxonomies, string $menu_slug ): array {
		$posts = get_posts( array(
			'post_type'   => $post_type,
			'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
			'numberposts' => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
		) );

		$rows = array();

		foreach ( $posts as $post ) {
			$terms = array();
			foreach ( $taxonomies as $taxonomy ) {
				foreach ( wp_get_post_terms( $post->ID, $taxonomy ) as $term ) {
					$terms[] = array( 'taxonomy' => $taxonomy, 'name' => $term->name );
				}
			}

			$active_meta = get_post_meta( $post->ID, '_am_active', true );

			$rows[] = array(
				'id'          => $post->ID,
				'title'       => $post->post_title ?: __( '(no title)', 'agency-manager' ),
				'status'      => $post->post_status,
				'thumbnail'   => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ) ?: '',
				'terms'       => $terms,
				'featured'    => '1' === get_post_meta( $post->ID, '_am_featured', true ),
				// Missing meta counts as active — matches Frontend\Query's own default.
				'active'      => '' === $active_meta || '1' === $active_meta,
				'editUrl'     => admin_url( "admin.php?page={$menu_slug}&view=edit&id={$post->ID}" ),
				'viewUrl'     => (string) get_permalink( $post->ID ),
			);
		}

		return $rows;
	}
}
