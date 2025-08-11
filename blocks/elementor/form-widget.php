<?php

namespace Groundhogg\Blocks\Elementor;

use function Groundhogg\get_form_list;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Form_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'groundhogg-form';
	}

	public function get_title() {
		/* translators: %s: the plugin/brand name */
		return sprintf( __( '%s Forms', 'groundhogg' ), white_labeled_name() );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return [ 'general', 'wordpress' ];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Content', 'groundhogg' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$form_options = get_form_list();

		$this->add_control(
			'form_id',
			[
				'label'   => __( 'Select a Form', 'groundhogg' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 0,
				'options' => $form_options
			]
		);

		$this->end_controls_section();
	}

	protected function render() {

		$settings = $this->get_settings_for_display();

		$form_id = intval( $settings['form_id'] );

		if ( $form_id ) {

			echo do_shortcode( sprintf( '[gh_form id="%d"]', $form_id ) );

		}

	}

}
