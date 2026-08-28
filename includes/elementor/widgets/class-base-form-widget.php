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
 * Shared base for the two application-form widgets. Field management itself
 * stays in Agency Manager -> Forms (per-instance controls here are limited
 * to hiding specific fields and basic styling — see Form_Renderer::render_form()).
 */
abstract class Base_Form_Widget extends Widget_Base {

	abstract protected function get_form_slug(): string;

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
			'hidden_fields',
			array(
				'label'       => __( 'Hide Fields', 'agency-manager' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $this->get_field_options(),
				'default'     => array(),
				'description' => __( 'Optional — hide specific fields for just this instance. Add, reorder, or require fields under Agency Manager -> Forms.', 'agency-manager' ),
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

	private function get_field_options(): array {
		$form = get_page_by_path( $this->get_form_slug(), OBJECT, 'am_form' );
		if ( ! $form ) {
			return array();
		}

		$options = array();
		foreach ( ( new Form_Renderer() )->get_fields( $form->ID ) as $field ) {
			$options[ $field['key'] ] = $field['label'];
		}

		return $options;
	}

	protected function render(): void {
		$settings      = $this->get_settings_for_display();
		$hidden_fields = ! empty( $settings['hidden_fields'] ) ? (array) $settings['hidden_fields'] : array();

		// Form_Renderer escapes every dynamic value it outputs internally.
		echo ( new Form_Renderer() )->render_form( $this->get_form_slug(), $hidden_fields ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
