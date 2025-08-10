<?php

namespace Groundhogg\Form\Fields;

use Groundhogg\Properties;
use function Groundhogg\ensure_array;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;
use function Groundhogg\sanitize_custom_field;

class Custom_Field extends Input {

	public function get_default_args() {
		return array_merge( [
			'custom_field' => false
		], parent::get_default_args() );
	}

	public function get_shortcode_name() {
		return 'custom';
	}

	public function get_field() {
		return Properties::instance()->get_field( $this->get_att( 'custom_field' ) );
	}

	/**
	 * Return the value that will be the final value.
	 *
	 * @param $input
	 * @param $config
	 *
	 * @return string|\WP_Error
	 */
	public static function validate( $input, $config ) {
		return sanitize_custom_field( $input, $config['name'] );
	}

	public function get_name() {
		$field = $this->get_field();

		if ( $field ) {
			return $field['name'];
		}

		return false;
	}

	public function get_id() {
		$field = $this->get_field();

		if ( $field ) {
			return $field['id'];
		}

		return false;
	}

	/**
	 * Render the field in HTML
	 *
	 * @return string
	 */
	public function render() {

		$field = $this->get_field();

		if ( ! $field ) {
			return '';
		}

		$input = $this->render_field();

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

	public function render_field() {

		$field = $this->get_field();
		$name  = $this->get_name();

		$type = $field['type'];

		$args = [
			'name'  => $name,
			'id'    => $this->get_id(),
			'class' => $this->get_att( 'class' )
		];

		switch ( $type ):

			default:
			case 'text':

				$args['value'] = $this->get_value();
				$args['class'] .= ' gh-input';

				return html()->input( $args );

			case 'textarea':

				$args['rows']  = '3';
				$args['value'] = $this->get_value();
				$args['class'] .= ' gh-input';

				return html()->textarea( $args );

			case 'number':

				$args['class'] .= ' gh-input';
				$args['value'] = $this->get_value();

				return html()->number( $args );

			case 'date':

				$args['class'] .= ' gh-input';
				$args['value'] = $this->get_value();
				$args['type']  = 'date';

				return html()->input( $args );

			case 'radio':

				$options = array_map( 'trim', $field['options'] );

				$html = [];

				foreach ( $options as $option ) {

					$html[] = html()->e( 'label', [
						'style' => [ 'display' => 'block' ]
					], html()->input( [
							'type'    => 'radio',
							'class'   => 'radio',
							'value'   => $option,
							'name'    => $name,
							'checked' => $this->get_value() === $option
						] ) . ' ' . esc_html( $option ) );
				}

				return html()->e( 'div', [ 'class' => 'radio-group' ], $html );

			case 'checkboxes':

				$options = array_map( 'trim', $field['options'] );
				$checked = ensure_array( $this->get_value() );

				$html = [];

				foreach ( $options as $option ) {

					$html[] = html()->e( 'label', [
						'style' => [ 'display' => 'block' ]
					], html()->input( [
							'type'    => 'checkbox',
							'class'   => 'checkbox',
							'value'   => $option,
							'name'    => $name . '[]',
							'checked' => in_array( trim( $option ), $checked )
						] ) . ' ' . esc_html( $option ) );
				}

				return html()->e( 'div', [ 'class' => 'checkbox-group' ], $html );

			case 'dropdown':

				$options     = array_map( 'trim', $field['options'] );
				$options     = array_combine( $options, $options );
				$is_multiple = isset_not_empty( $field, 'multiple' );

				if ( $is_multiple ) {
					$name .= '[]';
				}

				return html()->dropdown( [
					'name'        => $name,
					'id'          => $this->get_id(),
					'class'       => 'gh-input ' . $this->get_att( 'class' ),
					'multiple'    => $is_multiple,
					'options'     => $options,
					'selected'    => $this->get_value(),
					'option_none' => $is_multiple ? false : __( 'Please select one', 'groundhogg' ),
				] );

		endswitch;

	}
}
