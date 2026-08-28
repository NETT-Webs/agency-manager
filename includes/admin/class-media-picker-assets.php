<?php
namespace AgencyManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared wp.media picker enqueue for the wp-admin `.am-media-picker` UI —
 * used by both the post meta boxes (Cpt\Meta_Boxes) and the term meta
 * fields (Cpt\Term_Meta), which otherwise duplicated the same three calls.
 */
class Media_Picker_Assets {

	public static function enqueue(): void {
		wp_enqueue_media();
		wp_enqueue_script( 'am-admin-meta-boxes', AM_PLUGIN_URL . 'assets/admin/admin.js', array( 'jquery' ), AM_VERSION, true );
		wp_enqueue_style( 'am-admin', AM_PLUGIN_URL . 'assets/admin/admin.css', array(), AM_VERSION );

		wp_localize_script(
			'am-admin-meta-boxes',
			'amMediaPicker',
			array(
				'selectImagesTitle' => __( 'Select Images', 'agency-manager' ),
				'selectFileTitle'   => __( 'Select File', 'agency-manager' ),
			)
		);
	}
}
