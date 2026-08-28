<?php
namespace AgencyManager\Rest;

use AgencyManager\Frontend\Card_Renderer;
use AgencyManager\Frontend\Meta_Resolver;
use AgencyManager\Frontend\Templates;
use AgencyManager\Forms\Form_Renderer;
use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared CRUD/preview/options logic behind the Talent and Location React
 * editors (Talent_Rest_Controller / Location_Rest_Controller) — a new UI
 * layer over the exact same `talent`/`location` CPTs, `_am_*` meta
 * convention, and taxonomies the plugin (and Cpt\Meta_Boxes/Meta_Resolver)
 * already use. No new storage: every read goes through Meta_Resolver (so a
 * record whose real data still lives under a theme's own meta prefix, e.g.
 * Eden Cast's `_ec_*`, displays correctly); every write only touches
 * `_am_*` and only for fields that actually changed, so re-saving a record
 * unmodified writes nothing at all and existing theme-owned data is never
 * duplicated or overwritten needlessly.
 */
abstract class Profile_Rest_Controller extends Rest_Controller {

	abstract protected function post_type(): string;

	abstract protected function route_base(): string;

	/**
	 * @return array<string,array{type:string,options?:array<string,string>}> field key (without _am_ prefix) => spec.
	 */
	abstract protected function meta_fields(): array;

	/** @return string[] taxonomy slugs this type uses. */
	abstract protected function taxonomies(): array;

	public function register_routes(): void {
		$base = $this->route_base();

		register_rest_route( self::NAMESPACE_V1, "/$base", array(
			array( 'methods' => 'POST', 'callback' => array( $this, 'create' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );

		register_rest_route( self::NAMESPACE_V1, "/$base/options", array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_options' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );

		register_rest_route( self::NAMESPACE_V1, "/$base/(?P<id>\\d+)", array(
			array( 'methods' => 'GET', 'callback' => array( $this, 'get_record' ), 'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'PUT', 'callback' => array( $this, 'update' ), 'permission_callback' => array( $this, 'check_permission' ) ),
			array( 'methods' => 'DELETE', 'callback' => array( $this, 'delete' ), 'permission_callback' => array( $this, 'check_permission' ) ),
		) );

		register_rest_route( self::NAMESPACE_V1, "/$base/(?P<id>\\d+)/preview", array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_preview' ),
			'permission_callback' => array( $this, 'check_permission' ),
		) );
	}

	// ---- Reads ----

	public function get_record( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || $this->post_type() !== $post->post_type ) {
			return new \WP_REST_Response( array( 'message' => __( 'Record not found.', 'agency-manager' ) ), 404 );
		}

		return new \WP_REST_Response( $this->serialize( $post ) );
	}

	public function get_options(): \WP_REST_Response {
		$type = $this->post_type();
		$taxonomies = array();
		foreach ( $this->taxonomies() as $taxonomy ) {
			$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
			$taxonomies[ $taxonomy ] = is_wp_error( $terms ) ? array() : array_map(
				fn( $t ) => array( 'id' => $t->term_id, 'name' => $t->name ),
				$terms
			);
		}

		$custom_fields = array();
		foreach ( Settings::get_custom_fields( $type ) as $key => $custom ) {
			$custom_fields[] = array( 'key' => $key, 'label' => $custom['label'] ?? $key, 'type' => $custom['type'] ?? 'text' );
		}

		return new \WP_REST_Response( array(
			'taxonomies'   => $taxonomies,
			'customFields' => $custom_fields,
			'mappedFields' => $this->mapped_fields( $type ),
		) );
	}

	public function get_preview( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || $this->post_type() !== $post->post_type ) {
			return new \WP_REST_Response( array( 'message' => __( 'Record not found.', 'agency-manager' ) ), 404 );
		}

		$html = 'talent' === $this->post_type()
			? Card_Renderer::render_talent_card( $post_id )
			: Card_Renderer::render_location_card( $post_id );

		return new \WP_REST_Response( array(
			'html'        => $html,
			'themeCssUrl' => get_stylesheet_uri(),
			'pluginCssUrl' => AM_PLUGIN_URL . 'assets/css/frontend.css',
		) );
	}

	// ---- Writes ----

	public function create( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = $this->insert_from_payload( (array) $request->get_json_params() );

		if ( ! $post_id ) {
			return new \WP_REST_Response( array( 'message' => __( 'Could not create the record.', 'agency-manager' ) ), 500 );
		}

		return new \WP_REST_Response( array( 'id' => $post_id ), 201 );
	}

	public function update( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || $this->post_type() !== $post->post_type ) {
			return new \WP_REST_Response( array( 'message' => __( 'Record not found.', 'agency-manager' ) ), 404 );
		}

		$this->apply_payload( $post_id, (array) $request->get_json_params() );

		return new \WP_REST_Response( array( 'id' => $post_id ) );
	}

	/**
	 * The same create-record logic the React editor's "Save" POST uses,
	 * exposed for non-REST callers (the CSV importer — see
	 * Csv_Import\Importer) so an imported record is written through the
	 * exact same code path as one created by hand, not a second copy of the
	 * field-writing logic.
	 *
	 * @param array $body Same shape as the REST create() JSON body.
	 * @return int New post ID, or 0 on failure.
	 */
	public function insert_from_payload( array $body ): int {
		$post_id = wp_insert_post( array(
			'post_type'    => $this->post_type(),
			'post_title'   => sanitize_text_field( (string) ( $body['title'] ?? '' ) ) ?: __( 'Untitled', 'agency-manager' ),
			'post_content' => wp_kses_post( (string) ( $body['description'] ?? '' ) ),
			'post_status'  => $this->sanitize_status( $body['status'] ?? 'draft' ),
		) );

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return 0;
		}

		$this->write_fields( $post_id, $body );

		return $post_id;
	}

	/**
	 * The same update-record logic the React editor's "Save" PUT uses,
	 * exposed for non-REST callers — see insert_from_payload() doc.
	 *
	 * @param array $body Same shape as the REST update() JSON body; only
	 *                    keys actually present are written (see write_fields()).
	 */
	public function apply_payload( int $post_id, array $body ): void {
		$post = get_post( $post_id );
		if ( ! $post || $this->post_type() !== $post->post_type ) {
			return;
		}

		$title       = array_key_exists( 'title', $body ) ? sanitize_text_field( (string) $body['title'] ) : $post->post_title;
		$description = array_key_exists( 'description', $body ) ? wp_kses_post( (string) $body['description'] ) : $post->post_content;
		$status      = array_key_exists( 'status', $body ) ? $this->sanitize_status( $body['status'] ) : $post->post_status;

		if ( $title !== $post->post_title || $description !== $post->post_content || $status !== $post->post_status ) {
			wp_update_post( array( 'ID' => $post_id, 'post_title' => $title, 'post_content' => $description, 'post_status' => $status ) );
		}

		$this->write_fields( $post_id, $body );
	}

	/** Public accessor for the post type this controller manages (used by the CSV importer to pick the right controller instance). */
	public function get_post_type(): string {
		return $this->post_type();
	}

	/** Public accessor for this type's meta field spec (used by the CSV importer's field list / validation). */
	public function get_meta_fields(): array {
		return $this->meta_fields();
	}

	/** Public accessor for this type's taxonomy slugs (used by the CSV importer). */
	public function get_taxonomies(): array {
		return $this->taxonomies();
	}

	/** Reads the effective (Meta_Resolver-aware) value of one field on an existing record — used by the CSV importer's "preserve unmapped fields" duplicate-update logic. */
	public function get_effective_value( int $post_id, string $key ): string {
		return Meta_Resolver::get( $post_id, $this->post_type(), $key );
	}

	public function delete( \WP_REST_Request $request ): \WP_REST_Response {
		$post_id = (int) $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post || $this->post_type() !== $post->post_type ) {
			return new \WP_REST_Response( array( 'message' => __( 'Record not found.', 'agency-manager' ) ), 404 );
		}

		// Trash, not force-delete — recoverable, matches the Forms/Import-Export delete convention elsewhere in this plugin.
		wp_trash_post( $post_id );

		return new \WP_REST_Response( array( 'deleted' => true ) );
	}

	/**
	 * Writes every editable field EXCEPT title/description/status (handled
	 * by the caller). Every meta/taxonomy/thumbnail write is diff-checked
	 * against the record's current *effective* value (via Meta_Resolver for
	 * meta) so re-saving without changes performs zero writes — critical on
	 * a site where existing records' real data lives under a theme's own
	 * meta prefix and must never be silently duplicated into `_am_*`.
	 */
	private function write_fields( int $post_id, array $body ): void {
		$type = $this->post_type();

		// Featured image.
		if ( array_key_exists( 'thumbnailId', $body ) ) {
			$new_thumb = absint( $body['thumbnailId'] );
			$current_thumb = (int) get_post_thumbnail_id( $post_id );
			if ( $new_thumb !== $current_thumb ) {
				if ( $new_thumb ) {
					set_post_thumbnail( $post_id, $new_thumb );
				} else {
					delete_post_thumbnail( $post_id );
				}
			}
		}

		// Gallery (stored as comma-separated attachment IDs under _am_gallery_ids, same shape Meta_Boxes/Field_Mapper already use).
		if ( array_key_exists( 'galleryIds', $body ) ) {
			$new_ids = implode( ',', array_map( 'absint', (array) $body['galleryIds'] ) );
			$current = Meta_Resolver::get( $post_id, $type, 'gallery_ids' );
			if ( $new_ids !== $current ) {
				update_post_meta( $post_id, '_am_gallery_ids', $new_ids );
			}
		}

		// Flags.
		foreach ( array( 'featured', 'active', 'homepage' ) as $flag ) {
			if ( array_key_exists( $flag, $body ) ) {
				$new_val = ! empty( $body[ $flag ] ) ? 1 : 0;
				$current_raw = get_post_meta( $post_id, "_am_{$flag}", true );
				// Active defaults to "on" when no meta exists yet (matches Meta_Boxes/Query's own default).
				$current = '' === $current_raw ? ( 'active' === $flag ? 1 : 0 ) : (int) $current_raw;
				if ( $new_val !== $current ) {
					update_post_meta( $post_id, "_am_{$flag}", $new_val );
				}
			}
		}

		// Type-specific meta fields.
		$meta_in = (array) ( $body['meta'] ?? array() );
		foreach ( $this->meta_fields() as $key => $spec ) {
			if ( ! array_key_exists( $key, $meta_in ) ) {
				continue;
			}
			$new_value = $this->sanitize_meta_value( $meta_in[ $key ], $spec );
			$current   = Meta_Resolver::get( $post_id, $type, $key );
			if ( $new_value !== $current ) {
				update_post_meta( $post_id, "_am_{$key}", $new_value );
			}
		}

		// Custom fields (Settings::get_custom_fields) — same _am_custom_{key} convention as Field_Mapper::apply().
		$custom_in = (array) ( $body['customFields'] ?? array() );
		foreach ( Settings::get_custom_fields( $type ) as $key => $custom ) {
			if ( ! array_key_exists( $key, $custom_in ) ) {
				continue;
			}
			$new_value = sanitize_textarea_field( (string) $custom_in[ $key ] );
			$current   = (string) get_post_meta( $post_id, "_am_custom_{$key}", true );
			if ( $new_value !== $current ) {
				update_post_meta( $post_id, "_am_custom_{$key}", $new_value );
			}
		}

		// Taxonomies.
		$terms_in = (array) ( $body['terms'] ?? array() );
		foreach ( $this->taxonomies() as $taxonomy ) {
			if ( ! array_key_exists( $taxonomy, $terms_in ) ) {
				continue;
			}
			$new_ids     = array_values( array_unique( array_map( 'absint', (array) $terms_in[ $taxonomy ] ) ) );
			$current_ids = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
			$current_ids = is_wp_error( $current_ids ) ? array() : array_map( 'intval', $current_ids );
			sort( $new_ids );
			sort( $current_ids );
			if ( $new_ids !== $current_ids ) {
				wp_set_object_terms( $post_id, $new_ids, $taxonomy );
			}
		}

	}

	private function sanitize_status( $status ): string {
		$status = sanitize_key( (string) $status );
		return in_array( $status, array( 'publish', 'draft', 'pending', 'private' ), true ) ? $status : 'draft';
	}

	/**
	 * Matches Cpt\Meta_Boxes::save_talent()/save_location() exactly: select
	 * fields (availability, parking, power, body_type) are sanitized as
	 * plain text, not validated against the option list. This is
	 * deliberate, not an oversight — on this site those fields' real values
	 * come from a theme's own data (e.g. "Mains Power", "Available") which
	 * don't match this plugin's lowercase option keys, and enforcing
	 * enum membership here would silently blank an existing value the
	 * moment a record is re-saved without that field being touched. The
	 * `options` list only drives the React dropdown's suggested choices.
	 *
	 * @param mixed $value
	 * @param array{type:string,options?:array<string,string>} $spec
	 */
	private function sanitize_meta_value( $value, array $spec ): string {
		switch ( $spec['type'] ) {
			case 'textarea':
				return sanitize_textarea_field( (string) $value );
			case 'url':
				return esc_url_raw( (string) $value );
			default:
				return sanitize_text_field( (string) $value );
		}
	}

	/**
	 * Resolved (effective) field values for the edit form — same
	 * Meta_Resolver fallback logic the frontend cards already use, so an
	 * existing record whose data lives under a theme's own meta prefix
	 * (e.g. Eden Cast's `_ec_*`) shows correctly instead of blank.
	 */
	private function serialize( \WP_Post $post ): array {
		$type = $this->post_type();

		$meta = array();
		foreach ( $this->meta_fields() as $key => $spec ) {
			$meta[ $key ] = Meta_Resolver::get( $post->ID, $type, $key );
		}

		$custom_fields = array();
		foreach ( Settings::get_custom_fields( $type ) as $key => $custom ) {
			$custom_fields[ $key ] = (string) get_post_meta( $post->ID, "_am_custom_{$key}", true );
		}

		$gallery_raw = Meta_Resolver::get( $post->ID, $type, 'gallery_ids' );
		$gallery_ids = $gallery_raw ? array_values( array_filter( array_map( 'absint', explode( ',', $gallery_raw ) ) ) ) : array();

		$terms = array();
		foreach ( $this->taxonomies() as $taxonomy ) {
			$ids = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
			$terms[ $taxonomy ] = is_wp_error( $ids ) ? array() : array_map( 'intval', $ids );
		}

		$active_raw = get_post_meta( $post->ID, '_am_active', true );

		return array(
			'id'           => $post->ID,
			'title'        => $post->post_title,
			'description'  => $post->post_content,
			'status'       => $post->post_status,
			'thumbnailId'  => (int) get_post_thumbnail_id( $post->ID ),
			'thumbnailUrl' => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: '',
			'galleryIds'   => $gallery_ids,
			'featured'     => '1' === get_post_meta( $post->ID, '_am_featured', true ),
			'homepage'     => '1' === get_post_meta( $post->ID, '_am_homepage', true ),
			'active'       => '' === $active_raw || '1' === $active_raw,
			'terms'        => $terms,
			'meta'         => $meta,
			'customFields' => $custom_fields,
			'mappedFields' => $this->mapped_fields( $type ),
			'viewUrl'      => 'auto-draft' === $post->post_status ? '' : (string) get_permalink( $post->ID ),
			'editedAt'     => get_the_modified_date( '', $post ),
		);
	}

	/**
	 * Every form of this type whose field mappings target a meta field on
	 * this profile — purely informational (which forms feed which fields),
	 * read straight from the existing Form Builder field definitions, no
	 * new storage.
	 *
	 * @return array<string,array<int,array{formTitle:string,fieldLabel:string}>> meta field key => sources.
	 */
	private function mapped_fields( string $type ): array {
		$forms = get_posts( array(
			'post_type'   => 'am_form',
			'post_status' => 'any',
			'numberposts' => -1,
			'meta_key'    => '_am_form_type',
			'meta_value'  => $type,
		) );

		$renderer = new Form_Renderer();
		$out      = array();

		foreach ( $forms as $form ) {
			foreach ( $renderer->get_fields( $form->ID ) as $field ) {
				$mapping = $field['mapping'] ?? null;
				if ( ! is_array( $mapping ) || 'meta' !== ( $mapping['target_kind'] ?? '' ) ) {
					continue;
				}
				$key = (string) ( $mapping['target_key'] ?? '' );
				if ( '' === $key ) {
					continue;
				}
				$out[ $key ][] = array( 'formTitle' => $form->post_title, 'fieldLabel' => $field['label'] ?? $field['key'] );
			}
		}

		return $out;
	}
}
