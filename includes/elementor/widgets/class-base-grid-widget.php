<?php
namespace AgencyManager\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use AgencyManager\Elementor\Widget_Style_Presets;
use AgencyManager\Frontend\Carousel_Renderer;
use AgencyManager\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared base for the eight grid/featured/carousel/slider widgets (talent
 * and location). Subclasses only declare identity (name/title/icon) and
 * which type/layout/featured combination they render — every control group
 * and the render() delegation to Carousel_Renderer live here once, so
 * there's one implementation instead of eight.
 *
 * Featured widgets default to "Use Homepage Settings" (Website Display ->
 * Homepage Section drives count/mode with no filters); switching that off
 * exposes the exact same Category/Group/Type, Only Featured, Sort Order,
 * Only Active, and Display Mode controls as the non-featured widgets, so a
 * Featured widget can be fully hand-configured per instance too — matching
 * what the *_featured shortcodes already support via their own attributes.
 */
abstract class Base_Grid_Widget extends Widget_Base {

	/** @return string 'talent'|'location' */
	abstract protected function get_data_type(): string;

	/** @return string 'grid'|'carousel'|'slider' */
	abstract protected function get_layout(): string;

	abstract protected function is_featured_widget(): bool;

	public function get_categories(): array {
		return array( 'agency-manager' );
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'agency-manager' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		if ( $this->is_featured_widget() ) {
			$this->add_control(
				'inherit_settings',
				array(
					'label'       => __( 'Use Homepage Settings', 'agency-manager' ),
					'type'        => Controls_Manager::SWITCHER,
					'default'     => 'yes',
					'description' => __( 'On by default so the homepage never needs Elementor edits (Agency Manager -> Settings). Switch off to override count/mode for just this widget instance.', 'agency-manager' ),
				)
			);
		}

		// Featured widgets only show count/filter/mode controls once "Use
		// Homepage Settings" is switched off — otherwise every setting is
		// deliberately controlled from Website Display -> Homepage Section.
		$count_condition = $this->is_featured_widget() ? array( 'inherit_settings!' => 'yes' ) : array();

		$this->add_control(
			'count',
			array(
				'label'     => __( 'Number of Cards', 'agency-manager' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => $this->default_count(),
				'condition' => $count_condition,
			)
		);

		if ( 'talent' === $this->get_data_type() ) {
			$this->add_control(
				'category',
				array(
					'label'       => __( 'Category Filter', 'agency-manager' ),
					'type'        => Controls_Manager::SELECT2,
					'options'     => $this->term_options( 'talent_category' ),
					'default'     => array(),
					'label_block' => true,
					'description' => __( 'Leave blank to show all categories.', 'agency-manager' ),
					'condition'   => $count_condition,
				)
			);
			$this->add_control(
				'group',
				array(
					'label'       => __( 'Group Filter', 'agency-manager' ),
					'type'        => Controls_Manager::SELECT2,
					'options'     => $this->term_options( 'talent_group' ),
					'default'     => array(),
					'label_block' => true,
					'description' => __( 'Leave blank to show all groups.', 'agency-manager' ),
					'condition'   => $count_condition,
				)
			);
		} else {
			$this->add_control(
				'type',
				array(
					'label'       => __( 'Type Filter', 'agency-manager' ),
					'type'        => Controls_Manager::SELECT2,
					'options'     => $this->term_options( 'location_type' ),
					'default'     => array(),
					'label_block' => true,
					'description' => __( 'Leave blank to show all types.', 'agency-manager' ),
					'condition'   => $count_condition,
				)
			);
		}

		$this->add_control(
			'only_featured',
			array(
				'label'        => __( 'Only Featured', 'agency-manager' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'label_on'     => __( 'Yes', 'agency-manager' ),
				'label_off'    => __( 'No', 'agency-manager' ),
				'return_value' => 'yes',
				'condition'    => $count_condition,
			)
		);

		$this->add_control(
			'order',
			array(
				'label'     => __( 'Sort Order', 'agency-manager' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'newest',
				'options'   => array(
					'newest' => __( 'Newest First', 'agency-manager' ),
					'oldest' => __( 'Oldest First', 'agency-manager' ),
					'random' => __( 'Random', 'agency-manager' ),
				),
				'condition' => $count_condition,
			)
		);

		$this->add_control(
			'only_active',
			array(
				'label'        => __( 'Only Active', 'agency-manager' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'label_on'     => __( 'Yes', 'agency-manager' ),
				'label_off'    => __( 'No', 'agency-manager' ),
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'mode',
			array(
				'label'       => __( 'Display Mode', 'agency-manager' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'inherit',
				'options'     => array(
					'inherit'  => __( 'Auto (use global Display Mode)', 'agency-manager' ),
					'hidden'   => __( 'Hidden', 'agency-manager' ),
					'scouting' => __( 'Force Now Scouting', 'agency-manager' ),
					'live'     => __( 'Live', 'agency-manager' ),
				),
				'description' => __( 'Auto follows Website Display\'s global setting for this type. Force Now Scouting always shows placeholder cards here, regardless of the global mode — for placing a scouting section anywhere on demand.', 'agency-manager' ),
				'condition'   => $count_condition,
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'layout_section',
			array(
				'label' => __( 'Layout', 'agency-manager' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		if ( 'grid' === $this->get_layout() ) {
			$this->add_responsive_control(
				'columns',
				array(
					'label'     => __( 'Columns', 'agency-manager' ),
					'type'      => Controls_Manager::SELECT,
					'default'   => '3',
					'tablet_default' => '2',
					'mobile_default' => '1',
					'options'   => array(
						'1' => '1',
						'2' => '2',
						'3' => '3',
						'4' => '4',
					),
					'selectors' => array(
						'{{WRAPPER}} .am-talent-grid, {{WRAPPER}} .am-location-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
					),
				)
			);
		}

		$this->add_control(
			'card_hover',
			array(
				'label'        => __( 'Card Hover', 'agency-manager' ),
				'type'         => Controls_Manager::SELECT,
				'default'      => 'none',
				'options'      => array(
					'none' => __( 'None', 'agency-manager' ),
					'lift' => __( 'Lift', 'agency-manager' ),
					'zoom' => __( 'Zoom', 'agency-manager' ),
					'fade' => __( 'Fade', 'agency-manager' ),
				),
				'prefix_class' => 'am-card-hover-',
			)
		);

		$this->end_controls_section();

		$this->register_style_controls();
	}

	/**
	 * Every control below is a pure Elementor `selectors`/group-control
	 * definition — Elementor compiles these into CSS scoped to
	 * `.elementor-element-{this widget's unique ID}` automatically (its
	 * generated stylesheet, or inline in the editor), so nothing here is
	 * hardcoded and nothing applies beyond this one widget instance. No
	 * corresponding render()/PHP logic is needed for any of it. Split into
	 * six Style-tab sections (Image/Card/Button/Badge/Typography/Spacing)
	 * so non-technical users can find and change appearance visually,
	 * without ever opening a CSS or PHP file. A "Reset to Theme Defaults"
	 * button (assets/elementor/reset-style-defaults.js, editor-only) restores
	 * every one of these controls on THIS widget instance back to the
	 * default values declared here — no other widget is affected, since the
	 * reset only ever writes to this element's own settings model.
	 *
	 * The "Widget Style" section above it (assets/elementor/widget-style-
	 * presets.js, editor-only) lets a saved snapshot of these same controls
	 * — a named preset — be applied to any widget instance, so one widget
	 * only ever needs to be styled once. Presets are stored server-side
	 * (Elementor\Widget_Style_Presets, one slice of am_settings) and are
	 * included in Import/Export under Settings, so another Agency Manager
	 * installation can reuse them immediately.
	 */
	private function register_style_controls(): void {
		$this->start_controls_section(
			'widget_style_preset_section',
			array(
				'label' => __( 'Widget Style', 'agency-manager' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'widget_style_preset_select',
			array(
				'label'   => __( 'Preset', 'agency-manager' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->widget_style_preset_options(),
			)
		);

		$this->add_control(
			'widget_style_preset_name',
			array(
				'label'       => __( 'Preset Name', 'agency-manager' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'e.g. Luxury Cards', 'agency-manager' ),
				'description' => __( 'Used by "Save Current" (creates or overwrites a preset with this name) and "Rename" (renames the preset selected above to this name).', 'agency-manager' ),
			)
		);

		$this->add_control(
			'widget_style_load',
			array(
				'type'  => Controls_Manager::BUTTON,
				'label' => __( 'Load', 'agency-manager' ),
				'text'  => __( 'Load Selected Preset', 'agency-manager' ),
				'event' => 'am:widget_style_load',
			)
		);

		$this->add_control(
			'widget_style_save',
			array(
				'type'  => Controls_Manager::BUTTON,
				'label' => __( 'Save', 'agency-manager' ),
				'text'  => __( 'Save Current Widget Style', 'agency-manager' ),
				'event' => 'am:widget_style_save',
			)
		);

		$this->add_control(
			'widget_style_rename',
			array(
				'type'  => Controls_Manager::BUTTON,
				'label' => __( 'Rename', 'agency-manager' ),
				'text'  => __( 'Rename Selected Preset', 'agency-manager' ),
				'event' => 'am:widget_style_rename',
			)
		);

		$this->add_control(
			'widget_style_delete',
			array(
				'type'        => Controls_Manager::BUTTON,
				'label'       => __( 'Delete', 'agency-manager' ),
				'text'        => __( 'Delete Selected Preset', 'agency-manager' ),
				'button_type' => 'danger',
				'event'       => 'am:widget_style_delete',
				'description' => __( 'Save Current / Rename / Delete affect this named preset everywhere it is used, on any widget. Load only affects the widget you are currently editing.', 'agency-manager' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_reset_section',
			array(
				'label' => __( 'Reset', 'agency-manager' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'reset_style_defaults',
			array(
				'type'        => Controls_Manager::BUTTON,
				'label'       => __( 'Reset to Theme Defaults', 'agency-manager' ),
				'text'        => __( 'Reset to Theme Defaults', 'agency-manager' ),
				'button_type' => 'default',
				'event'       => 'am:reset_style_defaults',
				'description' => __( 'Restores every Style control above on THIS widget only back to its original default. Other widgets, pages, and the theme itself are never affected.', 'agency-manager' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_image_section',
			array(
				'label' => __( 'Image', 'agency-manager' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'image_ratio',
			array(
				'label'     => __( 'Image Aspect Ratio', 'agency-manager' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '3/4',
				'options'   => array(
					'3/4' => __( 'Portrait (3:4)', 'agency-manager' ),
					'1/1' => __( 'Square (1:1)', 'agency-manager' ),
					'4/3' => __( 'Landscape (4:3)', 'agency-manager' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .am-talent-card__media, {{WRAPPER}} .am-location-card__media' => 'aspect-ratio: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'image_height_enable',
			array(
				'label'        => __( 'Custom Image Height', 'agency-manager' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'description'  => __( 'Off by default so Image Aspect Ratio (above) controls the image height. Switch on to set an exact pixel height instead.', 'agency-manager' ),
			)
		);

		$this->add_responsive_control(
			'image_height',
			array(
				'label'     => __( 'Image Height (px)', 'agency-manager' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 100, 'max' => 800 ) ),
				'default'   => array( 'size' => 320 ),
				'condition' => array( 'image_height_enable' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .am-talent-card__media, {{WRAPPER}} .am-location-card__media' => 'height: {{SIZE}}{{UNIT}}; aspect-ratio: unset;',
				),
			)
		);

		$this->add_control(
			'image_border_radius',
			array(
				'label'      => __( 'Image Border Radius', 'agency-manager' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .am-talent-card__media, {{WRAPPER}} .am-location-card__media' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'image_object_fit',
			array(
				'label'     => __( 'Object Fit', 'agency-manager' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => array(
					'cover'   => __( 'Cover', 'agency-manager' ),
					'contain' => __( 'Contain', 'agency-manager' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .am-talent-card__media img, {{WRAPPER}} .am-location-card__media img' => 'object-fit: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_card_section',
			array(
				'label' => __( 'Card', 'agency-manager' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_bg',
			array(
				'label'     => __( 'Card Background Colour', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .am-talent-card, {{WRAPPER}} .am-location-card' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_border_color',
			array(
				'label'     => __( 'Card Border Colour', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .am-talent-card, {{WRAPPER}} .am-location-card' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'card_border_width',
			array(
				'label'      => __( 'Card Border Width', 'agency-manager' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 10 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .am-talent-card, {{WRAPPER}} .am-location-card' => 'border-width: {{SIZE}}{{UNIT}}; border-style: solid;',
				),
			)
		);

		$this->add_control(
			'card_border_radius',
			array(
				'label'      => __( 'Card Border Radius', 'agency-manager' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .am-talent-card, {{WRAPPER}} .am-location-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'card_padding',
			array(
				'label'      => __( 'Card Padding', 'agency-manager' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'description' => __( 'Padding around the whole card, outside the image.', 'agency-manager' ),
				'selectors'  => array(
					'{{WRAPPER}} .am-talent-card, {{WRAPPER}} .am-location-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'card_shadow_preset',
			array(
				'label'     => __( 'Card Shadow', 'agency-manager' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''                                 => __( 'None', 'agency-manager' ),
					'0 2px 8px rgba(0,0,0,0.08)'       => __( 'Small', 'agency-manager' ),
					'0 8px 24px rgba(0,0,0,0.12)'      => __( 'Medium', 'agency-manager' ),
					'0 16px 40px rgba(0,0,0,0.18)'     => __( 'Large', 'agency-manager' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .am-talent-card, {{WRAPPER}} .am-location-card' => 'box-shadow: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_button_section',
			array(
				'label' => __( 'Button', 'agency-manager' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'button_width',
			array(
				'label'     => __( 'Button Width', 'agency-manager' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'display:inline-block;width:auto;',
				'options'   => array(
					'display:inline-block;width:auto;' => __( 'Auto', 'agency-manager' ),
					'display:block;width:100%;'        => __( 'Full Width', 'agency-manager' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .am-btn' => '{{VALUE}}',
				),
			)
		);

		$this->add_responsive_control(
			'button_height',
			array(
				'label'     => __( 'Button Height', 'agency-manager' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 20, 'max' => 100 ) ),
				'selectors' => array(
					'{{WRAPPER}} .am-btn' => 'height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_border_radius',
			array(
				'label'      => __( 'Button Border Radius', 'agency-manager' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .am-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_text_size',
			array(
				'label'     => __( 'Button Text Size', 'agency-manager' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 10, 'max' => 30 ) ),
				'selectors' => array(
					'{{WRAPPER}} .am-btn' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'button_bg',
			array(
				'label'     => __( 'Button Background Colour', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .am-btn' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'button_color',
			array(
				'label'     => __( 'Button Text Colour', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .am-btn' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'button_border_color',
			array(
				'label'     => __( 'Button Border Colour', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .am-btn' => 'border-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'button_hover_bg',
			array(
				'label'     => __( 'Button Hover Background', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .am-btn:hover' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'button_hover_color',
			array(
				'label'     => __( 'Button Hover Text Colour', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .am-btn:hover' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_badge_section',
			array(
				'label'       => __( 'Badge', 'agency-manager' ),
				'tab'         => Controls_Manager::TAB_STYLE,
				'description' => __( 'The "Now Scouting" badge shown on placeholder cards only.', 'agency-manager' ),
			)
		);

		$this->add_control(
			'badge_bg',
			array(
				'label'     => __( 'Badge Background', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .am-scouting-card__badge' => 'background: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'badge_color',
			array(
				'label'     => __( 'Badge Text Colour', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .am-scouting-card__badge' => 'color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'badge_border_color',
			array(
				'label'     => __( 'Badge Border Colour', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .am-scouting-card__badge' => 'border: 1px solid {{VALUE}};' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_typography_section',
			array(
				'label' => __( 'Typography', 'agency-manager' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => __( 'Title Typography', 'agency-manager' ),
				'selector' => '{{WRAPPER}} .am-talent-card__meta h3, {{WRAPPER}} .am-location-card__meta h3',
			)
		);

		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Title Colour', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .am-talent-card__meta h3, {{WRAPPER}} .am-location-card__meta h3' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'subtitle_typography',
				'label'    => __( 'Subtitle Typography', 'agency-manager' ),
				'selector' => '{{WRAPPER}} .am-talent-card__sub, {{WRAPPER}} .am-location-card__sub',
			)
		);

		$this->add_control(
			'subtitle_color',
			array(
				'label'     => __( 'Subtitle Colour', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .am-talent-card__sub, {{WRAPPER}} .am-location-card__sub' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'        => 'description_typography',
				'label'       => __( 'Description Typography', 'agency-manager' ),
				'description' => __( 'Applies to the status line on placeholder ("Now Scouting") cards, e.g. "Applications Open".', 'agency-manager' ),
				'selector'    => '{{WRAPPER}} .am-scouting-card__status',
			)
		);

		$this->add_control(
			'description_color',
			array(
				'label'     => __( 'Description Colour', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .am-scouting-card__status' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_spacing_section',
			array(
				'label' => __( 'Spacing', 'agency-manager' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'card_gap',
			array(
				'label'       => __( 'Card Gap', 'agency-manager' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'     => array( 'size' => 24 ),
				'description' => __( 'Space between cards in the grid/carousel.', 'agency-manager' ),
				'selectors'   => array(
					'{{WRAPPER}} .am-talent-grid, {{WRAPPER}} .am-location-grid, {{WRAPPER}} .am-carousel__track' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'internal_padding',
			array(
				'label'       => __( 'Internal Padding', 'agency-manager' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'description' => __( 'Padding inside the card\'s text area (title/subtitle), separate from Card Padding.', 'agency-manager' ),
				'selectors'   => array(
					'{{WRAPPER}} .am-talent-card__meta, {{WRAPPER}} .am-location-card__meta' => 'padding: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'button_margin',
			array(
				'label'     => __( 'Button Margin', 'agency-manager' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'selectors' => array(
					'{{WRAPPER}} .am-btn' => 'margin-top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$type     = $this->get_data_type();
		$layout   = $this->get_layout();
		$args     = array(
			'only_active' => ! isset( $settings['only_active'] ) || 'yes' === $settings['only_active'],
		);

		if ( $this->is_featured_widget() && 'yes' === ( $settings['inherit_settings'] ?? 'yes' ) ) {
			$homepage              = Settings::get_homepage_config( $type );
			$args['count']         = (int) $homepage['count'];
			$args['homepage_only'] = true;
			if ( 'inherit' !== $homepage['display_mode'] ) {
				$args['display_mode'] = $homepage['display_mode'];
			}
		} else {
			$args['count'] = isset( $settings['count'] ) ? (int) $settings['count'] : $this->default_count();

			if ( $this->is_featured_widget() ) {
				$args['homepage_only'] = true;
			}

			if ( 'talent' === $type ) {
				if ( ! empty( $settings['category'] ) ) {
					$args['category'] = sanitize_title( $settings['category'] );
				}
				if ( ! empty( $settings['group'] ) ) {
					$args['group'] = sanitize_title( $settings['group'] );
				}
			} elseif ( ! empty( $settings['type'] ) ) {
				$args['type'] = sanitize_title( $settings['type'] );
			}

			$args['featured_only'] = 'yes' === ( $settings['only_featured'] ?? '' );
			$args['order']         = in_array( $settings['order'] ?? 'newest', array( 'newest', 'oldest', 'random' ), true ) ? $settings['order'] : 'newest';

			if ( ! empty( $settings['mode'] ) && 'inherit' !== $settings['mode'] ) {
				$args['display_mode'] = $settings['mode'];
			}
		}

		// Columns are handled by this widget's own native Elementor "Columns"
		// control (a responsive CSS selector on .am-talent-grid/.am-location-grid,
		// registered above) rather than Carousel_Renderer's $columns
		// parameter — that parameter exists for the shortcodes, which have
		// no equivalent CSS-injection mechanism of their own. Appearance
		// beyond these Elementor Style-tab controls is the active theme's
		// responsibility (CSS variables / a template override) — there is no
		// "Style preset" selector here.
		// Carousel_Renderer escapes every dynamic value it outputs internally.
		echo Carousel_Renderer::render( $type, $layout, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @return array<string,string> preset name => preset name (Elementor SELECT options are value => label)
	 */
	private function widget_style_preset_options(): array {
		$options = array( '' => __( '— Select —', 'agency-manager' ) );

		foreach ( array_keys( Widget_Style_Presets::all() ) as $name ) {
			$options[ $name ] = $name;
		}

		return $options;
	}

	/**
	 * @return array<string,string>
	 */
	private function term_options( string $taxonomy ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$options = array( '' => __( 'All', 'agency-manager' ) );
		foreach ( $terms as $term ) {
			$options[ $term->slug ] = $term->name;
		}

		return $options;
	}

	private function default_count(): int {
		switch ( $this->get_layout() ) {
			case 'carousel':
				return 9;
			case 'slider':
				return 6;
			default:
				return $this->is_featured_widget() ? 4 : 12;
		}
	}
}
