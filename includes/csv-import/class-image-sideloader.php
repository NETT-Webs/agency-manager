<?php
namespace AgencyManager\Csv_Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Downloads a CSV-supplied image URL into the Media Library using
 * WordPress's own secure sideloading (`download_url()` +
 * `media_handle_sideload()` — the same functions core itself uses for
 * "insert from URL"), attached to the Talent/Location post the image
 * belongs to. Never fetches anything that isn't a plain http(s) URL, and
 * one failed image is reported back to the caller rather than thrown.
 *
 * Every sideloaded attachment is tagged with the source URL it came from
 * (`_am_source_url` meta), and re-sideloading that same URL reuses the
 * existing attachment instead of downloading and creating a new one —
 * without this, re-running an import against records whose images never
 * changed (the common "re-upload the same CSV" / "update one field" case)
 * would silently re-download every image and leave the previous
 * attachment behind as orphaned Media Library clutter on every run.
 *
 * WebP: `media_handle_sideload()` always runs completely unmodified first
 * — WordPress's normal pipeline generates the original file and every
 * registered size in the original format, exactly as it always has, so
 * the true original upload is never touched. Only *after* that succeeds
 * does `convert_sizes_to_webp()` make a second pass: it opens each
 * already-generated JPEG/PNG *sub-size* file on disk (never the
 * full-size original) with `wp_get_image_editor()`, re-encodes it as
 * WebP at the exact same dimensions, and — only if that succeeds —
 * repoints that one size's metadata entry at the new file and removes
 * the JPEG/PNG derivative it replaced (safe: a sub-size is always
 * regenerable from the original via Core's own "Regenerate Thumbnails",
 * unlike the original itself, which this never touches).
 *
 * An earlier version of this used Core's `image_editor_output_format`
 * filter instead (the mechanism Core's own opt-in WebP feature uses) —
 * but that filter applies to *every* size the editor processes,
 * including the full-size original, so it silently replaced the
 * original upload with a WebP file too. Confirmed by direct file
 * inspection during this feature's own testing, not assumed; this
 * two-pass approach was written specifically to avoid that.
 *
 * `wp_get_attachment_image()`/srcset need no changes either way — they
 * already read whatever files a size's metadata entry currently points
 * to, so Card_Renderer's existing `wp_get_attachment_image( $id,
 * 'medium' )` calls start serving WebP automatically once that entry is
 * repointed. If the active image editor can't actually encode WebP, the
 * conversion pass is skipped entirely and every size stays in its
 * original format — nothing claims an optimization that didn't happen.
 */
class Image_Sideloader {

	/** WebP quality (0-100) used when re-encoding sub-sizes — high enough that a portrait photograph doesn't visibly degrade. */
	private const WEBP_QUALITY = 82;

	/** @var bool|null cached result of the one-time capability probe. */
	private static ?bool $webp_supported = null;

	/** @var array<string,int> URL => attachment ID, so the same image URL used twice in one import run only downloads once without a database round-trip. */
	private static array $cache = array();

	/**
	 * @return array{id:int,error:string} id is 0 on failure, with error set.
	 */
	public static function sideload( string $url, int $post_id ): array {
		if ( isset( self::$cache[ $url ] ) ) {
			return array( 'id' => self::$cache[ $url ], 'error' => '' );
		}

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) || ! preg_match( '#^https?://#i', $url ) ) {
			return array( 'id' => 0, 'error' => __( 'Not a valid image URL.', 'agency-manager' ) );
		}

		$existing = self::find_existing( $url );
		if ( $existing ) {
			self::$cache[ $url ] = $existing;
			return array( 'id' => $existing, 'error' => '' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$tmp = download_url( $url, 20 );
		if ( is_wp_error( $tmp ) ) {
			return array( 'id' => 0, 'error' => $tmp->get_error_message() );
		}

		$file_array = array(
			'name'     => sanitize_file_name( basename( wp_parse_url( $url, PHP_URL_PATH ) ?: 'import-image.jpg' ) ),
			'tmp_name' => $tmp,
		);

		$type = wp_check_filetype( $file_array['name'] );
		if ( empty( $type['type'] ) || 0 !== strpos( (string) $type['type'], 'image/' ) ) {
			wp_delete_file( $tmp );
			return array( 'id' => 0, 'error' => __( 'URL did not point to a supported image file.', 'agency-manager' ) );
		}

		// Unmodified Core pipeline — original file + every registered size
		// generated in their normal (JPEG/PNG) format, exactly as always.
		$attachment_id = media_handle_sideload( $file_array, $post_id );

		if ( is_wp_error( $attachment_id ) ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return array( 'id' => 0, 'error' => $attachment_id->get_error_message() );
		}

		update_post_meta( (int) $attachment_id, '_am_source_url', $url );

		$use_webp = self::webp_supported();
		$converted = $use_webp ? self::convert_sizes_to_webp( (int) $attachment_id ) : 0;
		update_post_meta( (int) $attachment_id, '_am_webp_optimized', $converted > 0 ? '1' : '0' );

		self::$cache[ $url ] = (int) $attachment_id;

		return array( 'id' => (int) $attachment_id, 'error' => '' );
	}

	/**
	 * Second pass: re-encodes every already-generated JPEG/PNG *sub-size*
	 * of one attachment as WebP, in place of that size's original file —
	 * the full-size original referenced by `_wp_attached_file` is never
	 * opened here. Returns how many sizes were actually converted, so the
	 * caller can record whether optimization genuinely happened.
	 */
	private static function convert_sizes_to_webp( int $attachment_id ): int {
		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( empty( $meta['sizes'] ) || empty( $meta['file'] ) ) {
			return 0;
		}

		$upload_dir = wp_upload_dir();
		$base_dir   = trailingslashit( dirname( $upload_dir['basedir'] . '/' . $meta['file'] ) );
		$converted  = 0;

		// Two registered sizes commonly resolve to identical pixel
		// dimensions for a given image's aspect ratio (e.g. "large" and
		// "medium_large" both landing on 768x1024) — Core only generates
		// ONE physical file in that case and points both size entries at
		// it. Converting per-size-entry rather than per-physical-file
		// would convert+delete that shared file on the first match, then
		// find nothing on disk for the second entry and leave it pointing
		// at a file that no longer exists. So: group entries by the
		// physical filename they currently share, convert each unique
		// file exactly once, then repoint every entry that used it.
		$by_file = array();
		foreach ( $meta['sizes'] as $size_name => $size_data ) {
			if ( empty( $size_data['mime-type'] ) || ! in_array( $size_data['mime-type'], array( 'image/jpeg', 'image/png' ), true ) ) {
				continue; // Already webp (e.g. the source itself was webp), or a format we don't touch.
			}
			$by_file[ $size_data['file'] ][] = $size_name;
		}

		foreach ( $by_file as $original_filename => $size_names ) {
			$src_path = $base_dir . $original_filename;
			if ( ! file_exists( $src_path ) ) {
				continue;
			}

			$editor = wp_get_image_editor( $src_path );
			if ( is_wp_error( $editor ) ) {
				continue;
			}

			$webp_filename = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $original_filename );
			if ( ! $webp_filename || $webp_filename === $original_filename ) {
				continue;
			}
			$dest_path = $base_dir . wp_unique_filename( $base_dir, $webp_filename );

			if ( method_exists( $editor, 'set_quality' ) ) {
				$editor->set_quality( self::WEBP_QUALITY );
			}

			$saved = $editor->save( $dest_path, 'image/webp' );
			if ( is_wp_error( $saved ) || empty( $saved['path'] ) || ! file_exists( $saved['path'] ) ) {
				continue;
			}

			// One physical JPEG/PNG file replaced by one physical WebP
			// file — then every size entry that shared the original now
			// shares the new one, exactly as Core itself does for
			// same-dimension sizes.
			wp_delete_file( $src_path );

			$new_entry = array(
				'file'      => basename( $saved['path'] ),
				'width'     => (int) $saved['width'],
				'height'    => (int) $saved['height'],
				'mime-type' => 'image/webp',
				'filesize'  => file_exists( $saved['path'] ) ? filesize( $saved['path'] ) : 0,
			);
			foreach ( $size_names as $size_name ) {
				$meta['sizes'][ $size_name ] = $new_entry;
				++$converted;
			}
		}

		if ( $converted > 0 ) {
			wp_update_attachment_metadata( $attachment_id, $meta );
		}

		return $converted;
	}

	/**
	 * Whether the active WordPress image editor (GD or Imagick, whichever
	 * Core picked) can actually encode WebP on this server — probed once
	 * per request via Core's own `wp_image_editor_supports()`, never
	 * assumed. If this is false, every size is left in its original
	 * format, exactly as before this feature existed.
	 */
	public static function webp_supported(): bool {
		if ( null !== self::$webp_supported ) {
			return self::$webp_supported;
		}
		self::$webp_supported = (bool) wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) );
		return self::$webp_supported;
	}

	/** An existing attachment previously sideloaded from this exact URL, or 0. */
	private static function find_existing( string $url ): int {
		$found = get_posts( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_am_source_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $url, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		) );
		return $found ? (int) $found[0] : 0;
	}
}
