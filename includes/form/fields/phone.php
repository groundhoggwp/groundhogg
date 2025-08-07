<?php

namespace Groundhogg\Form\Fields;

use function Groundhogg\html;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */
class Phone extends Input {

	public function get_default_args() {
		return [
			'type'        => 'tel',
			'label'       => esc_html_x( 'Phone *', 'form_default', 'groundhogg' ),
			'name'        => 'primary_phone',
			'id'          => 'primary_phone',
			'class'       => 'gh-tel gh-input',
			'value'       => '',
			'placeholder' => '',
			'attributes'  => '',
			'required'    => false,
			'phone_type'  => 'primary',
			'show_ext'    => false,
		];
	}

	/**
	 * Return name dependent on the phone type
	 *
	 * @return string
	 */
	public function get_name() {
		switch ( $this->get_att( 'phone_type', 'primary' ) ) {
			default:
			case 'primary':
				return 'primary_phone';
			case 'mobile':
				return 'mobile_phone';
			case 'company':
				return 'company_phone';
		}
	}

	/**
	 * Return name dependent on the phone type
	 *
	 * @return string
	 */
	public function get_ext_name() {
		switch ( $this->get_att( 'phone_type', 'primary' ) ) {
			default:
			case 'primary':
				return 'primary_phone_extension';
			case 'company':
				return 'company_phone_extension';
		}
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'phone';
	}

	public function render() {

		$phone = [
			'type'        => $this->get_type(),
			'name'        => $this->get_name(),
			'id'          => $this->get_id(),
			'class'       => $this->get_classes() . ' gh-input',
			'value'       => $this->get_value(),
			'placeholder' => $this->get_placeholder(),
			'title'       => $this->get_title(),
			'required'    => $this->is_required(),
		];

		$ext = [
			'type'        => 'number',
			'name'        => $this->get_ext_name(),
			'id'          => $this->get_id() . '-ext',
			'class'       => $this->get_classes() . ' gh-input',
			'value'       => '',
			'placeholder' => '',
			'title'       => '',
			'required'    => false,
		];

		$phone_input = html()->input( $phone );
		$ext_input   = html()->input( $ext );

		// Show ext for none mobile numbers if being collected
		if ( $this->get_att( 'show_ext' ) && $this->get_att( 'phone_type' ) !== 'mobile' ) {
			// No label, do not wrap in label element.
			if ( ! $this->has_label() ) {
				return html()->e( 'div', [ 'class' => 'gh-form-row' ], [
					html()->e( 'div', [ 'class' => 'gh-form-column col-2-of-3' ], html()->input( $phone ) ),
					html()->e( 'div', [ 'class' => 'gh-form-column col-1-of-3' ], html()->input( $ext ) ),
				] );
			}

			return html()->e( 'div', [ 'class' => 'gh-form-row' ], [
				html()->e( 'div', [ 'class' => 'gh-form-column col-2-of-3' ], [
					html()->e( 'div', [ 'class' => 'form-field-with-label' ], [
						html()->e( 'label', [
							'class' => 'gh-input-label',
							'for'   => $this->get_id(),
						], $this->get_label() ),
						$phone_input
					] )
				] ),
				html()->e( 'div', [ 'class' => 'gh-form-column col-1-of-3' ], [
					html()->e( 'div', [ 'class' => 'form-field-with-label' ], [
						html()->e( 'label', [
							'class' => 'gh-input-label',
							'for'   => $this->get_id(),
						], __( 'Ext.' ) ),
						$ext_input
					] )
				] ),
			] );
		}

		// No label, do not wrap in label element.
		if ( ! $this->has_label() ) {
			return $phone_input;
		}

		$label = html()->e( 'label', [
			'class' => 'gh-input-label',
			'for'   => $this->get_id(),
		], $this->get_label() );

		return html()->e( 'div', [ 'class' => 'form-field-with-label' ], [
			$label,
			$phone_input
		] );
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
		// Ignore empty phone number
		if ( ! preg_match( '/^[+]?[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/', $input ) && ! empty( $input ) ) {
			return new \WP_Error( 'invalid_phone_number', __( 'Please provide a valid number.', 'groundhogg' ) );
		}

		return apply_filters( 'groundhogg/form/fields/number/validate', $input );
	}
}
