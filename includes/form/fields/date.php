<?php

namespace Groundhogg\Form\Fields;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

/**
 * TODO Support for file types....
 *
 * Class File
 * @package Groundhogg\Form\Fields
 */
class Date extends Input {

	public function get_default_args() {
		return [
			'type'        => 'text',
			'label'       => _x( 'Date *', 'form_default', 'groundhogg' ),
			'name'        => '',
			'id'          => '',
			'class'       => '',
			'max_date'    => '',
			'min_date'    => '',
			'date_format' => 'yy-mm-dd',
			'required'    => false,
			'attributes'  => '',
		];
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'date';
	}

	/**
	 * Get the min date...
	 */
	public function get_min_date() {
		return esc_attr( $this->get_att( 'min_date' ) );
	}

	/**
	 * Get the max date...
	 */
	public function get_max_date() {
		return esc_attr( $this->get_att( 'max_date' ) );
	}

	/**
	 * Get the max date...
	 */
	public function get_date_format() {
		return esc_attr( $this->get_att( 'date_format' ) );
	}

	public function render() {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui' );

		$uniq_id = uniqid( 'date_' );

		$script = sprintf(
			"<script>jQuery(function($){\$('#%s').datepicker({changeMonth: true,changeYear: true,minDate: '%s', maxDate: '%s',dateFormat:'%s'})});</script>",
			$uniq_id,
			$this->get_min_date(),
			$this->get_max_date(),
			$this->get_date_format()
		);

		return sprintf(
			'<label class="gh-input-label">%1$s <input type="%2$s" name="%3$s" id="%4$s" class="gh-input %5$s" value="%6$s" placeholder="%7$s" title="%8$s" %9$s %10$s></label>%11$s',
			$this->get_label(),
			$this->get_type(),
			$this->get_name(),
			$uniq_id,
			$this->get_classes(),
			$this->get_value(),
			$this->get_placeholder(),
			$this->get_title(),
			$this->get_attributes(),
			$this->is_required() ? 'required' : '',
			$script
		);
	}
}