<?php
namespace Nettalo\TalentLocationManagement\Admin;

use Nettalo\TalentLocationManagement\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Agency type, notification email, and Backup & Restore. Everything about
 * how Talent/Locations *appear* on the website (Display Mode, placeholders,
 * homepage sections) lives on the Website Display screen instead — see
 * Admin\Website_Display_Page.
 */
class Settings_Page {

	private const NONCE_ACTION = 'am_save_settings';

	public function register(): void {
		add_action( 'admin_init', array( $this, 'maybe_save' ) );
	}

	public function maybe_save(): void {
		if ( ! isset( $_POST['am_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['am_settings_nonce'] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = Settings::all();

		$settings['agency_type']        = isset( $_POST['agency_type'] ) ? sanitize_key( wp_unslash( $_POST['agency_type'] ) ) : $settings['agency_type'];
		$settings['notification_email'] = isset( $_POST['notification_email'] ) ? sanitize_email( wp_unslash( $_POST['notification_email'] ) ) : $settings['notification_email'];

		Settings::update( $settings );

		add_action( 'admin_notices', array( $this, 'render_saved_notice' ) );
	}

	public function render_saved_notice(): void {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'nettwebs-talent-location-management' ) . '</p></div>';
	}

	public function render(): void {
		$settings = Settings::all();
		?>
		<div class="wrap am-admin-page">
			<h1><?php esc_html_e( 'Settings', 'nettwebs-talent-location-management' ); ?></h1>

			<form method="post">
				<?php wp_nonce_field( self::NONCE_ACTION, 'am_settings_nonce' ); ?>

				<h2><?php esc_html_e( 'Agency', 'nettwebs-talent-location-management' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="agency_type"><?php esc_html_e( 'Agency Type', 'nettwebs-talent-location-management' ); ?></label></th>
						<td>
							<select id="agency_type" name="agency_type">
								<?php
								foreach (
									array(
										'talent'   => __( 'Talent Agency', 'nettwebs-talent-location-management' ),
										'location' => __( 'Location Agency', 'nettwebs-talent-location-management' ),
										'casting'  => __( 'Casting Agency', 'nettwebs-talent-location-management' ),
										'model'    => __( 'Model Agency', 'nettwebs-talent-location-management' ),
										'both'     => __( 'Combined Agency', 'nettwebs-talent-location-management' ),
									) as $value => $label
								) :
									?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['agency_type'], $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="notification_email"><?php esc_html_e( 'Notification Email', 'nettwebs-talent-location-management' ); ?></label></th>
						<td><input type="email" id="notification_email" class="regular-text" name="notification_email" value="<?php echo esc_attr( $settings['notification_email'] ); ?>"></td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Settings', 'nettwebs-talent-location-management' ) ); ?>
			</form>

			<hr>
			<h2><?php esc_html_e( 'Backup & Restore', 'nettwebs-talent-location-management' ); ?></h2>
			<p class="description"><?php esc_html_e( 'A one-click backup of just your Settings and Forms (not Talent/Location content) — useful before making big configuration changes.', 'nettwebs-talent-location-management' ); ?></p>
			<?php ( new Backup_Page() )->render_buttons(); ?>
		</div>
		<?php
	}
}
