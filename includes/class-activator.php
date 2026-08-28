<?php
namespace AgencyManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs once on activation. WordPress fires `init` (and therefore CPT
 * registration) before the `activate_{plugin}` hook within the same admin
 * request, so `am_form` already exists as a post type by the time this runs.
 */
class Activator {

	public static function activate(): void {
		if ( false === get_option( 'am_settings' ) ) {
			update_option( 'am_settings', Settings::defaults() );
		}

		self::seed_default_forms();

		set_transient( 'am_activation_redirect', 1, 30 );

		flush_rewrite_rules();
	}

	private static function seed_default_forms(): void {
		$forms = array(
			'talent-application'  => array(
				'title'  => __( 'Talent Application', 'agency-manager' ),
				'type'   => 'talent',
				'fields' => array(
					array(
						'key'      => 'full_name',
						'label'    => __( 'Full Name', 'agency-manager' ),
						'type'     => 'text',
						'required' => true,
						'order'    => 1,
					),
					array(
						'key'      => 'email',
						'label'    => __( 'Email', 'agency-manager' ),
						'type'     => 'email',
						'required' => true,
						'order'    => 2,
					),
					array(
						'key'      => 'phone',
						'label'    => __( 'Phone', 'agency-manager' ),
						'type'     => 'tel',
						'required' => false,
						'order'    => 3,
					),
					array(
						'key'      => 'city',
						'label'    => __( 'City', 'agency-manager' ),
						'type'     => 'text',
						'required' => false,
						'order'    => 4,
					),
					array(
						'key'      => 'message',
						'label'    => __( 'Tell us about yourself', 'agency-manager' ),
						'type'     => 'textarea',
						'required' => false,
						'order'    => 5,
					),
					array(
						'key'      => 'photo',
						'label'    => __( 'Photo', 'agency-manager' ),
						'type'     => 'image',
						'required' => false,
						'order'    => 6,
					),
				),
			),
			'location-submission' => array(
				'title'  => __( 'Location Submission', 'agency-manager' ),
				'type'   => 'location',
				'fields' => array(
					array(
						'key'      => 'location_name',
						'label'    => __( 'Location Name', 'agency-manager' ),
						'type'     => 'text',
						'required' => true,
						'order'    => 1,
					),
					array(
						'key'      => 'contact_name',
						'label'    => __( 'Contact Name', 'agency-manager' ),
						'type'     => 'text',
						'required' => true,
						'order'    => 2,
					),
					array(
						'key'      => 'email',
						'label'    => __( 'Email', 'agency-manager' ),
						'type'     => 'email',
						'required' => true,
						'order'    => 3,
					),
					array(
						'key'      => 'phone',
						'label'    => __( 'Phone', 'agency-manager' ),
						'type'     => 'tel',
						'required' => false,
						'order'    => 4,
					),
					array(
						'key'      => 'city',
						'label'    => __( 'City', 'agency-manager' ),
						'type'     => 'text',
						'required' => false,
						'order'    => 5,
					),
					array(
						'key'      => 'description',
						'label'    => __( 'Describe the location', 'agency-manager' ),
						'type'     => 'textarea',
						'required' => false,
						'order'    => 6,
					),
					array(
						'key'      => 'photo',
						'label'    => __( 'Photo', 'agency-manager' ),
						'type'     => 'image',
						'required' => false,
						'order'    => 7,
					),
				),
			),
		);

		foreach ( $forms as $slug => $form ) {
			$existing = get_posts(
				array(
					'post_type'      => 'am_form',
					'name'           => $slug,
					'post_status'    => 'any',
					'numberposts'    => 1,
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);

			if ( ! empty( $existing ) ) {
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'   => 'am_form',
					'post_title'  => $form['title'],
					'post_name'   => $slug,
					'post_status' => 'publish',
				)
			);

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, '_am_form_type', $form['type'] );
				update_post_meta( $post_id, '_am_form_fields', wp_json_encode( $form['fields'] ) );
			}
		}
	}
}
