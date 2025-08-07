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
class Number extends Input {

	public function get_default_args() {
		return [
			'type'        => 'number',
			'label'       => esc_html_x( 'Number *', 'form_default', 'groundhogg' ),
			'name'        => '',
			'id'          => '',
			'class'       => '',
			'value'       => '',
			'placeholder' => '',
			'max'         => '',
			'min'         => '',
			'attributes'  => '',
			'required'    => false,
		];
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'number';
	}

	public function get_min() {
		return esc_attr( $this->get_att( 'min' ) );
	}

	public function get_max() {
		return esc_attr( $this->get_att( 'max' ) );
	}

	public function get_attributes() {
		return sprintf( ' max="%1$s" min="%2$s" %3$s',
			$this->get_min(),
			$this->get_max(),
			$this->get_att( 'attributes' ) );
	}

	/**
	 * Return the value that will be the final value.
	 *
	 * @param $input
	 * @param $config
	 *
	 * @return string
	 */
	public static function validate( $input, $config ) {
		if ( ! preg_match( '/[0-9]+/', $input ) ) {
			return new \WP_Error( 'invalid_number', __( 'Please provide a valid number.', 'groundhogg' ) );
		}

		return apply_filters( 'groundhogg/form/fields/number/validate', intval( $input ) );
	}
}
