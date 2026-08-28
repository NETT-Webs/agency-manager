<?php
namespace AgencyManager\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Talent_Application_Form_Widget extends Base_Form_Widget {

	public function get_name(): string {
		return 'am-talent-application-form';
	}

	public function get_title(): string {
		return __( 'Talent Application Form', 'agency-manager' );
	}

	public function get_icon(): string {
		return 'eicon-form-horizontal';
	}

	protected function get_form_slug(): string {
		return 'talent-application';
	}
}
