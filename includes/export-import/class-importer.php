<?php
namespace AgencyManager\Export_Import;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses an uploaded export file and creates/updates content by matching on
 * slug — never duplicating. Section import order matters: taxonomies must
 * exist before the posts that reference them by slug.
 */
class Importer {

	private const IMPORT_ORDER = array( 'categories', 'groups', 'location_types', 'talent', 'locations', 'forms', 'display_settings', 'homepage_settings', 'plugin_settings', 'widget_style_presets' );

	/** @var array<string,array<string,mixed>> */
	private array $report = array();

	public function register(): void {
		add_action( 'admin_post_am_import', array( $this, 'handle_import_request' ) );
	}

	public function handle_import_request(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'agency-manager' ) );
		}

		$referer = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=agency-manager-import-export' );

		if ( ! isset( $_POST['am_import_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['am_import_nonce'] ) ), 'am_import' ) ) {
			wp_safe_redirect( add_query_arg( 'am_import_error', 'invalid', $referer ) );
			exit;
		}

		if ( empty( $_FILES['am_import_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['am_import_file']['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( 'am_import_error', 'missing_file', $referer ) );
			exit;
		}

		$filetype = wp_check_filetype( $_FILES['am_import_file']['name'], array( 'json' => 'application/json' ) );
		if ( 'json' !== $filetype['ext'] || $_FILES['am_import_file']['size'] > 10 * MB_IN_BYTES ) {
			wp_safe_redirect( add_query_arg( 'am_import_error', 'invalid_file', $referer ) );
			exit;
		}

		$raw  = file_get_contents( $_FILES['am_import_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local tmp upload, not a remote request.
		$data = json_decode( (string) $raw, true );

		if ( ! is_array( $data ) || empty( $data['schema_version'] ) || empty( $data['sections'] ) || ! is_array( $data['sections'] ) ) {
			wp_safe_redirect( add_query_arg( 'am_import_error', 'invalid_json', $referer ) );
			exit;
		}

		$requested_sections = isset( $_POST['sections'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['sections'] ) ) : array_keys( $data['sections'] );

		$this->report = array();

		foreach ( self::IMPORT_ORDER as $section ) {
			if ( ! in_array( $section, $requested_sections, true ) || ! isset( $data['sections'][ $section ] ) ) {
				continue;
			}
			$this->import_section( $section, $data['sections'][ $section ] );
		}

		set_transient( 'am_import_report_' . get_current_user_id(), $this->report, 5 * MINUTE_IN_SECONDS );

		wp_safe_redirect( admin_url( 'admin.php?page=agency-manager-import-export&am_imported=1' ) );
		exit;
	}

	/**
	 * @param mixed $payload
	 */
	private function import_section( string $section, $payload ): void {
		/**
		 * Fires before a section of an import file is processed.
		 *
		 * @param string $section One of Schema::SECTIONS.
		 * @param mixed  $payload The raw (still unvalidated per-item) section payload.
		 */
		do_action( 'am_before_import_section', $section, $payload );

		switch ( $section ) {
			case 'categories':
				$this->import_terms( 'talent_category', $payload );
				break;
			case 'groups':
				$this->import_terms( 'talent_group', $payload );
				break;
			case 'location_types':
				$this->import_terms( 'location_type', $payload );
				break;
			case 'talent':
				$this->import_posts( 'talent', $payload, array( 'talent_group', 'talent_category' ) );
				break;
			case 'locations':
				$this->import_posts( 'location', $payload, array( 'location_type' ) );
				break;
			case 'forms':
				$this->import_forms( $payload );
				break;
			case 'display_settings':
				$this->import_settings_slice( is_array( $payload ) ? $payload : array() );
				break;
			case 'homepage_settings':
				$this->import_settings_slice( array( 'homepage' => $payload ) );
				break;
			case 'plugin_settings':
				$this->import_settings_slice( is_array( $payload ) ? $payload : array() );
				break;
			case 'widget_style_presets':
				// Upsert-by-name: array_replace_recursive() merges the
				// 'widget_style_presets' sub-array key-by-key (preset name
				// is the key), so existing presets not present in the
				// imported file are left untouched, matching presets are
				// overwritten, and new preset names are simply added —
				// exactly the same create-or-update-by-slug semantics used
				// for Talent/Locations/Categories/Groups/Forms elsewhere.
				$this->import_settings_slice( array( 'widget_style_presets' => is_array( $payload ) ? $payload : array() ) );
				break;
		}

		/**
		 * Fires after a section finishes importing — $this->report[$section]
		 * (created/updated/errors counts) is populated by this point.
		 *
		 * @param string $section
		 */
		do_action( 'am_after_import_section', $section, $this->report[ $section ] ?? array() );
	}

	private function import_terms( string $taxonomy, $items ): void {
		$counts = array(
			'created' => 0,
			'updated' => 0,
			'errors'  => 0,
		);

		foreach ( (array) $items as $item ) {
			// Malformed JSON can put a non-array value here (e.g. a bare
			// string) — (array) already wraps scalars into a single-element
			// array, so guard the element itself before indexing into it.
			if ( ! is_array( $item ) || empty( $item['slug'] ) ) {
				++$counts['errors'];
				continue;
			}

			$existing = get_term_by( 'slug', $item['slug'], $taxonomy );
			$args     = array(
				'slug'        => $item['slug'],
				'description' => $item['description'] ?? '',
			);

			if ( $existing ) {
				wp_update_term( $existing->term_id, $taxonomy, array_merge( $args, array( 'name' => $item['name'] ?? $existing->name ) ) );
				$term_id = $existing->term_id;
				++$counts['updated'];
			} else {
				$result = wp_insert_term( $item['name'] ?? $item['slug'], $taxonomy, $args );
				if ( is_wp_error( $result ) ) {
					++$counts['errors'];
					continue;
				}
				$term_id = $result['term_id'];
				++$counts['created'];
			}

			if ( ! empty( $item['meta'] ) && in_array( $taxonomy, array( 'talent_group', 'location_type' ), true ) ) {
				if ( ! empty( $item['meta']['image_url'] ) ) {
					$attachment_id = $this->sideload_image( $item['meta']['image_url'] );
					if ( $attachment_id ) {
						update_term_meta( $term_id, 'am_group_image_id', $attachment_id );
					}
				}
				if ( isset( $item['meta']['button_text'] ) ) {
					update_term_meta( $term_id, 'am_group_button_text', sanitize_text_field( $item['meta']['button_text'] ) );
				}
				if ( isset( $item['meta']['button_url'] ) ) {
					update_term_meta( $term_id, 'am_group_button_url', esc_url_raw( $item['meta']['button_url'] ) );
				}
			}
		}

		$this->report[ $taxonomy ] = $counts;
	}

	private function import_posts( string $post_type, $items, array $taxonomies ): void {
		$counts = array(
			'created' => 0,
			'updated' => 0,
			'errors'  => 0,
			'slugs'   => array(),
		);

		foreach ( (array) $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['slug'] ) ) {
				++$counts['errors'];
				continue;
			}

			$existing = get_page_by_path( $item['slug'], OBJECT, $post_type );

			$requested_status = $item['status'] ?? 'publish';
			$post_status      = in_array( $requested_status, array( 'publish', 'draft', 'pending' ), true ) ? $requested_status : 'publish';

			$post_args = array(
				'post_type'    => $post_type,
				'post_title'   => $item['title'] ?? $item['slug'],
				'post_name'    => $item['slug'],
				'post_status'  => $post_status,
				'post_excerpt' => $item['excerpt'] ?? '',
				'post_content' => $item['content'] ?? '',
			);

			$is_new = ! $existing;

			if ( $existing ) {
				$post_args['ID'] = $existing->ID;
				$post_id         = wp_update_post( $post_args, true );
			} else {
				$post_id = wp_insert_post( $post_args, true );
			}

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				++$counts['errors'];
				continue;
			}

			foreach ( (array) ( $item['meta'] ?? array() ) as $meta_key => $meta_value ) {
				if ( 0 === strpos( (string) $meta_key, '_am_' ) ) {
					update_post_meta( $post_id, $meta_key, $meta_value );
				}
			}

			foreach ( $taxonomies as $taxonomy ) {
				if ( isset( $item['taxonomies'][ $taxonomy ] ) ) {
					wp_set_object_terms( $post_id, (array) $item['taxonomies'][ $taxonomy ], $taxonomy, false );
				}
			}

			if ( ! empty( $item['featured_image']['url'] ) ) {
				$attachment_id = $this->sideload_image( $item['featured_image']['url'], $item['featured_image']['alt'] ?? '' );
				if ( $attachment_id ) {
					set_post_thumbnail( $post_id, $attachment_id );
				}
			}

			if ( ! empty( $item['gallery_images'] ) ) {
				$gallery_ids = array();
				foreach ( (array) $item['gallery_images'] as $image ) {
					if ( empty( $image['url'] ) ) {
						continue;
					}
					$attachment_id = $this->sideload_image( $image['url'], $image['alt'] ?? '' );
					if ( $attachment_id ) {
						$gallery_ids[] = $attachment_id;
					}
				}
				if ( $gallery_ids ) {
					// sideload_image() correctly dedupes by source filename, so
					// two gallery entries pointing at the same URL resolve to
					// the same attachment ID — dedupe the id list itself too,
					// or a repeated source URL would show the same image
					// multiple times in the rendered gallery.
					update_post_meta( $post_id, '_am_gallery_ids', implode( ',', array_unique( $gallery_ids ) ) );
				}
			}

			++$counts[ $is_new ? 'created' : 'updated' ];
			$counts['slugs'][] = $item['slug'];
		}

		$this->report[ $post_type ] = $counts;
	}

	private function import_forms( $items ): void {
		$counts = array(
			'created' => 0,
			'updated' => 0,
			'errors'  => 0,
		);

		foreach ( (array) $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['slug'] ) ) {
				++$counts['errors'];
				continue;
			}

			$existing = get_posts(
				array(
					'post_type'   => 'am_form',
					'name'        => $item['slug'],
					'post_status' => 'any',
					'numberposts' => 1,
				)
			);

			if ( $existing ) {
				$form_id = $existing[0]->ID;
				wp_update_post(
					array(
						'ID'         => $form_id,
						'post_title' => $item['title'] ?? $item['slug'],
					)
				);
				++$counts['updated'];
			} else {
				$form_id = wp_insert_post(
					array(
						'post_type'   => 'am_form',
						'post_title'  => $item['title'] ?? $item['slug'],
						'post_name'   => $item['slug'],
						'post_status' => 'publish',
					)
				);
				++$counts['created'];
			}

			if ( $form_id && ! is_wp_error( $form_id ) ) {
				update_post_meta( $form_id, '_am_form_type', $item['type'] ?? 'talent' );
				update_post_meta( $form_id, '_am_form_fields', wp_json_encode( $item['fields'] ?? array() ) );
			}
		}

		$this->report['forms'] = $counts;
	}

	private function import_settings_slice( array $slice ): void {
		$settings = array_replace_recursive( Settings::all(), $slice );
		Settings::update( $settings );

		$this->report['settings'] = array( 'updated' => 1 );
	}

	/**
	 * Downloads a remote image URL into this site's media library, matched
	 * by filename so a repeat import doesn't re-fetch the same file.
	 * Requires the source site to still be reachable over HTTP.
	 */
	private function sideload_image( string $url, string $alt = '' ): int {
		if ( ! $url ) {
			return 0;
		}

		$path     = wp_parse_url( $url, PHP_URL_PATH );
		$filename = $path ? sanitize_file_name( wp_basename( $path ) ) : '';

		if ( $filename ) {
			$existing = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'   => '_am_source_filename',
							'value' => $filename,
						),
					),
				)
			);

			if ( $existing ) {
				return (int) $existing[0];
			}
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_sideload_image( $url, 0, $alt, 'id' );

		if ( is_wp_error( $attachment_id ) ) {
			return 0;
		}

		if ( $filename ) {
			update_post_meta( (int) $attachment_id, '_am_source_filename', $filename );
		}

		return (int) $attachment_id;
	}

	public static function get_last_report(): array {
		$report = get_transient( 'am_import_report_' . get_current_user_id() );

		return is_array( $report ) ? $report : array();
	}
}
