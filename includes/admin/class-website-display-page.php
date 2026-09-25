<?php
namespace Nettalo\TalentLocationManagement\Admin;

use Nettalo\TalentLocationManagement\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One screen for everything that controls how Talent/Locations appear on
 * the website: the Display Mode switch (Hidden / Now Scouting / Live), the
 * "Now Scouting" placeholder editor, and the Homepage Control panel — per
 * type. Merges what were previously two separate screens (Display_Page and
 * the homepage half of Settings_Page) so "how the site looks" lives in one
 * place. Same underlying `Settings::all()`/`Settings::update()` calls and
 * field names as before — this is a presentation/organization merge, not a
 * data-model change.
 *
 * Deliberately does not offer any colour/typography/style selection —
 * appearance is entirely the active theme's responsibility, via the CSS
 * custom properties (--am-*) in assets/css/frontend.css (with sensible
 * plugin-provided fallbacks) and/or a full template override. Agency
 * Manager controls data, rendering, widgets, shortcodes, import/export, and
 * applications; the theme controls colours, typography, spacing, and
 * templates. The old per-homepage "Card Style" (elegant/minimal/bordered)
 * select was never actually read at render time — a dead setting from an
 * earlier iteration — and has simply been removed; the underlying
 * `homepage.{type}.card_style` data key is left alone (harmless, still
 * exported/imported for backward compatibility) but has no admin control or
 * any code path reading it.
 */
class Website_Display_Page {

	private const NONCE_ACTION = 'am_save_website_display';
	private const TYPES        = array( 'talent', 'location' );

	public function register(): void {
		add_action( 'admin_init', array( $this, 'maybe_save' ) );
	}

	public function maybe_save(): void {
		if ( ! isset( $_POST['am_website_display_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['am_website_display_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Settings::all();

		foreach ( self::TYPES as $type ) {
			// Display Mode.
			$mode                          = isset( $_POST[ "display_$type" ] ) ? sanitize_key( wp_unslash( $_POST[ "display_$type" ] ) ) : 'scouting';
			$settings['display'][ $type ]  = in_array( $mode, array( 'hidden', 'scouting', 'live' ), true ) ? $mode : 'scouting';

			// Placeholder Manager.
			$settings['placeholder'][ $type ]['badge']       = isset( $_POST[ "placeholder_{$type}_badge" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "placeholder_{$type}_badge" ] ) ) : '';
			$settings['placeholder'][ $type ]['heading']     = isset( $_POST[ "placeholder_{$type}_heading" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "placeholder_{$type}_heading" ] ) ) : '';
			$settings['placeholder'][ $type ]['description'] = isset( $_POST[ "placeholder_{$type}_description" ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ "placeholder_{$type}_description" ] ) ) : '';
			$settings['placeholder'][ $type ]['button_text'] = isset( $_POST[ "placeholder_{$type}_button_text" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "placeholder_{$type}_button_text" ] ) ) : '';
			$settings['placeholder'][ $type ]['button_link'] = isset( $_POST[ "placeholder_{$type}_button_link" ] ) ? esc_url_raw( wp_unslash( $_POST[ "placeholder_{$type}_button_link" ] ) ) : '';

			// Scouting Images: one multi-select media picker, stored as a
			// comma-separated list of attachment IDs in the same hidden
			// input the single-image picker used to populate — the JS is
			// unchanged, only data-multiple flips to "1".
			$image_ids_raw = isset( $_POST[ "placeholder_{$type}_image_ids" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "placeholder_{$type}_image_ids" ] ) ) : '';
			$image_ids     = $image_ids_raw ? array_values( array_filter( array_map( 'absint', explode( ',', $image_ids_raw ) ) ) ) : array();
			$settings['placeholder'][ $type ]['image_ids']   = $image_ids;

			$settings['placeholder'][ $type ]['count']       = isset( $_POST[ "placeholder_{$type}_count" ] ) ? max( 1, absint( $_POST[ "placeholder_{$type}_count" ] ) ) : 8;

			// Homepage Control.
			$settings['homepage'][ $type ]['heading']      = isset( $_POST[ "homepage_{$type}_heading" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "homepage_{$type}_heading" ] ) ) : '';
			$settings['homepage'][ $type ]['subheading']   = isset( $_POST[ "homepage_{$type}_subheading" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "homepage_{$type}_subheading" ] ) ) : '';
			$settings['homepage'][ $type ]['button_text']  = isset( $_POST[ "homepage_{$type}_button_text" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "homepage_{$type}_button_text" ] ) ) : '';
			$settings['homepage'][ $type ]['button_link']  = isset( $_POST[ "homepage_{$type}_button_link" ] ) ? esc_url_raw( wp_unslash( $_POST[ "homepage_{$type}_button_link" ] ) ) : '';
			$settings['homepage'][ $type ]['count']        = isset( $_POST[ "homepage_{$type}_count" ] ) ? max( 1, absint( $_POST[ "homepage_{$type}_count" ] ) ) : 4;
			$settings['homepage'][ $type ]['display_mode'] = isset( $_POST[ "homepage_{$type}_display_mode" ] ) ? sanitize_key( wp_unslash( $_POST[ "homepage_{$type}_display_mode" ] ) ) : 'inherit';
			$settings['homepage'][ $type ]['animation']    = isset( $_POST[ "homepage_{$type}_animation" ] ) ? sanitize_key( wp_unslash( $_POST[ "homepage_{$type}_animation" ] ) ) : 'none';
		}

		Settings::update( $settings );

		add_action( 'admin_notices', array( $this, 'render_saved_notice' ) );
	}

	public function render_saved_notice(): void {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Website Display settings saved.', 'nettwebs-talent-location-management' ) . '</p></div>';
	}

	public function render(): void {
		$settings = Settings::all();

		Media_Picker_Assets::enqueue();

		$type_labels = array(
			'talent'   => __( 'Talent', 'nettwebs-talent-location-management' ),
			'location' => __( 'Locations', 'nettwebs-talent-location-management' ),
		);

		$mode_labels = array(
			'hidden'   => __( 'Hidden', 'nettwebs-talent-location-management' ),
			'scouting' => __( 'Now Scouting', 'nettwebs-talent-location-management' ),
			'live'     => __( 'Live', 'nettwebs-talent-location-management' ),
		);
		?>
		<div class="wrap am-admin-page">
			<h1><?php esc_html_e( 'Website Display', 'nettwebs-talent-location-management' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Everything that controls how Talent and Locations appear on your website — Display Mode, the "Now Scouting" placeholders, and the homepage sections — all in one place. No Elementor editing required.', 'nettwebs-talent-location-management' ); ?></p>

			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION, 'am_website_display_nonce' ); ?>

				<?php foreach ( self::TYPES as $type ) : ?>
					<h2><?php echo esc_html( $type_labels[ $type ] ); ?></h2>

					<h3><?php esc_html_e( 'Display Mode', 'nettwebs-talent-location-management' ); ?></h3>
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Mode', 'nettwebs-talent-location-management' ); ?></th>
							<td>
								<?php foreach ( $mode_labels as $mode_value => $mode_label ) : ?>
									<label style="margin-right:16px;">
										<input type="radio" name="display_<?php echo esc_attr( $type ); ?>" value="<?php echo esc_attr( $mode_value ); ?>" <?php checked( $settings['display'][ $type ], $mode_value ); ?>>
										<?php echo esc_html( $mode_label ); ?>
									</label>
								<?php endforeach; ?>
							</td>
						</tr>
					</table>

					<h3><?php esc_html_e( 'Placeholder Manager ("Now Scouting" cards)', 'nettwebs-talent-location-management' ); ?></h3>
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Badge', 'nettwebs-talent-location-management' ); ?></th>
							<td><input type="text" class="regular-text" name="placeholder_<?php echo esc_attr( $type ); ?>_badge" value="<?php echo esc_attr( $settings['placeholder'][ $type ]['badge'] ); ?>"></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Title', 'nettwebs-talent-location-management' ); ?></th>
							<td><input type="text" class="regular-text" name="placeholder_<?php echo esc_attr( $type ); ?>_heading" value="<?php echo esc_attr( $settings['placeholder'][ $type ]['heading'] ); ?>"></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Description', 'nettwebs-talent-location-management' ); ?></th>
							<td><textarea class="large-text" rows="3" name="placeholder_<?php echo esc_attr( $type ); ?>_description"><?php echo esc_textarea( $settings['placeholder'][ $type ]['description'] ); ?></textarea></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Button Text', 'nettwebs-talent-location-management' ); ?></th>
							<td><input type="text" class="regular-text" name="placeholder_<?php echo esc_attr( $type ); ?>_button_text" value="<?php echo esc_attr( $settings['placeholder'][ $type ]['button_text'] ); ?>"></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Button Link', 'nettwebs-talent-location-management' ); ?></th>
							<td><input type="url" class="regular-text" name="placeholder_<?php echo esc_attr( $type ); ?>_button_link" value="<?php echo esc_attr( $settings['placeholder'][ $type ]['button_link'] ); ?>" placeholder="<?php echo esc_attr( home_url( '/contact/' ) ); ?>"></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Scouting Images', 'nettwebs-talent-location-management' ); ?></th>
							<td>
								<?php $image_ids = (array) $settings['placeholder'][ $type ]['image_ids']; ?>
								<p class="am-media-picker" data-multiple="1">
									<input type="hidden" class="am-media-ids" name="placeholder_<?php echo esc_attr( $type ); ?>_image_ids" value="<?php echo esc_attr( implode( ',', $image_ids ) ); ?>">
									<span class="am-media-preview">
										<?php foreach ( $image_ids as $image_id ) : ?>
											<span class="am-media-thumb"><?php echo wp_get_attachment_image( (int) $image_id, 'thumbnail' ); ?></span>
										<?php endforeach; ?>
									</span><br>
									<button type="button" class="button am-media-select"><?php esc_html_e( 'Select Images', 'nettwebs-talent-location-management' ); ?></button>
									<button type="button" class="button am-media-clear"><?php esc_html_e( 'Clear', 'nettwebs-talent-location-management' ); ?></button>
								</p>
								<p class="description"><?php esc_html_e( 'Select one or more images. Each placeholder card uses the next image in order, cycling back to the first once every image has been used. Leave empty to use the plain placeholder block.', 'nettwebs-talent-location-management' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Number of Placeholder Cards', 'nettwebs-talent-location-management' ); ?></th>
							<td><input type="number" min="1" max="24" name="placeholder_<?php echo esc_attr( $type ); ?>_count" value="<?php echo esc_attr( $settings['placeholder'][ $type ]['count'] ); ?>"></td>
						</tr>
					</table>

					<h3><?php esc_html_e( 'Homepage Section', 'nettwebs-talent-location-management' ); ?></h3>
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Heading', 'nettwebs-talent-location-management' ); ?></th>
							<td><input type="text" class="regular-text" name="homepage_<?php echo esc_attr( $type ); ?>_heading" value="<?php echo esc_attr( $settings['homepage'][ $type ]['heading'] ); ?>"></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Subheading', 'nettwebs-talent-location-management' ); ?></th>
							<td><input type="text" class="regular-text" name="homepage_<?php echo esc_attr( $type ); ?>_subheading" value="<?php echo esc_attr( $settings['homepage'][ $type ]['subheading'] ); ?>"></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Button Text', 'nettwebs-talent-location-management' ); ?></th>
							<td><input type="text" class="regular-text" name="homepage_<?php echo esc_attr( $type ); ?>_button_text" value="<?php echo esc_attr( $settings['homepage'][ $type ]['button_text'] ); ?>"></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Button Link', 'nettwebs-talent-location-management' ); ?></th>
							<td><input type="url" class="regular-text" name="homepage_<?php echo esc_attr( $type ); ?>_button_link" value="<?php echo esc_attr( $settings['homepage'][ $type ]['button_link'] ); ?>"></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Number of Cards', 'nettwebs-talent-location-management' ); ?></th>
							<td><input type="number" min="1" max="24" name="homepage_<?php echo esc_attr( $type ); ?>_count" value="<?php echo esc_attr( $settings['homepage'][ $type ]['count'] ); ?>"></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Display Mode', 'nettwebs-talent-location-management' ); ?></th>
							<td>
								<select name="homepage_<?php echo esc_attr( $type ); ?>_display_mode">
									<?php
									foreach (
										array(
											'inherit'  => __( 'Inherit from Display Mode above', 'nettwebs-talent-location-management' ),
											'hidden'   => __( 'Hidden', 'nettwebs-talent-location-management' ),
											'scouting' => __( 'Now Scouting', 'nettwebs-talent-location-management' ),
											'live'     => __( 'Live', 'nettwebs-talent-location-management' ),
										) as $value => $label
									) :
										?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['homepage'][ $type ]['display_mode'], $value ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Card Hover Animation', 'nettwebs-talent-location-management' ); ?></th>
							<td>
								<select name="homepage_<?php echo esc_attr( $type ); ?>_animation">
									<?php
									foreach (
										array(
											'none' => __( 'None', 'nettwebs-talent-location-management' ),
											'lift' => __( 'Lift', 'nettwebs-talent-location-management' ),
											'zoom' => __( 'Zoom', 'nettwebs-talent-location-management' ),
											'fade' => __( 'Fade', 'nettwebs-talent-location-management' ),
										) as $value => $label
									) :
										?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['homepage'][ $type ]['animation'], $value ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					</table>
					<hr>
				<?php endforeach; ?>

				<?php submit_button( __( 'Save Website Display Settings', 'nettwebs-talent-location-management' ) ); ?>
			</form>
		</div>
		<?php
	}
}
