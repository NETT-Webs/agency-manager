<?php
namespace AgencyManager\Csv_Import;

use AgencyManager\Rest\Location_Rest_Controller;
use AgencyManager\Rest\Talent_Rest_Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes one batch of CSV rows — either as a dry-run (`preview()`,
 * validates and detects duplicates, writes nothing) or for real
 * (`run()`, creates/updates/skips through the exact same
 * Rest\Talent_Rest_Controller / Location_Rest_Controller#insert_from_payload()
 * / #apply_payload() the React Talent/Location editor's own Save button
 * uses — an imported record is written by the identical code path as one
 * created by hand, not a second import-specific writer).
 */
class Importer {

	private const BATCH_SIZE = 25;

	public static function batch_size(): int {
		return self::BATCH_SIZE;
	}

	/** @return array{rows:array,summary:array} */
	public static function preview( array $session, int $offset, int $limit ): array {
		$rows = self::read_batch( $session, $offset, $limit );
		$out  = array();

		foreach ( $rows as $i => $row ) {
			$resolved   = Row_Resolver::resolve( $row, $session['columnMap'], $session['type'] );
			$match_value = self::match_value( $resolved['payload'], $session['options']['matchField'] ?? 'email', $row, $session['columnMap'] );
			$existing_id = empty( $resolved['errors'] )
				? Duplicate_Matcher::find( $session['type'], $session['options']['matchField'] ?? 'email', $match_value )
				: 0;

			$status = ! empty( $resolved['errors'] ) ? 'error' : ( ! empty( $resolved['warnings'] ) ? 'warning' : 'ok' );

			$out[] = array(
				'row'        => $offset + $i + 1,
				'name'       => $resolved['payload']['title'] ?? '',
				'status'     => $status,
				'errors'     => $resolved['errors'],
				'warnings'   => $resolved['warnings'],
				'existingId' => $existing_id,
				'preview'    => array(
					'title' => $resolved['payload']['title'] ?? '',
					'city'  => $resolved['payload']['meta']['city'] ?? '',
					'terms' => $resolved['payload']['terms'] ?? array(),
				),
			);
		}

		return array( 'rows' => $out );
	}

	/** @return array{results:array,created:int,updated:int,skipped:int,errors:int} */
	public static function run( array $session, int $offset, int $limit ): array {
		$rows       = self::read_batch( $session, $offset, $limit );
		$controller = 'talent' === $session['type'] ? new Talent_Rest_Controller() : new Location_Rest_Controller();
		$options    = $session['options'];

		$results = array();
		$counts  = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0 );

		foreach ( $rows as $i => $row ) {
			$row_number = $offset + $i + 1;
			$resolved   = Row_Resolver::resolve( $row, $session['columnMap'], $session['type'] );

			if ( ! empty( $resolved['errors'] ) ) {
				$results[] = array( 'row' => $row_number, 'name' => $resolved['payload']['title'] ?? '', 'action' => 'error', 'status' => 'error', 'reason' => implode( ' ', $resolved['errors'] ) );
				++$counts['errors'];
				continue;
			}

			$payload     = $resolved['payload'];
			$match_value = self::match_value( $payload, $options['matchField'] ?? 'email', $row, $session['columnMap'] );
			$existing_id = Duplicate_Matcher::find( $session['type'], $options['matchField'] ?? 'email', $match_value );

			$warnings = $resolved['warnings'];

			// Taxonomies: resolve names -> IDs, optionally creating missing terms.
			foreach ( (array) ( $payload['terms'] ?? array() ) as $taxonomy => $names ) {
				$term_result = Term_Resolver::resolve( $taxonomy, $names, ! empty( $options['createTerms'] ) );
				$payload['terms'][ $taxonomy ] = $term_result['ids'];
				foreach ( $term_result['missing'] as $missing_name ) {
					$warnings[] = sprintf( /* translators: %s: term name */ __( 'Unknown term (not created): "%s"', 'agency-manager' ), $missing_name );
				}
			}

			// Images (only if enabled — never for an update where clearBlanks-style behavior isn't requested).
			if ( ! empty( $options['importImages'] ) ) {
				if ( ! empty( $payload['_featuredImageUrl'] ) ) {
					$temp_id = $existing_id ?: 0;
					$image   = Image_Sideloader::sideload( $payload['_featuredImageUrl'], $temp_id );
					if ( $image['id'] ) {
						$payload['thumbnailId'] = $image['id'];
					} else {
						$warnings[] = sprintf( /* translators: %s: error message */ __( 'Featured image could not be imported: %s', 'agency-manager' ), $image['error'] );
					}
				}
				if ( ! empty( $payload['_galleryUrls'] ) ) {
					$gallery_ids = array();
					foreach ( $payload['_galleryUrls'] as $url ) {
						$image = Image_Sideloader::sideload( $url, $existing_id ?: 0 );
						if ( $image['id'] ) {
							$gallery_ids[] = $image['id'];
						} else {
							$warnings[] = sprintf( /* translators: %s: error message */ __( 'Gallery image could not be imported: %s', 'agency-manager' ), $image['error'] );
						}
					}
					if ( $gallery_ids ) {
						$payload['galleryIds'] = $gallery_ids;
					}
				}
			}
			unset( $payload['_featuredImageUrl'], $payload['_galleryUrls'] );

			if ( $existing_id ) {
				if ( 'skip' === $options['duplicateMode'] ) {
					$results[] = array( 'row' => $row_number, 'name' => $payload['title'] ?? '', 'action' => 'skipped', 'status' => 'warning', 'reason' => __( 'Matched an existing record; skipped by import settings.', 'agency-manager' ) );
					++$counts['skipped'];
					continue;
				}

				if ( 'update' === $options['duplicateMode'] ) {
					// Only mapped fields are in $payload at all — Profile_Rest_Controller::apply_payload() only ever touches keys present in the body, so unmapped fields (Instagram, Gallery, Skills, ...) are never overwritten. See the class doc.
					$controller->apply_payload( $existing_id, $payload );
					$results[] = array( 'row' => $row_number, 'name' => $payload['title'] ?? '', 'action' => 'updated', 'status' => $warnings ? 'warning' : 'ok', 'reason' => implode( ' ', $warnings ), 'postId' => $existing_id );
					++$counts['updated'];
					continue;
				}
				// 'create' duplicate mode falls through to create a new record even though a match was found — an explicit user choice.
			}

			$payload['status'] = 'draft';
			$new_id = $controller->insert_from_payload( $payload );

			if ( ! $new_id ) {
				$results[] = array( 'row' => $row_number, 'name' => $payload['title'] ?? '', 'action' => 'error', 'status' => 'error', 'reason' => __( 'Could not create the record.', 'agency-manager' ) );
				++$counts['errors'];
				continue;
			}

			$results[] = array( 'row' => $row_number, 'name' => $payload['title'] ?? '', 'action' => 'created', 'status' => $warnings ? 'warning' : 'ok', 'reason' => implode( ' ', $warnings ), 'postId' => $new_id );
			++$counts['created'];
		}

		return array_merge( array( 'results' => $results ), $counts );
	}

	private static function read_batch( array $session, int $offset, int $limit ): array {
		return Csv_Parser::read_rows( $session['file'], $session['delimiter'], $session['header'], $session['rowOffsets'], $offset, $limit );
	}

	/** Which raw CSV value to feed Duplicate_Matcher, based on the chosen match strategy. */
	private static function match_value( array $payload, string $match_field, array $row, array $column_map ): string {
		if ( 'email' === $match_field ) {
			return (string) ( $payload['meta']['contact_email'] ?? '' );
		}
		if ( 'title' === $match_field ) {
			return (string) ( $payload['title'] ?? '' );
		}
		if ( 'id' === $match_field ) {
			// A CSV column mapped specifically to "Don't import" but chosen as the ID match column isn't representable via the normal target list, so 'id' matching uses whichever mapped column name literally equals "id"/"ID" if present — a pragmatic default rather than a dedicated pseudo-target.
			foreach ( $column_map as $csv_column => $target_key ) {
				if ( 'id' === strtolower( trim( $csv_column ) ) ) {
					return (string) ( $row[ $csv_column ] ?? '' );
				}
			}
		}
		return '';
	}
}
