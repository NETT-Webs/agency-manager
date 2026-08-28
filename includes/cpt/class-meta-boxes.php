<?php
namespace AgencyManager\Cpt;

use AgencyManager\Compat\Registration_Guard;
use AgencyManager\Frontend\Card_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post meta boxes for `talent` and `location` — a tabbed editor (General /
 * Profile / Measurements / Media / Gallery / Social Links / Visibility /
 * Preview for Talent; General / Property Details / Gallery / Facilities /
 * Availability / Map / Visibility / Preview for Location) rather than one
 * long flat table. Skipped entirely when
 * Registration_Guard::should_register_meta_boxes() is false, so the admin
 * never sees two competing "Profile" boxes.
 */
class Meta_Boxes {

	private const NONCE_ACTION = 'am_save_profile_meta';
	private const NONCE_NAME   = 'am_profile_meta_nonce';

	private Registration_Guard $guard;

	public function __construct( Registration_Guard $guard ) {
		$this->guard = $guard;
	}

	public function register(): void {
		// Deferred to 'init' (priority 20, matching the CPT guards) rather
		// than checked here: this register() call runs at plugin-load time,
		// before the active theme's functions.php has even executed — so
		// EDEN_CAST_DIR (or anything else a theme defines) genuinely isn't
		// defined yet regardless of which theme is active. Checking that
		// early always evaluated "not defined" and let this plugin's boxes
		// render alongside the theme's own, which the guard exists to
		// prevent — confirmed live on Eden Cast during verification.
		add_action( 'init', array( $this, 'maybe_register' ), 20 );
	}

	public function maybe_register(): void {
		if ( ! $this->guard->should_register_meta_boxes() ) {
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_talent', array( $this, 'save_talent' ) );
		add_action( 'save_post_location', array( $this, 'save_location' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, array( 'talent', 'location' ), true ) ) {
			return;
		}

		\AgencyManager\Admin\Media_Picker_Assets::enqueue();
		wp_enqueue_script( 'am-admin-tabs', AM_PLUGIN_URL . 'assets/admin/tabs.js', array(), AM_VERSION, true );
	}

	public function add_meta_boxes(): void {
		add_meta_box( 'am_talent_profile', __( 'Talent Profile', 'agency-manager' ), array( $this, 'render_talent_box' ), 'talent', 'normal', 'high' );
		add_meta_box( 'am_location_profile', __( 'Location Profile', 'agency-manager' ), array( $this, 'render_location_box' ), 'location', 'normal', 'high' );
	}

	// ---- Talent ----

	public function render_talent_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$this->render_tabs(
			array(
				array(
					'id'       => 'general',
					'label'    => __( 'General', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						echo '<table class="form-table am-meta-table">';
						$this->render_field_row( $post->ID, 'city', array( __( 'City', 'agency-manager' ), 'text' ) );
						$this->render_field_row( $post->ID, 'age', array( __( 'Age', 'agency-manager' ), 'text' ) );
						$this->render_field_row(
							$post->ID,
							'availability',
							array(
								__( 'Availability', 'agency-manager' ),
								'select',
								array(
									''          => __( '—', 'agency-manager' ),
									'available' => __( 'Available', 'agency-manager' ),
									'limited'   => __( 'Limited', 'agency-manager' ),
									'booked'    => __( 'Booked', 'agency-manager' ),
								),
							)
						);
						echo '</table>';
					},
				),
				array(
					'id'       => 'profile',
					'label'    => __( 'Profile', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						echo '<table class="form-table am-meta-table">';
						$this->render_field_row( $post->ID, 'languages', array( __( 'Languages (one per line)', 'agency-manager' ), 'textarea' ) );
						$this->render_field_row( $post->ID, 'skills', array( __( 'Skills (one per line)', 'agency-manager' ), 'textarea' ) );
						$this->render_field_row( $post->ID, 'experience', array( __( 'Experience (one credit per line)', 'agency-manager' ), 'textarea' ) );
						$this->render_field_row( $post->ID, 'video_url', array( __( 'Video URL', 'agency-manager' ), 'url' ) );
						echo '</table>';
					},
				),
				array(
					'id'       => 'measurements',
					'label'    => __( 'Measurements', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						echo '<table class="form-table am-meta-table">';
						$this->render_field_row( $post->ID, 'height', array( __( 'Height', 'agency-manager' ), 'text' ) );
						$this->render_field_row(
							$post->ID,
							'body_type',
							array(
								__( 'Body Type', 'agency-manager' ),
								'select',
								array(
									''              => __( '—', 'agency-manager' ),
									'straight-size' => __( 'Straight Size', 'agency-manager' ),
									'plus-size'     => __( 'Plus Size', 'agency-manager' ),
									'athletic'      => __( 'Athletic', 'agency-manager' ),
									'petite'        => __( 'Petite', 'agency-manager' ),
									'tall'          => __( 'Tall', 'agency-manager' ),
								),
							)
						);
						$this->render_field_row( $post->ID, 'hair_color', array( __( 'Hair Colour', 'agency-manager' ), 'text' ) );
						$this->render_field_row( $post->ID, 'eye_color', array( __( 'Eye Colour', 'agency-manager' ), 'text' ) );
						$this->render_field_row( $post->ID, 'measurements', array( __( 'Measurements', 'agency-manager' ), 'textarea' ) );
						echo '</table>';
					},
				),
				array(
					'id'       => 'media',
					'label'    => __( 'Media', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						echo '<p class="description">' . esc_html__( 'The main profile photo is set using the Profile Photo panel in the sidebar.', 'agency-manager' ) . '</p>';
						$this->render_media_picker_row( $post->ID, 'pdf_id', __( 'PDF (comp card / CV)', 'agency-manager' ), false );
					},
				),
				array(
					'id'       => 'gallery',
					'label'    => __( 'Gallery', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						$this->render_media_picker_row( $post->ID, 'gallery_ids', __( 'Gallery', 'agency-manager' ), true );
					},
				),
				array(
					'id'       => 'social',
					'label'    => __( 'Social Links', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						echo '<table class="form-table am-meta-table">';
						$this->render_field_row( $post->ID, 'social_instagram', array( __( 'Instagram URL', 'agency-manager' ), 'url' ) );
						$this->render_field_row( $post->ID, 'social_facebook', array( __( 'Facebook URL', 'agency-manager' ), 'url' ) );
						$this->render_field_row( $post->ID, 'social_tiktok', array( __( 'TikTok URL', 'agency-manager' ), 'url' ) );
						$this->render_field_row( $post->ID, 'social_website', array( __( 'Website / Portfolio URL', 'agency-manager' ), 'url' ) );
						echo '</table>';
					},
				),
				array(
					'id'       => 'visibility',
					'label'    => __( 'Visibility', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						$this->render_flags_box( $post );
					},
				),
				array(
					'id'       => 'preview',
					'label'    => __( 'Preview', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						$this->render_preview( $post, 'talent' );
					},
				),
			)
		);
	}

	public function save_talent( int $post_id ): void {
		if ( ! $this->can_save( $post_id ) ) {
			return;
		}

		foreach ( array( 'age', 'city', 'height', 'hair_color', 'eye_color', 'availability', 'body_type', 'pdf_id', 'gallery_ids' ) as $key ) {
			$this->save_text_meta( $post_id, $key );
		}

		foreach ( array( 'measurements', 'languages', 'skills', 'experience' ) as $key ) {
			$this->save_textarea_meta( $post_id, $key );
		}

		foreach ( array( 'video_url', 'social_instagram', 'social_facebook', 'social_tiktok', 'social_website' ) as $key ) {
			$this->save_url_meta( $post_id, $key );
		}

		$this->save_flags( $post_id );
	}

	// ---- Location ----

	public function render_location_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$this->render_tabs(
			array(
				array(
					'id'       => 'general',
					'label'    => __( 'General', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						echo '<table class="form-table am-meta-table">';
						$this->render_field_row( $post->ID, 'city', array( __( 'City / Area', 'agency-manager' ), 'text' ) );
						echo '</table>';
					},
				),
				array(
					'id'       => 'property',
					'label'    => __( 'Property Details', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						echo '<table class="form-table am-meta-table">';
						$this->render_field_row(
							$post->ID,
							'parking',
							array(
								__( 'Parking', 'agency-manager' ),
								'select',
								array(
									''          => __( '—', 'agency-manager' ),
									'available' => __( 'Available', 'agency-manager' ),
									'limited'   => __( 'Limited', 'agency-manager' ),
									'none'      => __( 'Not Available', 'agency-manager' ),
								),
							)
						);
						$this->render_field_row(
							$post->ID,
							'power',
							array(
								__( 'Power', 'agency-manager' ),
								'select',
								array(
									''          => __( '—', 'agency-manager' ),
									'mains'     => __( 'Mains Power', 'agency-manager' ),
									'generator' => __( 'Generator Required', 'agency-manager' ),
									'limited'   => __( 'Limited', 'agency-manager' ),
								),
							)
						);
						echo '</table>';
					},
				),
				array(
					'id'       => 'gallery',
					'label'    => __( 'Gallery', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						$this->render_media_picker_row( $post->ID, 'gallery_ids', __( 'Gallery', 'agency-manager' ), true );
					},
				),
				array(
					'id'       => 'facilities',
					'label'    => __( 'Facilities', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						echo '<table class="form-table am-meta-table">';
						$this->render_field_row( $post->ID, 'amenities', array( __( 'Amenities (one per line)', 'agency-manager' ), 'textarea' ) );
						echo '</table>';
					},
				),
				array(
					'id'       => 'availability',
					'label'    => __( 'Availability', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						echo '<table class="form-table am-meta-table">';
						$this->render_field_row(
							$post->ID,
							'availability',
							array(
								__( 'Availability', 'agency-manager' ),
								'select',
								array(
									''          => __( '—', 'agency-manager' ),
									'available' => __( 'Available', 'agency-manager' ),
									'booked'    => __( 'Booked', 'agency-manager' ),
									'seasonal'  => __( 'Seasonal', 'agency-manager' ),
								),
							)
						);
						echo '</table>';
					},
				),
				array(
					'id'       => 'map',
					'label'    => __( 'Map', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						echo '<table class="form-table am-meta-table">';
						$this->render_field_row( $post->ID, 'map_embed', array( __( 'Google Maps Embed URL', 'agency-manager' ), 'url' ) );
						echo '</table>';
					},
				),
				array(
					'id'       => 'visibility',
					'label'    => __( 'Visibility', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						$this->render_flags_box( $post );
					},
				),
				array(
					'id'       => 'preview',
					'label'    => __( 'Preview', 'agency-manager' ),
					'callback' => function () use ( $post ) {
						$this->render_preview( $post, 'location' );
					},
				),
			)
		);
	}

	public function save_location( int $post_id ): void {
		if ( ! $this->can_save( $post_id ) ) {
			return;
		}

		foreach ( array( 'city', 'parking', 'power', 'availability', 'gallery_ids' ) as $key ) {
			$this->save_text_meta( $post_id, $key );
		}

		$this->save_textarea_meta( $post_id, 'amenities' );
		$this->save_url_meta( $post_id, 'map_embed' );

		$this->save_flags( $post_id );
	}

	// ---- Shared: Featured / Homepage / Active ----

	public function render_flags_box( \WP_Post $post ): void {
		$featured   = (bool) get_post_meta( $post->ID, '_am_featured', true );
		$homepage   = (bool) get_post_meta( $post->ID, '_am_homepage', true );
		$active_raw = get_post_meta( $post->ID, '_am_active', true );
		// Default Active to on: existing posts predate this field and should
		// not silently vanish from queries the moment the plugin is activated.
		$active = '' === $active_raw ? true : (bool) $active_raw;
		?>
		<p>
			<label><input type="checkbox" name="am_featured" value="1" <?php checked( $featured ); ?>> <?php esc_html_e( 'Featured', 'agency-manager' ); ?></label>
		</p>
		<p>
			<label><input type="checkbox" name="am_homepage" value="1" <?php checked( $homepage ); ?>> <?php esc_html_e( 'Show on Homepage', 'agency-manager' ); ?></label>
		</p>
		<p>
			<label><input type="checkbox" name="am_active" value="1" <?php checked( $active ); ?>> <?php esc_html_e( 'Active', 'agency-manager' ); ?></label>
		</p>
		<?php
	}

	private function save_flags( int $post_id ): void {
		update_post_meta( $post_id, '_am_featured', isset( $_POST['am_featured'] ) ? 1 : 0 );
		update_post_meta( $post_id, '_am_homepage', isset( $_POST['am_homepage'] ) ? 1 : 0 );
		update_post_meta( $post_id, '_am_active', isset( $_POST['am_active'] ) ? 1 : 0 );
	}

	// ---- Preview ----

	/**
	 * Static, server-rendered preview reusing the exact same template the
	 * frontend uses (Frontend\Card_Renderer) — a single source of truth, no
	 * duplicated markup. Reflects the last-saved state only; there is
	 * deliberately no live/AJAX preview in v1.
	 */
	private function render_preview( \WP_Post $post, string $type ): void {
		echo '<p class="description">' . esc_html__( 'Save or Update this entry to refresh the preview.', 'agency-manager' ) . '</p>';

		if ( 'auto-draft' === $post->post_status ) {
			echo '<p>' . esc_html__( 'This entry has not been saved yet — save a draft to see a preview.', 'agency-manager' ) . '</p>';
			return;
		}

		echo '<div class="am-meta-preview">';
		echo 'talent' === $type ? Card_Renderer::render_talent_card( $post->ID ) : Card_Renderer::render_location_card( $post->ID ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Card_Renderer/templates escape all dynamic values internally.
		echo '</div>';
	}

	// ---- Tab shell ----

	/**
	 * @param array<int,array{id:string,label:string,callback:callable}> $tabs
	 */
	private function render_tabs( array $tabs ): void {
		echo '<div class="am-tabs">';

		echo '<h2 class="nav-tab-wrapper am-tab-nav">';
		foreach ( $tabs as $i => $tab ) {
			$class = 'nav-tab am-tab-nav__link' . ( 0 === $i ? ' nav-tab-active is-active' : '' );
			echo '<a href="#" class="' . esc_attr( $class ) . '" data-tab="' . esc_attr( $tab['id'] ) . '">' . esc_html( $tab['label'] ) . '</a>';
		}
		echo '</h2>';

		foreach ( $tabs as $i => $tab ) {
			$class = 'am-tab-panel' . ( 0 === $i ? ' is-active' : '' );
			echo '<div class="' . esc_attr( $class ) . '" data-tab="' . esc_attr( $tab['id'] ) . '">';
			call_user_func( $tab['callback'] );
			echo '</div>';
		}

		echo '</div>';
	}

	// ---- Rendering helpers ----

	private function render_field_row( int $post_id, string $key, array $field ): void {
		$label = $field[0];
		$type  = $field[1];
		$value = get_post_meta( $post_id, '_am_' . $key, true );
		$name  = 'am_' . $key;

		echo '<tr><th><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';

		if ( 'select' === $type ) {
			echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '">';
			foreach ( $field[2] as $option_value => $option_label ) {
				echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'textarea' === $type ) {
			echo '<textarea id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" rows="4" class="large-text">' . esc_textarea( $value ) . '</textarea>';
		} elseif ( 'url' === $type ) {
			echo '<input type="url" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="large-text">';
		} else {
			echo '<input type="text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="large-text">';
		}

		echo '</td></tr>';
	}

	private function render_media_picker_row( int $post_id, string $key, string $label, bool $multiple ): void {
		$value = get_post_meta( $post_id, '_am_' . $key, true );
		$name  = 'am_' . $key;
		$ids   = $value ? array_filter( array_map( 'trim', explode( ',', $value ) ) ) : array();

		echo '<p class="am-media-picker" data-multiple="' . ( $multiple ? '1' : '0' ) . '">';
		echo '<label for="' . esc_attr( $name ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
		echo '<input type="hidden" class="am-media-ids" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
		echo '<span class="am-media-preview">';
		foreach ( $ids as $id ) {
			$thumb = wp_get_attachment_image( (int) $id, 'thumbnail' );
			if ( $thumb ) {
				echo '<span class="am-media-thumb">' . $thumb . '</span>';
			}
		}
		echo '</span><br>';
		echo '<button type="button" class="button am-media-select">' . esc_html__( 'Select', 'agency-manager' ) . '</button> ';
		echo '<button type="button" class="button am-media-clear">' . esc_html__( 'Clear', 'agency-manager' ) . '</button>';
		echo '</p>';
	}

	private function can_save( int $post_id ): bool {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return false;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	private function save_text_meta( int $post_id, string $key ): void {
		$name = 'am_' . $key;
		if ( isset( $_POST[ $name ] ) ) {
			update_post_meta( $post_id, '_am_' . $key, sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) );
		}
	}

	private function save_textarea_meta( int $post_id, string $key ): void {
		$name = 'am_' . $key;
		if ( isset( $_POST[ $name ] ) ) {
			update_post_meta( $post_id, '_am_' . $key, sanitize_textarea_field( wp_unslash( $_POST[ $name ] ) ) );
		}
	}

	private function save_url_meta( int $post_id, string $key ): void {
		$name = 'am_' . $key;
		if ( isset( $_POST[ $name ] ) ) {
			update_post_meta( $post_id, '_am_' . $key, esc_url_raw( wp_unslash( $_POST[ $name ] ) ) );
		}
	}
}
