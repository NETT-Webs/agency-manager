<?php
namespace AgencyManager\Csv_Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Streaming CSV reader — never loads the whole file into memory or into a
 * single PHP request. `index()` makes one pass over the file to record the
 * header, detect the delimiter, and remember the byte offset where each
 * data row starts; every later batch request (preview, import) then
 * `fseek()`s straight to the row it needs instead of re-scanning from the
 * top, which is what keeps a 10,000-row import from turning into an
 * effectively-quadratic pile of work across its batches.
 */
class Csv_Parser {

	private const MAX_INDEX_ROWS = 50000;

	/**
	 * @return array{delimiter:string,header:array<int,string>,rowOffsets:array<int,int>,rowCount:int}
	 */
	public static function index( string $path ): array {
		$handle = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		if ( ! $handle ) {
			return array( 'delimiter' => ',', 'header' => array(), 'rowOffsets' => array(), 'rowCount' => 0 );
		}

		// BOM: detect and skip so the first header cell isn't corrupted.
		$bom = fread( $handle, 3 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
		if ( "\xEF\xBB\xBF" !== $bom ) {
			fseek( $handle, 0 );
		}

		$delimiter = self::detect_delimiter( $handle );

		$header = fgetcsv( $handle, 0, $delimiter );
		$header = is_array( $header ) ? array_map( array( __CLASS__, 'clean_cell' ), $header ) : array();

		$offsets = array();
		while ( count( $offsets ) < self::MAX_INDEX_ROWS && ! feof( $handle ) ) {
			$pos = ftell( $handle );
			$row = fgetcsv( $handle, 0, $delimiter );
			if ( false === $row || null === $row ) {
				break;
			}
			// Skip fully-blank lines (fgetcsv returns [null] for them) rather than counting them as data rows.
			if ( 1 === count( $row ) && null === $row[0] ) {
				continue;
			}
			$offsets[] = $pos;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		return array(
			'delimiter'  => $delimiter,
			'header'     => $header,
			'rowOffsets' => $offsets,
			'rowCount'   => count( $offsets ),
		);
	}

	/**
	 * @param int[] $row_offsets From index()['rowOffsets'].
	 * @return array<int,array<string,string>> Zero-indexed batch, each row keyed by header column name.
	 */
	public static function read_rows( string $path, string $delimiter, array $header, array $row_offsets, int $offset, int $limit ): array {
		$slice = array_slice( $row_offsets, $offset, $limit );
		if ( empty( $slice ) ) {
			return array();
		}

		$handle = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		if ( ! $handle ) {
			return array();
		}

		$rows = array();
		foreach ( $slice as $byte_offset ) {
			fseek( $handle, $byte_offset );
			$raw = fgetcsv( $handle, 0, $delimiter );
			if ( ! is_array( $raw ) ) {
				continue;
			}
			$row = array();
			foreach ( $header as $i => $col_name ) {
				$row[ $col_name ] = self::clean_cell( $raw[ $i ] ?? '' );
			}
			$rows[] = $row;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		return $rows;
	}

	/** Reads the first N raw rows without needing a prior index() call — used by the upload endpoint for the initial column/preview response. */
	public static function peek( string $path, int $rows = 5 ): array {
		$indexed = self::index( $path );
		$sample  = self::read_rows( $path, $indexed['delimiter'], $indexed['header'], $indexed['rowOffsets'], 0, $rows );
		return array(
			'header'    => $indexed['header'],
			'delimiter' => $indexed['delimiter'],
			'rowCount'  => $indexed['rowCount'],
			'sample'    => $sample,
		);
	}

	/** Comma vs semicolon — whichever appears more often across a small sample of lines, a reasonable heuristic that covers the common European semicolon-delimited export case. */
	private static function detect_delimiter( $handle ) {
		$start = ftell( $handle );
		$sample = fread( $handle, 8192 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fread
		fseek( $handle, $start );

		$comma_count     = substr_count( $sample, ',' );
		$semicolon_count = substr_count( $sample, ';' );

		return $semicolon_count > $comma_count ? ';' : ',';
	}

	private static function clean_cell( $value ): string {
		$value = null === $value ? '' : (string) $value;
		// Strip a stray UTF-8 BOM that can end up on an individual cell in some spreadsheet exports, and normalize to a valid UTF-8 string.
		$value = str_replace( "\xEF\xBB\xBF", '', $value );
		if ( ! mb_check_encoding( $value, 'UTF-8' ) ) {
			$value = mb_convert_encoding( $value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252' );
		}
		return trim( $value );
	}
}
