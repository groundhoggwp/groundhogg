<?php

namespace Groundhogg\Form;

use Groundhogg\Properties;
use function Groundhogg\get_array_var;
use function Groundhogg\html;
use function Groundhogg\utils;

/**
 * Outputs a form that when submitted the values can be passed directly to generate_contact_with_map()
 */
class Form_Fields {

	protected static array $field_templates = [];

	/**
	 * Basic field template
	 *
	 * @param $props
	 * @param $input
	 *
	 * @return string
	 */
	protected static function field_template( $props, $input ): string {

		$label = $props['label'];

		if ( $props['required'] ) {
			$label .= ' <span class="required">*</span>';
		}

		return html()->e( 'div', [
			'class' => 'form-field-row'
		], [
			html()->e( 'label', [ 'id' => $props['id'] ], $label ),
			$input
		] );
	}

	/**
	 * Standard input field template
	 *
	 * @param $props
	 *
	 * @return string
	 */
	protected static function input_field_template( $props, $type = 'text' ) {
		$props = wp_parse_args( $props, [
			'id'       => '',
			'required' => false,
		] );

		return self::field_template( $props, html()->input( [
			'type'     => $type,
			'name'     => $props['id'],
			'id'       => $props['id'],
			'required' => $props['required'],
		] ) );
	}

	/**
	 * Handle inputs for custom fields
	 *
	 * @param $props
	 * @param $field
	 *
	 * @return string
	 */
	protected static function handle_custom_field( $props, $field ): string {

		$name = $field['name'];

		switch ( $field['type'] ):
			default:
			case 'custom_email':
				$input = html()->input( [
					'type'     => 'email',
					'id'       => $name,
					'name'     => $name,
					'required' => $props['required']
				] );
				break;
			case 'email':
			case 'text':
			case 'url':
			case 'tel':
			case 'time':
			case 'date':
			case 'number':
				$input = html()->input( [
					'type'     => $field['type'],
					'id'       => $name,
					'name'     => $name,
					'required' => $props['required']
				] );
				break;
			case 'datetime':
				$input = html()->input( [
					'type'     => 'datetime-local',
					'id'       => $name,
					'name'     => $name,
					'required' => $props['required']
				] );
				break;
			case 'html':
			case 'textarea':
				$input = html()->textarea( [
					'id'       => $name,
					'name'     => $name,
					'required' => $props['required']
				] );
				break;
			case 'dropdown':
				$options = $field['options'];

				$input = html()->dropdown( [
					'id'       => $name,
					'name'     => $name,
					'options'  => array_combine( $options, $options ),
					'multiple' => get_array_var( $field, 'multiple' ),
					'required' => $props['required']
				] );
				break;
			case 'radio':
				$options = $field['options'];

				$input = html()->e( 'div', [
					'class' => 'radio-buttons'
				], array_map( function ( $option ) use ( $field ) {
					return html()->e( 'label', [], [
						html()->input( [
							'type'  => 'radio',
							'name'  => $field['name'],
							'value' => $option
						] ),
						' ',
						$option
					] );
				}, $options ) );

				break;
			case 'checkboxes':
				$options = $field['options'];

				$input = html()->e( 'div', [
					'class' => 'checkboxes'
				], array_map( function ( $option ) use ( $field ) {
					return html()->e( 'label', [], [
						html()->input( [
							'type'  => 'checkbox',
							'name'  => $field['name'] . '[]',
							'value' => $option
						] ),
						' ',
						$option
					] );
				}, $options ) );

				break;
		endswitch;

		return self::field_template( $props, $input );
	}

	/**
	 * Register the initial field templates
	 *
	 * @return void
	 */
	protected static function init_field_templates() {

		$field_templates = [
			'full_name'        => [ __CLASS__, 'input_field_template' ],
			'first_name'       => [ __CLASS__, 'input_field_template' ],
			'last_name'        => [ __CLASS__, 'input_field_template' ],
			'email'            => function ( $props ) {
				return Form_Fields::field_template( $props, html()->input( [
					'type'     => 'email',
					'name'     => 'email',
					'id'       => 'email',
					'required' => true,
				] ) );
			},
			'primary_phone'    => function ( $props ) {
				return Form_Fields::input_field_template( $props, 'tel' );
			},
			'mobile_phone'     => function ( $props ) {
				return Form_Fields::input_field_template( $props, 'tel' );
			},
			'birthday'         => function ( $props ) {
				return Form_Fields::field_template( $props, html()->input( [
					'type'     => 'date',
					'name'     => 'birthday',
					'id'       => 'birthday',
					'required' => $props['required'],
				] ) );
			},
			'street_address_1' => [ __CLASS__, 'input_field_template' ],
			'street_address_2' => [ __CLASS__, 'input_field_template' ],
			'city'             => [ __CLASS__, 'input_field_template' ],
			'region'           => [ __CLASS__, 'input_field_template' ],
			'postal_zip'       => [ __CLASS__, 'input_field_template' ],
			'country'          => function ( $props ) {
				return Form_Fields::field_template( $props, html()->dropdown( [
					'name'     => 'country',
					'id'       => 'country',
					'required' => $props['required'],
					'options'  => utils()->location->get_countries_list()
				] ) );
			},
		];

		$custom_fields = Properties::instance()->get_fields();

		foreach ( $custom_fields as $field ) {

			$field_templates[ $field['id'] ] = function ( $props ) use ( $field ) {
				return Form_Fields::handle_custom_field( $props, $field );
			};

		}

		self::$field_templates = $field_templates;
	}

	/**
	 * Render a field
	 *
	 * @param $field
	 *
	 * @return mixed|string
	 */
	protected static function render_field( $field ) {

		if ( empty( self::$field_templates ) ) {
			self::init_field_templates();
		}

		$field = wp_parse_args( $field, [
			'label'    => '',
			'required' => false,
			'id'       => '',
		] );

		if ( ! key_exists( $field['id'], self::$field_templates ) ) {
			return '';
		}

		$template = self::$field_templates[ $field['id'] ];

		return call_user_func( $template, $field );
	}

	protected array $form;

	/**
	 * Initialize
	 *
	 * @param array $form
	 */
	public function __construct( array $form = [] ) {
		$this->form = $form;
	}

	/**
	 * Output the field HTML
	 *
	 * @return string
	 */
	public function __toString(): string {
		return html()->e( 'div', [ 'class' => 'fields' ], array_map( [ __CLASS__, 'render_field' ], $this->form ) );
	}
}
