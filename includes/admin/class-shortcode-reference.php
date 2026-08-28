<?php
namespace AgencyManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A single source of truth for "which shortcodes exist and what do they do,"
 * rendered as a discoverable, searchable, interactive-builder panel on the
 * Dashboard and on every admin screen its shortcodes are relevant to
 * (Talent, Locations, Applications, Forms) — so a non-technical site owner
 * never has to open documentation to find, configure, or use one.
 */
class Shortcode_Reference {

	/**
	 * Maps each real shortcode parameter to how the builder should render
	 * and label it. 'type' drives the HTML input the JS builder reads from
	 * (assets/admin/shortcode-reference.js) — adding a parameter here is the
	 * only step needed to expose it in the interactive builder.
	 */
	private const PARAM_CONTROLS = array(
		'limit'         => array( 'type' => 'number', 'label' => 'Limit' ),
		'columns'       => array( 'type' => 'select', 'label' => 'Columns', 'options' => array( '', '1', '2', '3', '4', '5', '6' ) ),
		'category'      => array( 'type' => 'text', 'label' => 'Category' ),
		'group'         => array( 'type' => 'text', 'label' => 'Group' ),
		'type'          => array( 'type' => 'text', 'label' => 'Type' ),
		'only_featured' => array( 'type' => 'checkbox', 'label' => 'Featured Only' ),
		'only_active'   => array( 'type' => 'checkbox', 'label' => 'Active Only' ),
		'order'         => array( 'type' => 'select', 'label' => 'Order By', 'options' => array( '', 'newest', 'oldest', 'random' ) ),
		'mode'          => array( 'type' => 'select', 'label' => 'Display Mode', 'options' => array( '', 'inherit', 'hidden', 'scouting', 'live' ) ),
	);

	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
		add_action( 'admin_notices', array( $this, 'maybe_render_on_list_screen' ) );
	}

	/**
	 * @return array<string,array{label:string,shortcodes:array<int,array{tag:string,example:string,description:string,when:string,params:array<int,string>}>}>
	 */
	public static function groups(): array {
		$groups = array(
			'talent'    => array(
				'label'      => __( 'Talent', 'agency-manager' ),
				'shortcodes' => array(
					array(
						'tag'         => 'talent_grid',
						'example'     => '[talent_grid]',
						'description' => __( 'Static grid of Talent cards.', 'agency-manager' ),
						'when'        => __( 'A dedicated Talent archive page, or anywhere you want every (or a filtered subset of) Talent record shown at once.', 'agency-manager' ),
						'params'      => array( 'limit', 'columns', 'category', 'group', 'only_featured', 'only_active', 'order', 'mode' ),
					),
					array(
						'tag'         => 'talent_featured',
						'example'     => '[talent_featured limit="4"]',
						'description' => __( 'Displays featured Talent cards, using the Homepage Section settings by default.', 'agency-manager' ),
						'when'        => __( 'The homepage, or any page that should highlight a small hand-picked selection rather than the full roster.', 'agency-manager' ),
						'params'      => array( 'limit', 'columns', 'category', 'group', 'only_active', 'order', 'mode' ),
					),
					array(
						'tag'         => 'talent_carousel',
						'example'     => '[talent_carousel]',
						'description' => __( 'Scrollable Talent carousel.', 'agency-manager' ),
						'when'        => __( 'A narrower page section where a full grid would take up too much space, but you still want to show several Talent at once.', 'agency-manager' ),
						'params'      => array( 'limit', 'category', 'group', 'only_featured', 'only_active', 'order', 'mode' ),
					),
					array(
						'tag'         => 'talent_slider',
						'example'     => '[talent_slider]',
						'description' => __( 'One-at-a-time Talent slider with autoplay.', 'agency-manager' ),
						'when'        => __( 'A hero/banner-style section that should showcase one Talent at a time, changing automatically.', 'agency-manager' ),
						'params'      => array( 'limit', 'category', 'group', 'only_featured', 'only_active', 'order', 'mode' ),
					),
					array(
						'tag'         => 'talent_scouting',
						'example'     => '[talent_scouting limit="3"]',
						'description' => __( 'Always shows "Now Scouting" Talent placeholder cards, regardless of the global Display Mode.', 'agency-manager' ),
						'when'        => __( 'A "coming soon" or recruitment teaser section that should always show placeholder cards, even while the rest of the site is Live.', 'agency-manager' ),
						'params'      => array( 'limit', 'columns' ),
					),
				),
			),
			'locations' => array(
				'label'      => __( 'Locations', 'agency-manager' ),
				'shortcodes' => array(
					array(
						'tag'         => 'location_grid',
						'example'     => '[location_grid]',
						'description' => __( 'Static grid of Location cards.', 'agency-manager' ),
						'when'        => __( 'A dedicated Locations archive page, or anywhere you want every (or a filtered subset of) Location shown at once.', 'agency-manager' ),
						'params'      => array( 'limit', 'columns', 'type', 'only_featured', 'only_active', 'order', 'mode' ),
					),
					array(
						'tag'         => 'location_featured',
						'example'     => '[location_featured limit="4"]',
						'description' => __( 'Displays featured Location cards, using the Homepage Section settings by default.', 'agency-manager' ),
						'when'        => __( 'The homepage, or any page that should highlight a small hand-picked selection of Locations.', 'agency-manager' ),
						'params'      => array( 'limit', 'columns', 'type', 'only_active', 'order', 'mode' ),
					),
					array(
						'tag'         => 'location_carousel',
						'example'     => '[location_carousel]',
						'description' => __( 'Scrollable Location carousel.', 'agency-manager' ),
						'when'        => __( 'A narrower page section where a full grid would take up too much space.', 'agency-manager' ),
						'params'      => array( 'limit', 'type', 'only_featured', 'only_active', 'order', 'mode' ),
					),
					array(
						'tag'         => 'location_slider',
						'example'     => '[location_slider]',
						'description' => __( 'One-at-a-time Location slider with autoplay.', 'agency-manager' ),
						'when'        => __( 'A hero/banner-style section that should showcase one Location at a time, changing automatically.', 'agency-manager' ),
						'params'      => array( 'limit', 'type', 'only_featured', 'only_active', 'order', 'mode' ),
					),
					array(
						'tag'         => 'location_scouting',
						'example'     => '[location_scouting limit="3"]',
						'description' => __( 'Always shows "Now Scouting" Location placeholder cards, regardless of the global Display Mode.', 'agency-manager' ),
						'when'        => __( 'A "coming soon" or recruitment teaser section for Locations, regardless of the site\'s current Display Mode.', 'agency-manager' ),
						'params'      => array( 'limit', 'columns' ),
					),
				),
			),
			'forms'     => array(
				'label'      => __( 'Forms', 'agency-manager' ),
				'shortcodes' => array(
					array(
						'tag'         => 'talent_application_form',
						'example'     => '[talent_application_form]',
						'description' => __( 'The public Talent application form.', 'agency-manager' ),
						'when'        => __( 'A "Join Us" / "Apply Now" page. Submissions appear on Agency Manager -> Applications (Talent tab) for review.', 'agency-manager' ),
						'params'      => array(),
					),
					array(
						'tag'         => 'location_submission_form',
						'example'     => '[location_submission_form]',
						'description' => __( 'The public Location submission form.', 'agency-manager' ),
						'when'        => __( 'A "List Your Location" / "Register" page. Submissions appear on Agency Manager -> Applications (Location tab) for review.', 'agency-manager' ),
						'params'      => array(),
					),
				),
			),
		);

		// Every form built in the Form Builder automatically gets a
		// [agency_form id="N"] entry here — no manual list to keep in sync
		// as forms are created, renamed, or deleted.
		$groups['forms']['shortcodes'] = array_merge( $groups['forms']['shortcodes'], self::form_shortcodes() );

		return $groups;
	}

	/**
	 * @return array<int,array{tag:string,example:string,description:string,when:string,params:array}>
	 */
	private static function form_shortcodes(): array {
		if ( ! post_type_exists( 'am_form' ) ) {
			return array();
		}

		$forms = get_posts(
			array(
				'post_type'   => 'am_form',
				'post_status' => 'publish',
				'numberposts' => -1,
			)
		);

		$entries = array();
		foreach ( $forms as $form ) {
			$entries[] = array(
				'tag'         => 'agency_form',
				'example'     => '[agency_form id="' . $form->ID . '"]',
				'description' => sprintf(
					/* translators: %s: form title */
					__( 'Renders the "%s" form built in the Form Builder.', 'agency-manager' ),
					$form->post_title
				),
				'when'        => __( 'Anywhere you want this specific form to appear — no shortcode attributes needed beyond id.', 'agency-manager' ),
				'params'      => array(),
			);
		}

		return $entries;
	}

	/**
	 * @return array<int,array{label:string,example:string}>
	 */
	private static function common_examples(): array {
		return array(
			array(
				'label'   => __( 'Homepage Featured Talent', 'agency-manager' ),
				'example' => '[talent_featured limit="4"]',
			),
			array(
				'label'   => __( 'Homepage Featured Locations', 'agency-manager' ),
				'example' => '[location_featured limit="4"]',
			),
			array(
				'label'   => __( 'Full Talent Page', 'agency-manager' ),
				'example' => '[talent_grid]',
			),
			array(
				'label'   => __( 'Full Location Page', 'agency-manager' ),
				'example' => '[location_grid]',
			),
			array(
				'label'   => __( 'Now Scouting Talent', 'agency-manager' ),
				'example' => '[talent_scouting limit="4"]',
			),
			array(
				'label'   => __( 'Now Scouting Locations', 'agency-manager' ),
				'example' => '[location_scouting limit="4"]',
			),
		);
	}

	public function maybe_enqueue( string $hook ): void {
		wp_enqueue_style( 'am-admin', AM_PLUGIN_URL . 'assets/admin/admin.css', array(), AM_VERSION );
		wp_enqueue_script( 'am-shortcode-reference', AM_PLUGIN_URL . 'assets/admin/shortcode-reference.js', array(), AM_VERSION, true );
		wp_localize_script(
			'am-shortcode-reference',
			'amShortcodeReference',
			array( 'copiedText' => __( 'Shortcode copied.', 'agency-manager' ) )
		);
	}

	/**
	 * Renders the panel, optionally scoped to one or more group keys
	 * ('talent'|'locations'|'forms'). Pass an empty array for every group
	 * (used on the Dashboard).
	 *
	 * @param string[] $only_groups
	 */
	public static function render_panel( array $only_groups = array(), bool $show_common_examples = true ): void {
		$groups = self::groups();

		if ( ! empty( $only_groups ) ) {
			$groups = array_intersect_key( $groups, array_flip( $only_groups ) );
		}

		if ( empty( $groups ) ) {
			return;
		}
		?>
		<div class="am-shortcode-reference">
			<p class="am-shortcode-reference__search">
				<input type="search" class="regular-text am-shortcode-search" placeholder="<?php esc_attr_e( 'Search shortcodes… e.g. "featured" or "carousel"', 'agency-manager' ); ?>">
			</p>

			<?php foreach ( $groups as $group ) : ?>
				<h4 class="am-shortcode-reference__group"><?php echo esc_html( $group['label'] ); ?></h4>
				<?php foreach ( $group['shortcodes'] as $shortcode ) : ?>
					<details class="am-shortcode-reference__item" data-search-text="<?php echo esc_attr( strtolower( $shortcode['tag'] . ' ' . $shortcode['description'] ) ); ?>">
						<summary>
							<code><?php echo esc_html( $shortcode['example'] ); ?></code>
							<button type="button" class="button button-small am-shortcode-copy" data-shortcode="<?php echo esc_attr( $shortcode['example'] ); ?>"><?php esc_html_e( 'Copy', 'agency-manager' ); ?></button>
						</summary>

						<p><strong><?php esc_html_e( 'What it does:', 'agency-manager' ); ?></strong> <?php echo esc_html( $shortcode['description'] ); ?></p>
						<p><strong><?php esc_html_e( 'When to use it:', 'agency-manager' ); ?></strong> <?php echo esc_html( $shortcode['when'] ); ?></p>

						<?php if ( ! empty( $shortcode['params'] ) ) : ?>
							<p class="am-shortcode-reference__params">
								<strong><?php esc_html_e( 'Available parameters:', 'agency-manager' ); ?></strong>
								<?php echo esc_html( implode( ', ', $shortcode['params'] ) ); ?>
							</p>

							<div class="am-shortcode-builder" data-tag="<?php echo esc_attr( $shortcode['tag'] ); ?>">
								<p class="am-shortcode-builder__label"><?php esc_html_e( 'Build it:', 'agency-manager' ); ?></p>
								<div class="am-shortcode-builder__fields">
									<?php foreach ( $shortcode['params'] as $param ) : ?>
										<?php self::render_builder_field( $param ); ?>
									<?php endforeach; ?>
								</div>
								<p class="am-shortcode-builder__output">
									<code class="am-shortcode-builder__code">[<?php echo esc_html( $shortcode['tag'] ); ?>]</code>
									<button type="button" class="button button-small am-shortcode-copy" data-shortcode="[<?php echo esc_attr( $shortcode['tag'] ); ?>]"><?php esc_html_e( 'Copy', 'agency-manager' ); ?></button>
								</p>
							</div>
						<?php endif; ?>
					</details>
				<?php endforeach; ?>
			<?php endforeach; ?>

			<p class="am-shortcode-reference__no-results" hidden><?php esc_html_e( 'No shortcodes match your search.', 'agency-manager' ); ?></p>

			<?php if ( $show_common_examples ) : ?>
				<h4 class="am-shortcode-reference__group"><?php esc_html_e( 'Common Examples', 'agency-manager' ); ?></h4>
				<table class="widefat striped am-shortcode-reference__placements">
					<tbody>
						<?php foreach ( self::common_examples() as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['label'] ); ?></td>
								<td><code><?php echo esc_html( $row['example'] ); ?></code></td>
								<td><button type="button" class="button button-small am-shortcode-copy" data-shortcode="<?php echo esc_attr( $row['example'] ); ?>"><?php esc_html_e( 'Copy', 'agency-manager' ); ?></button></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_builder_field( string $param ): void {
		$control = self::PARAM_CONTROLS[ $param ] ?? array( 'type' => 'text', 'label' => ucfirst( $param ) );
		$id      = 'am-builder-field-' . wp_unique_id();
		?>
		<label class="am-shortcode-builder__field" for="<?php echo esc_attr( $id ); ?>">
			<span><?php echo esc_html( $control['label'] ); ?></span>
			<?php if ( 'select' === $control['type'] ) : ?>
				<select id="<?php echo esc_attr( $id ); ?>" data-param="<?php echo esc_attr( $param ); ?>">
					<?php foreach ( $control['options'] as $option ) : ?>
						<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( '' === $option ? __( '(default)', 'agency-manager' ) : $option ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php elseif ( 'checkbox' === $control['type'] ) : ?>
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" data-param="<?php echo esc_attr( $param ); ?>" value="1">
			<?php elseif ( 'number' === $control['type'] ) : ?>
				<input type="number" min="1" id="<?php echo esc_attr( $id ); ?>" data-param="<?php echo esc_attr( $param ); ?>">
			<?php else : ?>
				<input type="text" id="<?php echo esc_attr( $id ); ?>" data-param="<?php echo esc_attr( $param ); ?>">
			<?php endif; ?>
		</label>
		<?php
	}

	/**
	 * Injects a "Locations" or "Talent" shortcode panel above the native
	 * WordPress post list screen for that CPT — the plugin has no custom
	 * admin page class for these screens (WordPress renders them
	 * automatically), so an admin_notices hook scoped to the exact screen ID
	 * is the standard way to add plugin UI there without a template override.
	 */
	public function maybe_render_on_list_screen(): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$group_by_screen = array(
			'edit-talent'   => 'talent',
			'edit-location' => 'locations',
		);

		if ( ! isset( $group_by_screen[ $screen->id ] ) ) {
			return;
		}

		echo '<div class="notice am-shortcode-reference-notice"><p><strong>' . esc_html__( 'Shortcodes for this section', 'agency-manager' ) . '</strong></p>';
		self::render_panel( array( $group_by_screen[ $screen->id ] ), false );
		echo '</div>';
	}
}
