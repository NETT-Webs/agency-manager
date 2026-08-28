<?php
namespace AgencyManager\Export_Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The export/import envelope and canonical section keys — shared by
 * Exporter and Importer so both always agree on the file shape.
 */
class Schema {

	public const VERSION = '1.0';

	public const SECTIONS = array(
		'categories',
		'groups',
		'location_types',
		'talent',
		'locations',
		'forms',
		'display_settings',
		'homepage_settings',
		'plugin_settings',
		'widget_style_presets',
	);

	public static function envelope( array $sections ): array {
		return array(
			'schema_version'  => self::VERSION,
			'generator'       => 'Agency Manager ' . AM_VERSION,
			'exported_at'     => gmdate( 'c' ),
			'source_site_url' => home_url(),
			'sections'        => $sections,
		);
	}
}
