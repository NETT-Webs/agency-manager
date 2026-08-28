<?php
namespace AgencyManager\Wizard;

use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * First-run onboarding: Agency Type -> Starter Content -> Done. Redirects
 * here once after activation (the Activator sets a short-lived transient —
 * WooCommerce-onboarding-style). Deliberately does not attempt to
 * auto-generate Elementor layouts — shortcode-embedded pages are more
 * robust across themes and match the plugin's own "don't hardcode Elementor
 * layouts" principle; the ten Elementor widgets are simply available,
 * ready to drag in, for anyone who wants builder-level control instead.
 */
class Setup_Wizard {

	private const PAGE_SLUG = 'agency-manager-setup';

	public function register(): void {
		add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
		add_action( 'admin_menu', array( $this, 'add_hidden_page' ) );
		add_action( 'admin_init', array( $this, 'maybe_handle_step' ) );
	}

	public function maybe_redirect(): void {
		if ( ! get_transient( 'am_activation_redirect' ) ) {
			return;
		}

		delete_transient( 'am_activation_redirect' );

		if ( wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	public function add_hidden_page(): void {
		// A null parent hides this from every menu while still registering
		// a valid admin.php?page=... route — the standard way to add a
		// URL-only admin screen in WordPress.
		add_submenu_page(
			null,
			__( 'Agency Manager Setup', 'agency-manager' ),
			__( 'Setup', 'agency-manager' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render' )
		);
	}

	public function maybe_handle_step(): void {
		if ( ! isset( $_POST['am_wizard_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['am_wizard_nonce'] ) ), 'am_wizard_step' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$step = isset( $_POST['am_wizard_step'] ) ? sanitize_key( wp_unslash( $_POST['am_wizard_step'] ) ) : '';

		if ( 'agency_type' === $step ) {
			$this->save_agency_type();
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&step=content' ) );
			exit;
		}

		if ( 'content' === $step ) {
			$this->create_starter_content();
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&step=done' ) );
			exit;
		}
	}

	private function save_agency_type(): void {
		$type = isset( $_POST['agency_type'] ) ? sanitize_key( wp_unslash( $_POST['agency_type'] ) ) : 'both';
		$type = in_array( $type, array( 'talent', 'location', 'casting', 'model', 'both' ), true ) ? $type : 'both';

		$settings                = Settings::all();
		$settings['agency_type'] = $type;

		// Hide the section the site owner said they don't need.
		if ( 'location' === $type ) {
			$settings['display']['talent'] = 'hidden';
		} elseif ( in_array( $type, array( 'talent', 'casting', 'model' ), true ) ) {
			$settings['display']['location'] = 'hidden';
		}

		Settings::update( $settings );
	}

	private function create_starter_content(): void {
		$created_pages = array();

		if ( ! empty( $_POST['create_talent_page'] ) ) {
			$created_pages[] = $this->create_page_if_missing( __( 'Talent', 'agency-manager' ), 'talent-roster', '[talent_grid]' );
		}
		if ( ! empty( $_POST['create_location_page'] ) ) {
			$created_pages[] = $this->create_page_if_missing( __( 'Locations', 'agency-manager' ), 'location-portfolio', '[location_grid]' );
		}
		if ( ! empty( $_POST['create_talent_form_page'] ) ) {
			$created_pages[] = $this->create_page_if_missing( __( 'Apply as Talent', 'agency-manager' ), 'apply-as-talent', '[talent_application_form]' );
		}
		if ( ! empty( $_POST['create_location_form_page'] ) ) {
			$created_pages[] = $this->create_page_if_missing( __( 'Register Your Location', 'agency-manager' ), 'register-your-location', '[location_submission_form]' );
		}

		$this->maybe_add_to_menu( array_filter( $created_pages ) );
	}

	private function create_page_if_missing( string $title, string $slug, string $content ): int {
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			return (int) $existing->ID;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_content' => $content,
			)
		);

		return ( $post_id && ! is_wp_error( $post_id ) ) ? (int) $post_id : 0;
	}

	private function maybe_add_to_menu( array $page_ids ): void {
		if ( empty( $page_ids ) ) {
			return;
		}

		$locations = get_nav_menu_locations();
		$menu_id   = 0;

		if ( ! empty( $locations ) ) {
			$first_location = reset( $locations );
			if ( $first_location ) {
				$menu_id = (int) $first_location;
			}
		}

		if ( ! $menu_id ) {
			return; // No menu assigned to a theme location — leave navigation alone rather than guessing.
		}

		$existing_object_ids = array();
		foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
			if ( 'post_type' === $item->type ) {
				$existing_object_ids[] = (int) $item->object_id;
			}
		}

		foreach ( $page_ids as $page_id ) {
			if ( in_array( $page_id, $existing_object_ids, true ) ) {
				continue;
			}

			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => get_the_title( $page_id ),
					'menu-item-object-id' => $page_id,
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
	}

	public function render(): void {
		$step = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : 'agency_type';
		?>
		<div class="am-wizard-wrap">
			<h1><?php esc_html_e( 'Welcome to Agency Manager', 'agency-manager' ); ?></h1>
			<div class="am-wizard-steps">
				<span class="<?php echo 'agency_type' === $step ? 'is-current' : ''; ?>">1. <?php esc_html_e( 'Agency Type', 'agency-manager' ); ?></span>
				<span class="<?php echo 'content' === $step ? 'is-current' : ''; ?>">2. <?php esc_html_e( 'Starter Content', 'agency-manager' ); ?></span>
				<span class="<?php echo 'done' === $step ? 'is-current' : ''; ?>">3. <?php esc_html_e( 'Done', 'agency-manager' ); ?></span>
			</div>

			<?php if ( 'content' === $step ) : ?>
				<?php $this->render_content_step(); ?>
			<?php elseif ( 'done' === $step ) : ?>
				<?php $this->render_done_step(); ?>
			<?php else : ?>
				<?php $this->render_agency_type_step(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_agency_type_step(): void {
		$types = array(
			'talent'   => __( 'Talent Agency', 'agency-manager' ),
			'location' => __( 'Location Agency', 'agency-manager' ),
			'casting'  => __( 'Casting Agency', 'agency-manager' ),
			'model'    => __( 'Model Agency', 'agency-manager' ),
			'both'     => __( 'Combined Agency', 'agency-manager' ),
		);
		?>
		<form method="post">
			<?php wp_nonce_field( 'am_wizard_step', 'am_wizard_nonce' ); ?>
			<input type="hidden" name="am_wizard_step" value="agency_type">
			<p><?php esc_html_e( 'What kind of agency is this?', 'agency-manager' ); ?></p>
			<div class="am-wizard-agency-types">
				<?php foreach ( $types as $value => $label ) : ?>
					<label>
						<input type="radio" name="agency_type" value="<?php echo esc_attr( $value ); ?>" <?php checked( 'both', $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			<?php submit_button( __( 'Continue', 'agency-manager' ) ); ?>
		</form>
		<?php
	}

	private function render_content_step(): void {
		?>
		<form method="post">
			<?php wp_nonce_field( 'am_wizard_step', 'am_wizard_nonce' ); ?>
			<input type="hidden" name="am_wizard_step" value="content">
			<p><?php esc_html_e( 'Create starter pages? Each embeds the matching shortcode, so it works immediately and respects your Display settings.', 'agency-manager' ); ?></p>
			<p><label><input type="checkbox" name="create_talent_page" value="1" checked> <?php esc_html_e( 'Talent page — [talent_grid]', 'agency-manager' ); ?></label></p>
			<p><label><input type="checkbox" name="create_location_page" value="1" checked> <?php esc_html_e( 'Locations page — [location_grid]', 'agency-manager' ); ?></label></p>
			<p><label><input type="checkbox" name="create_talent_form_page" value="1" checked> <?php esc_html_e( 'Apply as Talent page — [talent_application_form]', 'agency-manager' ); ?></label></p>
			<p><label><input type="checkbox" name="create_location_form_page" value="1" checked> <?php esc_html_e( 'Register Your Location page — [location_submission_form]', 'agency-manager' ); ?></label></p>
			<?php submit_button( __( 'Continue', 'agency-manager' ) ); ?>
		</form>
		<?php
	}

	private function render_done_step(): void {
		?>
		<p><?php esc_html_e( "You're all set. Manage everything from the Agency Manager dashboard — no code, no Elementor required.", 'agency-manager' ); ?></p>
		<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=agency-manager' ) ); ?>"><?php esc_html_e( 'Go to Dashboard', 'agency-manager' ); ?></a></p>
		<?php
	}
}
