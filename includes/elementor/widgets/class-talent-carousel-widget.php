<?php
namespace AgencyManager\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Talent_Carousel_Widget extends Base_Grid_Widget {

	public function get_name(): string {
		return 'am-talent-carousel';
	}

	public function get_title(): string {
		return __( 'Talent Carousel', 'agency-manager' );
	}

	public function get_icon(): string {
		return 'eicon-carousel';
	}

	protected function get_data_type(): string {
		return 'talent';
	}

	protected function get_layout(): string {
		return 'carousel';
	}

	protected function is_featured_widget(): bool {
		return false;
	}
}
