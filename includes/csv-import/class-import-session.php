<?php
namespace AgencyManager\Csv_Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One CSV import session: the uploaded file (kept in a locked-down, non-
 * public uploads subdirectory — never the public Media Library, and never
 * executable) plus its parsed header/delimiter/row-offset index and the
 * user's column mapping/options, all addressed by one opaque session ID.
 * Session state lives in a transient (existing WP storage, no new DB
 * table); the file itself is deleted the moment the import finishes, is
 * cancelled, or the transient expires (6 hours).
 */
class Import_Session {

	private const TTL = 6 * HOUR_IN_SECONDS;

	public static function private_dir(): string {
		$upload = wp_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . 'agency-manager-imports';

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			// Belt-and-braces on Apache hosts; the directory is also never linked from anywhere public.
			file_put_contents( $dir . '/.htaccess', "Deny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		return $dir;
	}

	/**
	 * @param string $tmp_path  The uploaded file's PHP temp path ($_FILES[...]['tmp_name']).
	 * @param string $type      'talent'|'location'
	 */
	public static function create( string $tmp_path, string $original_name, string $type ): array {
		$id           = wp_generate_password( 24, false, false );
		$dest         = trailingslashit( self::private_dir() ) . $id . '.csv';

		if ( ! @copy( $tmp_path, $dest ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_copy
			return array();
		}

		$indexed = Csv_Parser::index( $dest );

		$session = array(
			'id'         => $id,
			'type'       => $type,
			'file'       => $dest,
			'fileName'   => sanitize_file_name( $original_name ),
			'delimiter'  => $indexed['delimiter'],
			'header'     => $indexed['header'],
			'rowOffsets' => $indexed['rowOffsets'],
			'rowCount'   => $indexed['rowCount'],
			'columnMap'  => array(),
			'options'    => array(
				'createTerms'  => true,
				'importImages' => true,
				'duplicateMode' => 'create',
				'matchField'   => 'email',
				'clearBlanks'  => false,
			),
			'createdBy'  => get_current_user_id(),
			'createdAt'  => time(),
		);

		set_transient( self::key( $id ), $session, self::TTL );

		return $session;
	}

	public static function get( string $id ): ?array {
		$session = get_transient( self::key( $id ) );
		return is_array( $session ) ? $session : null;
	}

	public static function update( string $id, array $partial ): ?array {
		$session = self::get( $id );
		if ( ! $session ) {
			return null;
		}
		$session = array_merge( $session, $partial );
		set_transient( self::key( $id ), $session, self::TTL );
		return $session;
	}

	public static function delete( string $id ): void {
		$session = self::get( $id );
		if ( $session && ! empty( $session['file'] ) && file_exists( $session['file'] ) ) {
			wp_delete_file( $session['file'] );
		}
		delete_transient( self::key( $id ) );
	}

	private static function key( string $id ): string {
		return 'am_csv_import_' . $id;
	}
}
