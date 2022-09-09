<?php

namespace Groundhogg\Form\Fields;

use function Groundhogg\html;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */
class Textarea extends Input {

	public function get_default_args() {
		return [
			'label'       => '',
			'name'        => '',
			'id'          => '',
			'class'       => '',
			'value'       => '',
			'placeholder' => '',
			'title'       => '',
			'attributes'  => '',
			'required'    => false,
			'rows'        => '4',
			'cols'        => false,
		];
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'textarea';
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
		return apply_filters( 'groundhogg/form/fields/textarea/validate', sanitize_textarea_field( $input ) );
	}

	/**
	 * Render the HTML
	 *
	 * @return string
	 */
	public function render() {
		$atts = [
			'type'        => $this->get_type(),
			'name'        => $this->get_name(),
			'id'          => $this->get_id(),
			'class'       => $this->get_classes() . ' gh-input',
			'value'       => $this->get_value(),
			'placeholder' => $this->get_placeholder(),
			'title'       => $this->get_title(),
			'required'    => $this->is_required(),
			'rows'        => $this->get_att( 'rows', 4 ),
			'cols'        => $this->get_att( 'cols', false )
		];

		$input = html()->textarea( $atts );

		// No label, do not wrap in label element.
		if ( ! $this->has_label() ) {
			return $input;
		}

		$label = html()->e( 'label', [
			'class' => 'gh-input-label',
			'for'   => $this->get_id(),
		], $this->get_label() );

		return html()->e( 'div', [ 'class' => 'form-field-with-label' ], [
			$label,
			$input
		] );
	}
}
