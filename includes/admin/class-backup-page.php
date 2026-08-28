<?php
namespace AgencyManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One-click backup/restore of just Settings + Forms (not Talent/Location
 * content) — a thin convenience wrapper around the same admin-post actions
 * Import/Export uses (`am_export`/`am_import`), scoped to a fixed section
 * list. No duplicated export/import logic.
 */
class Backup_Page {

	private const SECTIONS = array( 'plugin_settings', 'display_settings', 'homepage_settings', 'forms' );

	public function render_buttons(): void {
		$export_url = add_query_arg(
			array( 'sections' => self::SECTIONS ),
			wp_nonce_url( admin_url( 'admin-post.php?action=am_export' ), 'am_export' )
		);
		?>
		<p>
			<a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Backup Settings', 'agency-manager' ); ?></a>
		</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="margin-top:8px;">
			<input type="hidden" name="action" value="am_import">
			<?php foreach ( self::SECTIONS as $section ) : ?>
				<input type="hidden" name="sections[]" value="<?php echo esc_attr( $section ); ?>">
			<?php endforeach; ?>
			<?php wp_nonce_field( 'am_import', 'am_import_nonce' ); ?>
			<input type="file" name="am_import_file" accept="application/json">
			<button type="submit" class="button"><?php esc_html_e( 'Restore Settings', 'agency-manager' ); ?></button>
		</form>
		<?php
	}
}
