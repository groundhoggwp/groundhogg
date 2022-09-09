<?php

namespace Groundhogg\Form\Fields;

use function Groundhogg\html;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */
class Address extends Input {

	public function get_default_args() {
		return [
			'label'    => _x( 'Address *', 'form_default', 'groundhogg' ),
			'class'    => 'gh-address',
			'enabled'  => 'all',
			'required' => false,
		];
	}

	public function get_name() {
		return 'address';
	}

	/**
	 * Return the value that will be the final value.
	 *
	 * @param $input  string|array
	 * @param $config array
	 *
	 * @return string
	 */
	public static function validate( $input, $config ) {
		return apply_filters( 'groundhogg/form/fields/address/validate', array_map( 'sanitize_text_field', $input ) );
	}


	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'address';
	}

	public function render() {

		$street_address_1 = [
			'type'        => 'text',
			'name'        => 'address[street_address_1]',
			'class'       => 'gh-input',
			'placeholder' => __( 'Street Address 1', 'groundhogg' ),
			'required'    => $this->is_required(),
		];

		$street_address_2 = [
			'type'        => 'text',
			'name'        => 'address[street_address_2]',
			'class'       => 'gh-input gh-form-field',
			'placeholder' => __( 'Street Address 2', 'groundhogg' ),
			'required'    => false
		];

		$city = [
			'type'        => 'text',
			'name'        => 'address[city]',
			'class'       => 'gh-input gh-form-field',
			'placeholder' => __( 'City', 'groundhogg' ),
			'required'    => $this->is_required()
		];

		$region = [
			'type'        => 'text',
			'name'        => 'address[region]',
			'class'       => 'gh-input gh-form-field',
			'placeholder' => __( 'Province/State', 'groundhogg' ),
			'required'    => $this->is_required()
		];

		$postal_code = [
			'type'        => 'text',
			'name'        => 'address[postal_zip]',
			'class'       => 'gh-input gh-form-field',
			'placeholder' => __( 'Zip/Postal Code', 'groundhogg' ),
			'required'    => $this->is_required()
		];

		$country = [
			'name'        => 'address[country]',
			'class'       => 'gh-input gh-form-field',
			'options'     => Plugin::$instance->utils->location->get_countries_list(),
			'option_none' => __( 'Country', 'groundhogg' ),
			'required'    => $this->is_required(),
		];

		return html()->e( 'div', [ 'class' => 'form-fields address-fields' ], [
			html()->e( 'div', [ 'class' => 'gh-form-row' ], [
				html()->e( 'div', [ 'class' => 'gh-form-column col-1-of-1' ], [
					html()->e('div', ['class' => 'form-field-with-label'], [
						html()->e('label', [], $this->get_label() ),
						html()->input( $street_address_1 )
					] )
				] )
			] ),
			html()->e( 'div', [ 'class' => 'gh-form-row' ], [
				html()->e( 'div', [ 'class' => 'gh-form-column col-1-of-1' ], html()->input( $street_address_2 ) ),
			] ),
			html()->e( 'div', [ 'class' => 'gh-form-row' ], [
				html()->e( 'div', [ 'class' => 'gh-form-column col-1-of-1' ], html()->input( $city ) ),
			] ),
			html()->e( 'div', [ 'class' => 'gh-form-row' ], [
				html()->e( 'div', [ 'class' => 'gh-form-column col-1-of-3' ], html()->input( $region ) ),
				html()->e( 'div', [ 'class' => 'gh-form-column col-1-of-3' ], html()->input( $postal_code ) ),
				html()->e( 'div', [ 'class' => 'gh-form-column col-1-of-3' ], html()->dropdown( $country ) ),

			] )
		] );

	}
}
