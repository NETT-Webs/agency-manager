<?php
namespace AgencyManager\Admin;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dashboard_Page {

	public function register(): void {
		// No hooks of its own — menu wiring lives in Admin::add_menu().
	}

	public function render(): void {
		$activity = $this->get_recent_activity();
		?>
		<div class="wrap am-admin-page">
			<h1><?php esc_html_e( 'Agency Manager', 'agency-manager' ); ?></h1>

			<div class="am-dashboard-tiles">
				<?php foreach ( $this->get_tiles() as $tile ) : ?>
					<div class="am-dashboard-tile">
						<span class="am-dashboard-tile__value"><?php echo esc_html( $tile['value'] ); ?></span>
						<span class="am-dashboard-tile__label"><?php echo esc_html( $tile['label'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="am-dashboard-columns">
				<div>
					<h2><?php esc_html_e( 'Recent Activity', 'agency-manager' ); ?></h2>
					<ul class="am-dashboard-activity">
						<?php if ( empty( $activity ) ) : ?>
							<li><?php esc_html_e( 'Nothing yet.', 'agency-manager' ); ?></li>
						<?php else : ?>
							<?php foreach ( $activity as $item ) : ?>
								<li>
									<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
									<span class="am-dashboard-activity__meta"><?php echo esc_html( $item['type'] ); ?> &middot; <?php echo esc_html( $item['date'] ); ?></span>
								</li>
							<?php endforeach; ?>
						<?php endif; ?>
					</ul>
				</div>

				<div>
					<h2><?php esc_html_e( 'Quick Actions', 'agency-manager' ); ?></h2>
					<ul class="am-dashboard-quick-actions">
						<li><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=talent' ) ); ?>">+ <?php esc_html_e( 'Add Talent', 'agency-manager' ); ?></a></li>
						<li><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=location' ) ); ?>">+ <?php esc_html_e( 'Add Location', 'agency-manager' ); ?></a></li>
						<li><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=agency-manager-applications' ) ); ?>">+ <?php esc_html_e( 'View Applications', 'agency-manager' ); ?></a></li>
						<li><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=agency-manager-display' ) ); ?>">+ <?php esc_html_e( 'Website Display', 'agency-manager' ); ?></a></li>
					</ul>
				</div>
			</div>

			<h2><?php esc_html_e( 'Shortcodes', 'agency-manager' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Every shortcode available in Agency Manager, grouped by section. Click Copy, then paste into any Elementor Text/Shortcode widget, block editor Shortcode block, or classic editor.', 'agency-manager' ); ?></p>
			<?php Shortcode_Reference::render_panel(); ?>
		</div>
		<?php
	}

	/**
	 * Kept for reference/fallback — Dashboard_Data is now the single source
	 * of truth these counts come from (also used by the REST endpoint the
	 * new React dashboard reads).
	 */
	private function get_tiles(): array {
		$stats = Dashboard_Data::get_stats();

		return array(
			array( 'label' => __( 'Total Talent', 'agency-manager' ), 'value' => $stats['talent']['total'] ),
			array( 'label' => __( 'Featured Talent', 'agency-manager' ), 'value' => $stats['talent']['featured'] ),
			array( 'label' => __( 'Active Talent', 'agency-manager' ), 'value' => $stats['talent']['active'] ),
			array( 'label' => __( 'Talent Display Mode', 'agency-manager' ), 'value' => ucfirst( $stats['talent']['display_mode'] ) ),
			array( 'label' => __( 'Total Locations', 'agency-manager' ), 'value' => $stats['locations']['total'] ),
			array( 'label' => __( 'Featured Locations', 'agency-manager' ), 'value' => $stats['locations']['featured'] ),
			array( 'label' => __( 'Active Locations', 'agency-manager' ), 'value' => $stats['locations']['active'] ),
			array( 'label' => __( 'Locations Display Mode', 'agency-manager' ), 'value' => ucfirst( $stats['locations']['display_mode'] ) ),
			array( 'label' => __( 'Pending Applications', 'agency-manager' ), 'value' => $stats['applications']['pending'] ),
			array( 'label' => __( 'Approved Applications', 'agency-manager' ), 'value' => $stats['applications']['approved'] ),
		);
	}

	private function get_recent_activity(): array {
		$labels = array(
			'talent'        => __( 'Talent', 'agency-manager' ),
			'location'      => __( 'Location', 'agency-manager' ),
			'am_submission' => __( 'Application', 'agency-manager' ),
		);

		return array_map(
			static function ( $item ) use ( $labels ) {
				$item['type'] = $labels[ $item['type'] ] ?? $item['type'];
				return $item;
			},
			Dashboard_Data::get_recent_activity()
		);
	}
}
