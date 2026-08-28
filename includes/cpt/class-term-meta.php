<?php
namespace AgencyManager\Cpt;

use AgencyManager\Compat\Registration_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Card Image / Button Text / Button URL term meta for talent_group and
 * location_type terms — offered symmetrically across both taxonomies.
 */
class Term_Meta {

	private const TAXONOMIES = array( 'talent_group', 'location_type' );

	private Registration_Guard $guard;

	public function __construct( Registration_Guard $guard ) {
		$this->guard = $guard;
	}

	public function register(): void {
		// Deferred to 'init' (see the identical fix + explanation in
		// Meta_Boxes::register()) — this register() call runs before the
		// active theme's functions.php has executed, so a theme-defined
		// constant like EDEN_CAST_DIR isn't defined yet regardless of which
		// theme is active if checked here directly.
		add_action( 'init', array( $this, 'maybe_register' ), 20 );
	}

	public function maybe_register(): void {
		if ( ! $this->guard->should_register_term_meta() ) {
			return;
		}

		foreach ( self::TAXONOMIES as $taxonomy ) {
			add_action( "{$taxonomy}_add_form_fields", array( $this, 'render_add_fields' ) );
			add_action( "{$taxonomy}_edit_form_fields", array( $this, 'render_edit_fields' ) );
			add_action( "created_{$taxonomy}", array( $this, 'save' ) );
			add_action( "edited_{$taxonomy}", array( $this, 'save' ) );
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}

		\AgencyManager\Admin\Media_Picker_Assets::enqueue();
	}

	public function render_add_fields(): void {
		?>
		<div class="form-field">
			<label><?php esc_html_e( 'Card Image', 'agency-manager' ); ?></label>
			<p class="am-media-picker" data-multiple="0">
				<input type="hidden" class="am-media-ids" name="am_group_image_id" value="">
				<span class="am-media-preview"></span><br>
				<button type="button" class="button am-media-select"><?php esc_html_e( 'Select', 'agency-manager' ); ?></button>
				<button type="button" class="button am-media-clear"><?php esc_html_e( 'Clear', 'agency-manager' ); ?></button>
			</p>
		</div>
		<div class="form-field">
			<label for="am_group_button_text"><?php esc_html_e( 'Button Text', 'agency-manager' ); ?></label>
			<input type="text" name="am_group_button_text" id="am_group_button_text" value="">
		</div>
		<div class="form-field">
			<label for="am_group_button_url"><?php esc_html_e( 'Button URL', 'agency-manager' ); ?></label>
			<input type="url" name="am_group_button_url" id="am_group_button_url" value="">
		</div>
		<?php
		wp_nonce_field( 'am_save_term_meta', 'am_term_meta_nonce' );
	}

	public function render_edit_fields( \WP_Term $term ): void {
		$image_id    = get_term_meta( $term->term_id, 'am_group_image_id', true );
		$button_text = get_term_meta( $term->term_id, 'am_group_button_text', true );
		$button_url  = get_term_meta( $term->term_id, 'am_group_button_url', true );
		?>
		<tr class="form-field">
			<th><label><?php esc_html_e( 'Card Image', 'agency-manager' ); ?></label></th>
			<td>
				<p class="am-media-picker" data-multiple="0">
					<input type="hidden" class="am-media-ids" name="am_group_image_id" value="<?php echo esc_attr( $image_id ); ?>">
					<span class="am-media-preview">
						<?php if ( $image_id ) : ?>
							<span class="am-media-thumb"><?php echo wp_get_attachment_image( (int) $image_id, 'thumbnail' ); ?></span>
						<?php endif; ?>
					</span><br>
					<button type="button" class="button am-media-select"><?php esc_html_e( 'Select', 'agency-manager' ); ?></button>
					<button type="button" class="button am-media-clear"><?php esc_html_e( 'Clear', 'agency-manager' ); ?></button>
				</p>
			</td>
		</tr>
		<tr class="form-field">
			<th><label for="am_group_button_text"><?php esc_html_e( 'Button Text', 'agency-manager' ); ?></label></th>
			<td><input type="text" name="am_group_button_text" id="am_group_button_text" value="<?php echo esc_attr( $button_text ); ?>"></td>
		</tr>
		<tr class="form-field">
			<th><label for="am_group_button_url"><?php esc_html_e( 'Button URL', 'agency-manager' ); ?></label></th>
			<td><input type="url" name="am_group_button_url" id="am_group_button_url" value="<?php echo esc_attr( $button_url ); ?>"></td>
		</tr>
		<?php
		wp_nonce_field( 'am_save_term_meta', 'am_term_meta_nonce' );
	}

	public function save( int $term_id ): void {
		if ( ! isset( $_POST['am_term_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['am_term_meta_nonce'] ) ), 'am_save_term_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		if ( isset( $_POST['am_group_image_id'] ) ) {
			update_term_meta( $term_id, 'am_group_image_id', absint( $_POST['am_group_image_id'] ) );
		}

		if ( isset( $_POST['am_group_button_text'] ) ) {
			update_term_meta( $term_id, 'am_group_button_text', sanitize_text_field( wp_unslash( $_POST['am_group_button_text'] ) ) );
		}

		if ( isset( $_POST['am_group_button_url'] ) ) {
			update_term_meta( $term_id, 'am_group_button_url', esc_url_raw( wp_unslash( $_POST['am_group_button_url'] ) ) );
		}
	}
}
