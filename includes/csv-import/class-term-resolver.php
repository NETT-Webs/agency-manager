<?php
namespace AgencyManager\Csv_Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves CSV-supplied taxonomy term names to term IDs using WordPress's
 * own term functions (`get_term_by()` / `wp_insert_term()`) — the same
 * taxonomy system Cpt\Taxonomies registers and the Talent/Location editors
 * already manage. Never creates a duplicate: an existing term (by exact
 * name, case-insensitive) is always reused.
 */
class Term_Resolver {

	/**
	 * @param string[] $names
	 * @return array{ids:int[],missing:string[]}
	 */
	public static function resolve( string $taxonomy, array $names, bool $create_missing ): array {
		$ids     = array();
		$missing = array();

		foreach ( $names as $name ) {
			$name = trim( $name );
			if ( '' === $name ) {
				continue;
			}

			$term = get_term_by( 'name', $name, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$ids[] = (int) $term->term_id;
				continue;
			}

			if ( $create_missing ) {
				$result = wp_insert_term( $name, $taxonomy );
				if ( ! is_wp_error( $result ) ) {
					$ids[] = (int) $result['term_id'];
					continue;
				}
			}

			$missing[] = $name;
		}

		return array( 'ids' => $ids, 'missing' => $missing );
	}
}
