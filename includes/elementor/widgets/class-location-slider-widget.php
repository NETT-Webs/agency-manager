<?php
namespace AgencyManager\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Location_Slider_Widget extends Base_Grid_Widget {

	public function get_name(): string {
		return 'am-location-slider';
	}

	public function get_title(): string {
		return __( 'Location Slider', 'agency-manager' );
	}

	public function get_icon(): string {
		return 'eicon-slider-push';
	}

	protected function get_data_type(): string {
		return 'location';
	}

	protected function get_layout(): string {
		return 'slider';
	}

	protected function is_featured_widget(): bool {
		return false;
	}
}
