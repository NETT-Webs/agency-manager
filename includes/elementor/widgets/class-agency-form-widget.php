<?php
namespace AgencyManager\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use AgencyManager\Forms\Form_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The generic "any form" widget — one Form dropdown, populated from every
 * `am_form` post, so a form built in the Form Builder never needs its own
 * hand-written widget class (unlike the two legacy hardcoded-slug widgets,
 * which keep working unchanged alongside this one). Same rendering path
 * (Form_Renderer::render_form_by_id()) as the [agency_form] shortcode — one
 * form definition, one renderer, everywhere it's embedded.
 */
class Agency_Form_Widget extends Widget_Base {

	public function get_name(): string {
		return 'am-agency-form';
	}

	public function get_title(): string {
		return __( 'Agency Manager Form', 'agency-manager' );
	}

	public function get_icon(): string {
		return 'eicon-form-horizontal';
	}

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

		$this->add_control(
			'form_id',
			array(
				'label'       => __( 'Form', 'agency-manager' ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_form_options(),
				'default'     => '',
				'label_block' => true,
				'description' => __( 'Choose which form (built under Agency Manager -> Forms) to display.', 'agency-manager' ),
			)
		);

		$this->add_control(
			'hidden_fields',
			array(
				'label'       => __( 'Hide Fields', 'agency-manager' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $this->get_all_field_options(),
				'default'     => array(),
				'description' => __( 'Optional — hide specific fields for just this instance. Add, reorder, or require fields in the Form Builder.', 'agency-manager' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_section',
			array(
				'label' => __( 'Style', 'agency-manager' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'label'    => __( 'Label Typography', 'agency-manager' ),
				'selector' => '{{WRAPPER}} .am-form label',
			)
		);

		$this->add_control(
			'button_bg',
			array(
				'label'     => __( 'Button Background', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .am-btn' => 'background-color: {{VALUE}};' ),
			)
		);

		$this->add_control(
			'button_color',
			array(
				'label'     => __( 'Button Text Color', 'agency-manager' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .am-btn' => 'color: {{VALUE}};' ),
			)
		);

		$this->end_controls_section();
	}

	private function get_form_options(): array {
		$forms = get_posts(
			array(
				'post_type'   => 'am_form',
				'post_status' => 'publish',
				'numberposts' => -1,
			)
		);

		$options = array( '' => __( '— Select a form —', 'agency-manager' ) );
		foreach ( $forms as $form ) {
			$options[ $form->ID ] = $form->post_title;
		}

		return $options;
	}

	/**
	 * Every field across every form, prefixed by form title, so "Hide
	 * Fields" has something to offer regardless of which form ends up
	 * selected (Elementor controls can't easily depend on another control's
	 * value without extra JS this plugin doesn't ship) — the render path
	 * only ever applies the ones that actually belong to the chosen form.
	 */
	private function get_all_field_options(): array {
		$forms    = get_posts( array( 'post_type' => 'am_form', 'post_status' => 'publish', 'numberposts' => -1 ) );
		$renderer = new Form_Renderer();
		$options  = array();

		foreach ( $forms as $form ) {
			foreach ( $renderer->get_fields( $form->ID ) as $field ) {
				$options[ $field['key'] ] = $form->post_title . ': ' . $field['label'];
			}
		}

		return $options;
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$form_id  = absint( $settings['form_id'] ?? 0 );

		if ( ! $form_id ) {
			return;
		}

		$hidden_fields = ! empty( $settings['hidden_fields'] ) ? (array) $settings['hidden_fields'] : array();

		// Form_Renderer escapes every dynamic value it outputs internally.
		echo ( new Form_Renderer() )->render_form_by_id( $form_id, $hidden_fields ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
