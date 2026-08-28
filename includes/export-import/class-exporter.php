<?php
namespace AgencyManager\Export_Import;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Exporter {

	public function register(): void {
		add_action( 'admin_post_am_export', array( $this, 'handle_export_request' ) );
	}

	public function handle_export_request(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'agency-manager' ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'am_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'agency-manager' ) );
		}

		$requested = isset( $_GET['sections'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_GET['sections'] ) ) : array();
		$sections  = $requested ? array_values( array_intersect( $requested, Schema::SECTIONS ) ) : Schema::SECTIONS;

		$payload = Schema::envelope( $this->build_sections( $sections ) );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="agency-manager-export-' . gmdate( 'Y-m-d' ) . '.json"' );

		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	public function build_sections( array $sections ): array {
		$data = array();

		foreach ( $sections as $section ) {
			switch ( $section ) {
				case 'categories':
					$data['categories'] = $this->export_terms( 'talent_category' );
					break;
				case 'groups':
					$data['groups'] = $this->export_terms( 'talent_group' );
					break;
				case 'location_types':
					$data['location_types'] = $this->export_terms( 'location_type' );
					break;
				case 'talent':
					$data['talent'] = $this->export_posts( 'talent', array( 'talent_group', 'talent_category' ) );
					break;
				case 'locations':
					$data['locations'] = $this->export_posts( 'location', array( 'location_type' ) );
					break;
				case 'forms':
					$data['forms'] = $this->export_forms();
					break;
				case 'display_settings':
					$data['display_settings'] = array(
						'display'     => Settings::get( 'display' ),
						'placeholder' => Settings::get( 'placeholder' ),
					);
					break;
				case 'homepage_settings':
					$data['homepage_settings'] = Settings::get( 'homepage' );
					break;
				case 'plugin_settings':
					$data['plugin_settings'] = array(
						'agency_type'        => Settings::get( 'agency_type' ),
						'notification_email' => Settings::get( 'notification_email' ),
					);
					break;
				case 'widget_style_presets':
					$data['widget_style_presets'] = Settings::get_widget_style_presets();
					break;
			}
		}

		if ( ! empty( $data['talent'] ) || ! empty( $data['locations'] ) ) {
			$data['media_references'] = $this->build_media_manifest( $data );
		}

		return $data;
	}

	private function export_terms( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$out = array();
		foreach ( $terms as $term ) {
			$parent_slug = '';
			if ( $term->parent ) {
				$parent = get_term( $term->parent, $taxonomy );
				$parent_slug = ( $parent && ! is_wp_error( $parent ) ) ? $parent->slug : '';
			}

			$entry = array(
				'slug'        => $term->slug,
				'name'        => $term->name,
				'description' => $term->description,
				'parent_slug' => $parent_slug,
			);

			if ( in_array( $taxonomy, array( 'talent_group', 'location_type' ), true ) ) {
				$image_id       = (int) get_term_meta( $term->term_id, 'am_group_image_id', true );
				$entry['meta']  = array(
					'image_url'   => $image_id ? wp_get_attachment_url( $image_id ) : '',
					'button_text' => get_term_meta( $term->term_id, 'am_group_button_text', true ),
					'button_url'  => get_term_meta( $term->term_id, 'am_group_button_url', true ),
				);
			}

			$out[] = $entry;
		}

		return $out;
	}

	private function export_posts( string $post_type, array $taxonomies ): array {
		$posts = get_posts(
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'numberposts' => -1,
			)
		);

		$out = array();

		foreach ( $posts as $post ) {
			$meta = array();
			foreach ( get_post_meta( $post->ID ) as $key => $values ) {
				if ( 0 !== strpos( $key, '_am_' ) || '_am_gallery_ids' === $key ) {
					continue; // Gallery handled below as resolved image URLs, not raw IDs.
				}
				$meta[ $key ] = maybe_unserialize( $values[0] );
			}

			$taxonomies_out = array();
			foreach ( $taxonomies as $taxonomy ) {
				$terms = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'slugs' ) );
				if ( ! is_wp_error( $terms ) ) {
					$taxonomies_out[ $taxonomy ] = $terms;
				}
			}

			$gallery_ids = get_post_meta( $post->ID, '_am_gallery_ids', true );
			$gallery_ids = $gallery_ids ? array_filter( array_map( 'intval', array_map( 'trim', explode( ',', $gallery_ids ) ) ) ) : array();

			$out[] = array(
				'slug'           => $post->post_name,
				'title'          => $post->post_title,
				'status'         => $post->post_status,
				'excerpt'        => $post->post_excerpt,
				'content'        => $post->post_content,
				'featured_image' => $this->image_ref( (int) get_post_thumbnail_id( $post->ID ) ),
				'gallery_images' => array_values( array_filter( array_map( array( $this, 'image_ref' ), $gallery_ids ) ) ),
				'meta'           => $meta,
				'taxonomies'     => $taxonomies_out,
			);
		}

		return $out;
	}

	private function image_ref( int $attachment_id ): ?array {
		if ( ! $attachment_id ) {
			return null;
		}

		$url = wp_get_attachment_url( $attachment_id );
		if ( ! $url ) {
			return null;
		}

		return array(
			'url' => $url,
			'alt' => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		);
	}

	private function export_forms(): array {
		$forms = get_posts(
			array(
				'post_type'   => 'am_form',
				'post_status' => 'any',
				'numberposts' => -1,
			)
		);

		$out = array();
		foreach ( $forms as $form ) {
			$fields = json_decode( (string) get_post_meta( $form->ID, '_am_form_fields', true ), true );

			$out[] = array(
				'slug'   => $form->post_name,
				'title'  => $form->post_title,
				'type'   => get_post_meta( $form->ID, '_am_form_type', true ),
				'fields' => is_array( $fields ) ? $fields : array(),
			);
		}

		return $out;
	}

	/**
	 * A transparency manifest of every media URL referenced by the export —
	 * auto-attached whenever talent/locations are exported, not a separate
	 * toggleable section (there's nothing meaningful to select independently
	 * of the posts that reference it).
	 */
	private function build_media_manifest( array $data ): array {
		$urls = array();

		foreach ( array( 'talent', 'locations' ) as $key ) {
			foreach ( (array) ( $data[ $key ] ?? array() ) as $post ) {
				if ( ! empty( $post['featured_image']['url'] ) ) {
					$urls[] = $post['featured_image']['url'];
				}
				foreach ( (array) ( $post['gallery_images'] ?? array() ) as $image ) {
					if ( ! empty( $image['url'] ) ) {
						$urls[] = $image['url'];
					}
				}
			}
		}

		return array_values( array_unique( $urls ) );
	}
}
