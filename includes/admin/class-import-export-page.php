<?php
namespace AgencyManager\Admin;

use AgencyManager\Export_Import\Importer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Import_Export_Page {

	private const SECTION_LABELS = array(
		'talent'                => 'Talent',
		'locations'             => 'Locations',
		'categories'            => 'Talent Categories',
		'groups'                => 'Talent Groups',
		'location_types'        => 'Location Categories',
		'display_settings'      => 'Website Display Settings',
		'homepage_settings'     => 'Homepage Featured Settings',
		'widget_style_presets'  => 'Widget Style Presets',
		'plugin_settings'       => 'Plugin Settings',
		'forms'                 => 'Forms',
	);

	/**
	 * Sections selected by the "Export Content" quick action. Forms and
	 * Plugin Settings are deliberately excluded — Forms are usually
	 * customised per agency (wording, branding, workflow) rather than
	 * reused across sites, so migrating them is always an explicit,
	 * separate opt-in. Widget Style Presets are included — like Display/
	 * Homepage Settings, they control appearance and are exactly what
	 * "reuse the same widget styles on another installation" needs.
	 */
	private const CONTENT_SECTIONS = array( 'talent', 'locations', 'categories', 'groups', 'location_types', 'display_settings', 'homepage_settings', 'widget_style_presets' );

	/** Sections selected by the "Export Everything" quick action: Content plus Forms and Plugin Settings. */
	private const EVERYTHING_SECTIONS = array( 'talent', 'locations', 'categories', 'groups', 'location_types', 'display_settings', 'homepage_settings', 'widget_style_presets', 'forms', 'plugin_settings' );

	private const CONTENT_KEYS  = array( 'talent', 'locations', 'categories', 'groups', 'location_types' );
	private const SETTINGS_KEYS = array( 'display_settings', 'homepage_settings', 'widget_style_presets', 'plugin_settings' );
	private const OPTIONAL_KEYS = array( 'forms' );

	public function register(): void {
		// Exporter/Importer register their own admin-post handlers directly.
	}

	public function render(): void {
		$imported     = isset( $_GET['am_imported'] );
		$import_error = isset( $_GET['am_import_error'] ) ? sanitize_key( wp_unslash( $_GET['am_import_error'] ) ) : '';
		?>
		<div class="wrap am-admin-page">
			<h1><?php esc_html_e( 'Import / Export', 'agency-manager' ); ?></h1>

			<?php if ( $imported ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Import complete — see the report below.', 'agency-manager' ); ?></p></div>
				<?php $this->render_report(); ?>
			<?php endif; ?>

			<?php if ( $import_error ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Import failed — check the file and try again.', 'agency-manager' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Export', 'agency-manager' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Move the reusable parts of this agency to another WordPress installation: Talent, Locations, their Categories/Groups, and the Display Settings that control how they appear. Elementor page layouts are never touched — the Talent/Location pages on the destination site already use the same widgets or shortcodes, so imported content appears there automatically.', 'agency-manager' ); ?></p>
			<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="am_export">
				<?php wp_nonce_field( 'am_export', '_wpnonce' ); ?>
				<?php $this->render_grouped_checkboxes( false, array() ); ?>
				<p>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Export Selected', 'agency-manager' ); ?></button>
				</p>
				<h4 style="margin-bottom:4px;"><?php esc_html_e( 'Quick Actions', 'agency-manager' ); ?></h4>
				<p>
					<button type="submit" class="button" onclick="<?php echo esc_attr( $this->check_only_js( array( 'talent' ) ) ); ?>"><?php esc_html_e( 'Export Talent', 'agency-manager' ); ?></button>
					<button type="submit" class="button" onclick="<?php echo esc_attr( $this->check_only_js( array( 'locations' ) ) ); ?>"><?php esc_html_e( 'Export Locations', 'agency-manager' ); ?></button>
					<button type="submit" class="button" onclick="<?php echo esc_attr( $this->check_only_js( self::CONTENT_SECTIONS ) ); ?>"><?php esc_html_e( 'Export Content', 'agency-manager' ); ?></button>
					<button type="submit" class="button button-secondary" onclick="<?php echo esc_attr( $this->check_only_js( self::EVERYTHING_SECTIONS ) ); ?>"><?php esc_html_e( 'Export Everything', 'agency-manager' ); ?></button>
				</p>
				<p class="description">
					<?php esc_html_e( 'Export Talent / Export Locations select only that one section. Export Content selects Talent, Locations, Talent Categories, Talent Groups, Location Categories, and Website Display + Homepage Featured Settings. Export Everything additionally includes Forms and Plugin Settings. Each button submits immediately — no manual ticking required.', 'agency-manager' ); ?>
				</p>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Import', 'agency-manager' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Upload the same JSON file. Existing Talent, Locations, Categories, Groups, and Forms are matched by slug and updated in place — nothing is duplicated.', 'agency-manager' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="am_import">
				<?php wp_nonce_field( 'am_import', 'am_import_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="am_import_file"><?php esc_html_e( 'JSON File', 'agency-manager' ); ?></label></th>
						<td><input type="file" id="am_import_file" name="am_import_file" accept="application/json"></td>
					</tr>
				</table>
				<?php $this->render_grouped_checkboxes( true, array( 'forms' ) ); ?>
				<p class="description"><?php esc_html_e( 'Only sections actually present in the uploaded file are applied. Forms is left unticked by default since it is optional — check it only if you intend to overwrite this site\'s forms.', 'agency-manager' ); ?></p>
				<?php submit_button( __( 'Import', 'agency-manager' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Builds the inline onclick JS for a Quick Action button: ticks exactly
	 * the given section keys and unticks everything else, then lets the
	 * click continue on to submit the form.
	 *
	 * @param string[] $sections Section keys this quick action selects.
	 */
	private function check_only_js( array $sections ): string {
		return sprintf(
			"var f=this.form; f.querySelectorAll('input[type=checkbox]').forEach(function(c){c.checked=%s.includes(c.value);});",
			wp_json_encode( array_values( $sections ) )
		);
	}

	/**
	 * Renders the Content / Settings / Optional checkbox groups shared by
	 * both the Export and Import forms.
	 *
	 * @param bool     $check_by_default Whether checkboxes default to checked (import) or unchecked (export).
	 * @param string[] $exceptions       Section keys to render opposite of $check_by_default (e.g. Forms left unticked on Import since it's optional).
	 */
	private function render_grouped_checkboxes( bool $check_by_default, array $exceptions ): void {
		$exceptions = array_flip( $exceptions );

		$groups = array(
			__( 'Content', 'agency-manager' )  => self::CONTENT_KEYS,
			__( 'Settings', 'agency-manager' ) => self::SETTINGS_KEYS,
			__( 'Optional', 'agency-manager' ) => self::OPTIONAL_KEYS,
		);

		foreach ( $groups as $group_label => $keys ) {
			echo '<h4 style="margin-bottom:4px;">' . esc_html( $group_label ) . '</h4>';
			echo '<table class="form-table" style="margin-top:0;"><tr><td>';
			foreach ( $keys as $key ) {
				$label       = self::SECTION_LABELS[ $key ];
				$is_optional = in_array( $key, self::OPTIONAL_KEYS, true );
				$checked     = ( $check_by_default xor isset( $exceptions[ $key ] ) ) ? 'checked' : '';
				echo '<label style="display:block;margin-bottom:6px;">';
				echo '<input type="checkbox" name="sections[]" value="' . esc_attr( $key ) . '" ' . esc_attr( $checked ) . '> ' . esc_html( $label );
				if ( $is_optional ) {
					echo ' <span class="description">(' . esc_html__( 'optional — not included in Export Content; included in Export Everything', 'agency-manager' ) . ')</span>';
				}
				echo '</label>';
			}
			echo '</td></tr></table>';
		}
	}

	private function render_report(): void {
		$report = Importer::get_last_report();
		if ( empty( $report ) ) {
			return;
		}

		echo '<table class="widefat striped" style="max-width:640px;"><thead><tr><th>' . esc_html__( 'Section', 'agency-manager' ) . '</th><th>' . esc_html__( 'Created', 'agency-manager' ) . '</th><th>' . esc_html__( 'Updated', 'agency-manager' ) . '</th><th>' . esc_html__( 'Errors', 'agency-manager' ) . '</th></tr></thead><tbody>';

		foreach ( $report as $section => $counts ) {
			echo '<tr>';
			echo '<td>' . esc_html( self::SECTION_LABELS[ $section ] ?? $section ) . '</td>';
			echo '<td>' . esc_html( (string) ( $counts['created'] ?? '—' ) ) . '</td>';
			echo '<td>' . esc_html( (string) ( $counts['updated'] ?? '—' ) ) . '</td>';
			echo '<td>' . esc_html( (string) ( $counts['errors'] ?? 0 ) ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}
}
