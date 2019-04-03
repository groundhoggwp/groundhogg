<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPGH_Elementor_Form_Widget extends  \Elementor\Widget_Base {

    public function get_name() {
        return 'groundhogg-form';
    }

    public function get_title() {
        return __( 'Groundhogg Forms', 'groundhogg' );
    }

    public function get_icon() {
        // Icon name from the Elementor font file, as per http://dtbaker.net/web-development/creating-your-own-custom-elementor-widgets/
        return 'eicon-form-horizontal';
//        return 'fa fa-wpforms';
    }

    public function get_categories() {
        return [ 'general', 'wordpress' ];
    }

    protected function _register_controls() {

        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__( 'Content', 'elementor' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $forms = WPGH()->steps->get_steps( array(
            'step_type' => 'form_fill'
        ) );

        $form_options = array();
        $default = 0;
        foreach ( $forms as $form ){
            if ( ! $default ){$default = $form->ID;}
            $step = wpgh_get_funnel_step( $form->ID );
            if ( $step->is_active() ){$form_options[ $form->ID ] = $form->step_title;}
        }

        $this->add_control(
            'form_id',
            [
                'label' => __( 'Select a Form', 'groundhogg' ),
                'type' => Elementor\Controls_Manager::SELECT,
                'default' => $default,
                'options' => $form_options
            ]
        );

        $this->end_controls_section();
    }

    protected function render(){

        $settings = $this->get_settings_for_display();

        $form_id = intval( $settings[ 'form_id' ] );

        if ( $form_id ){

            echo do_shortcode( sprintf( '[gh_form id="%d"]', $form_id ) );

        }

    }

}
