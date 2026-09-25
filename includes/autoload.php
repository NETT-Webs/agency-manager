<?php
/**
 * Lightweight autoloader for the Nettalo\TalentLocationManagement\ namespace — no Composer step.
 * Maps Nettalo\TalentLocationManagement\Sub_Namespace\Class_Name to includes/sub-namespace/class-class-name.php
 * (underscores in any segment become hyphens, mirroring WordPress's own file-naming convention).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	function ( $class ) {
		$prefix = 'Nettalo\TalentLocationManagement\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$segments = explode( '\\', $relative );
		$segments = array_map(
			static function ( $segment ) {
				return strtolower( str_replace( '_', '-', $segment ) );
			},
			$segments
		);

		$file_name = 'class-' . array_pop( $segments ) . '.php';
		$sub_path  = $segments ? implode( '/', $segments ) . '/' : '';
		$path      = NETTALO_PLUGIN_DIR . 'includes/' . $sub_path . $file_name;

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);
