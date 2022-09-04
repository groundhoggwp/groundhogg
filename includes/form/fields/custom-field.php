<?php

namespace Groundhogg\Form\Fields;

use Groundhogg\Properties;
use function Groundhogg\display_custom_field;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;

class Custom_Field extends Input {

	public function get_shortcode_name() {
		return 'custom';
	}

	public function get_field() {
		return Properties::instance()->get_field( $this->get_att( 'custom_field' ) );
	}

	public function get_name() {
		$field = $this->get_field();

		if ( $field ) {
			return $field['name'];
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

		// No label, do not wrap in label element.
		if ( ! $this->has_label() ) {
			return $this->render_field();
		}

		return html()->e('div', [], [
			html()->e('label', [ 'for' => $this->get_id() ], $this->get_label() ),
			$this->render_field()
		] );

	}

	public function render_field() {

		$field = $this->get_field();

		$type = $field['type'];

		$args = [
			'name'  => $field['name'],
			'id'    => $this->get_att( 'id' ),
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

				$options = explode( PHP_EOL, $field['settings']['options'] );

				foreach ( $options as $option ) {

					echo html()->e( 'label', [
						'style' => [ 'display' => 'block' ]
					], html()->input( [
							'type'    => 'radio',
							'class'   => 'radio',
							'value'   => $option,
							'name'    => $name,
							'checked' => trim( $option ) === trim( $value )
						] ) . ' ' . esc_html( $option ) );

				}

				break;

			case 'checkboxes':

				$options = explode( PHP_EOL, $field['settings']['options'] );
				$options = array_map( 'trim', $options );

				if ( is_string( $value ) ) {
					$value = array_map( 'trim', explode( ',', $value ) );
				}

				foreach ( $options as $option ) {

					echo html()->e( 'label', [
						'style' => [ 'display' => 'block' ]
					], html()->input( [
							'type'    => 'checkbox',
							'class'   => 'checkbox',
							'value'   => $option,
							'name'    => $name . '[]',
							'checked' => in_array( trim( $option ), $value )
						] ) . ' ' . esc_html( $option ) );
				}

				break;

			case 'dropdown':

				$options             = explode( PHP_EOL, $field['settings']['options'] );
				$options             = array_map( 'trim', $options );
				$options             = array_combine( $options, $options );
				$is_multiple         = isset_not_empty( $field['settings'], 'multiple' );
				$insert_blank_option = isset_not_empty( $field['settings'], 'blank_option' );

				if ( $is_multiple ) {
					$name .= '[]';
				}

				// Handles the comma speprated value if values are stored as a sting using the Form Builders
				if ( is_string( $value ) ) {
					$value = explode( ',', $value );
				}

				echo html()->dropdown( [
					'name'        => $name,
					'multiple'    => $is_multiple,
					'options'     => $options,
					'selected'    => $value,
					'option_none' => $insert_blank_option ? __( '-----' ) : false,
				] );

				break;

		endswitch;

	}
}
