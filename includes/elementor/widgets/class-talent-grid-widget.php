<?php
namespace AgencyManager\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Talent_Grid_Widget extends Base_Grid_Widget {

	public function get_name(): string {
		return 'am-talent-grid';
	}

	public function get_title(): string {
		return __( 'Talent Grid', 'agency-manager' );
	}

	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	protected function get_data_type(): string {
		return 'talent';
	}

	protected function get_layout(): string {
		return 'grid';
	}

	protected function is_featured_widget(): bool {
		return false;
	}
}
