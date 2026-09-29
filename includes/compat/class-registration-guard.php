<?php
namespace Nettalo\TalentLocationManagement\Compat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Answers "should this plugin register X?" for every CPT/taxonomy/meta-box
 * this plugin ships. Generic checks (post_type_exists/taxonomy_exists) defer
 * to *anything* that already registered the same slug, from any source.
 *
 * should_register_meta_boxes()/should_register_term_meta() have no
 * equivalent generic exists-check (WordPress has no meta_box_exists()), and
 * two "Talent Profile Details" boxes writing the same fields would just be
 * confusing duplicate UI, not a fatal. A theme that already owns this UI can
 * opt this plugin out via the `nettalo_register_meta_boxes` /
 * `nettalo_register_term_meta` filters — no plugin code change needed for
 * any theme, including the site this plugin was originally built for, which
 * previously required a hardcoded constant check here (defined( 'EDEN_CAST_DIR' ));
 * that check is now expressed as this filter's default so nothing changes in
 * behaviour for that site without touching this class again.
 */
class Registration_Guard {

	public function should_register_post_type( string $post_type ): bool {
		return ! post_type_exists( $post_type );
	}

	public function should_register_taxonomy( string $taxonomy ): bool {
		return ! taxonomy_exists( $taxonomy );
	}

	public function should_register_meta_boxes(): bool {
		return (bool) apply_filters( 'nettalo_register_meta_boxes', ! defined( 'EDEN_CAST_DIR' ) );
	}

	public function should_register_term_meta(): bool {
		return (bool) apply_filters( 'nettalo_register_term_meta', ! defined( 'EDEN_CAST_DIR' ) );
	}
}
