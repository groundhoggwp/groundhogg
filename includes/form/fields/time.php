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
class Time extends Input {

	public function get_default_args() {
		return [
			'type'       => 'time',
			'label'      => esc_html_x( 'Time *', 'form_default', 'groundhogg' ),
			'name'       => '',
			'id'         => '',
			'class'      => '',
			'max_time'   => '',
			'min_time'   => '',
			'required'   => false,
			'attributes' => '',
		];
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'time';
	}

	public function get_min_time() {
		return esc_attr( $this->get_att( 'min_time' ) );
	}

	public function get_max_time() {
		return esc_attr( $this->get_att( 'max_time' ) );
	}

	public function get_attributes() {
		return sprintf( ' max="%1$s" min="%2$s" %3$s',
			$this->get_min_time(),
			$this->get_max_time(),
			$this->get_att( 'attributes' ) );
	}
}
