<?php
namespace AgencyManager\Compat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Answers "should this plugin register X?" for every CPT/taxonomy/meta-box
 * this plugin ships. Generic checks (post_type_exists/taxonomy_exists) defer
 * to *anything* that already registered the same slug, from any source.
 *
 * The one theme-aware exception is should_register_meta_boxes(): a generic
 * exists-check has no equivalent for meta boxes (WordPress has no
 * meta_box_exists()), and two "Talent Profile Details" boxes writing the same
 * fields would just be confusing duplicate UI, not a fatal. EDEN_CAST_DIR is
 * that theme's own bootstrap constant (defined in its functions.php) — the
 * only Eden-Cast-specific line in this entire plugin, isolated here on
 * purpose so the rest of the codebase stays fully theme-agnostic.
 */
class Registration_Guard {

	public function should_register_post_type( string $post_type ): bool {
		return ! post_type_exists( $post_type );
	}

	public function should_register_taxonomy( string $taxonomy ): bool {
		return ! taxonomy_exists( $taxonomy );
	}

	public function should_register_meta_boxes(): bool {
		return ! defined( 'EDEN_CAST_DIR' );
	}

	public function should_register_term_meta(): bool {
		return ! defined( 'EDEN_CAST_DIR' );
	}
}
