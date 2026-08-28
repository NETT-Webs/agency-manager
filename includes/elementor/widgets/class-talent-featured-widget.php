<?php
namespace AgencyManager\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Talent_Featured_Widget extends Base_Grid_Widget {

	public function get_name(): string {
		return 'am-talent-featured';
	}

	public function get_title(): string {
		return __( 'Featured Talent', 'agency-manager' );
	}

	public function get_icon(): string {
		return 'eicon-star';
	}

	protected function get_data_type(): string {
		return 'talent';
	}

	protected function get_layout(): string {
		return 'grid';
	}

	protected function is_featured_widget(): bool {
		return true;
	}
}
