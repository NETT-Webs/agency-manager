<?php
namespace AgencyManager\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Location_Grid_Widget extends Base_Grid_Widget {

	public function get_name(): string {
		return 'am-location-grid';
	}

	public function get_title(): string {
		return __( 'Location Grid', 'agency-manager' );
	}

	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	protected function get_data_type(): string {
		return 'location';
	}

	protected function get_layout(): string {
		return 'grid';
	}

	protected function is_featured_widget(): bool {
		return false;
	}
}
