<?php
namespace AgencyManager\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Location_Carousel_Widget extends Base_Grid_Widget {

	public function get_name(): string {
		return 'am-location-carousel';
	}

	public function get_title(): string {
		return __( 'Location Carousel', 'agency-manager' );
	}

	public function get_icon(): string {
		return 'eicon-carousel';
	}

	protected function get_data_type(): string {
		return 'location';
	}

	protected function get_layout(): string {
		return 'carousel';
	}

	protected function is_featured_widget(): bool {
		return false;
	}
}
