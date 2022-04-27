<?php

namespace Groundhogg\Form;


use Groundhogg\Properties;
use Groundhogg\Step;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_to_atts;
use function Groundhogg\encrypt;
use function Groundhogg\form_errors;
use function Groundhogg\get_array_var;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;
use function Groundhogg\managed_page_url;
use function Groundhogg\utils;

/**
 * Wrapper for most field types
 *
 * @param $field
 * @param $input
 *
 * @return mixed|string
 */
function basic_field( $field, $input ) {

	$field = wp_parse_args( $field, [
		'id'          => '',
		'name'        => '',
		'placeholder' => '',
		'className'   => '',
		'required'    => false,
		'value'       => '',
		'hide_label'  => false,
		'label'       => '',
	] );

	if ( $field['hide_label'] ) {
		return $input;
	}

	if ( $field['required'] ) {
		$field['label'] .= ' <span class="required">*</span>';
	}

	return html()->e( 'label', [
			'for' => $field['id']
		], $field['label'] ) . html()->e( 'div', [
			'class' => 'gh-form-input-field'
		], $input );
}

/**
 * Wrapper for basic text fields
 *
 * @param $field
 *
 * @return mixed|string
 */
function basic_text_field( $field ) {

	$field = wp_parse_args( $field, [
		'id'          => '',
		'type'        => 'text',
		'name'        => '',
		'placeholder' => '',
		'className'   => '',
		'required'    => false,
		'value'       => '',
		'hide_label'  => false,
		'label'       => '',
	] );

	if ( empty( $field['id'] ) ) {
		$field['id'] = $field['name'];
	}

	$input = html()->input( [
		'id'          => $field['id'],
		'name'        => $field['name'],
		'class'       => trim( 'gh-input ' . $field['className'] ),
		'placeholder' => $field['placeholder'],
		'required'    => $field['required'],
		'value'       => $field['value'],
	] );

	return basic_field( $field, $input );
}

/**
 * Sanitize a regular basic text field
 *
 * @param $field
 * @param $posted_data
 *
 * @return string
 */
function sanitize_text( $field, $posted_data ) {
	return sanitize_text_field( $posted_data[ $field['name'] ] );
}

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-10
 * Time: 9:51 AM
 */
class Form_v2 extends Step {

	/**
	 * Library of fields with callbacks for rendering and validation
	 *
	 * @var array
	 */
	public static $fields = [];

	/**
	 * Register the basic form fields
	 *
	 * @return void
	 */
	public static function register_fields() {

		$fields = [
			'first'        => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'text',
						'name' => 'first_name',
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$first_name = get_array_var( $posted_data, 'first_name' );
					$last_name  = get_array_var( $posted_data, 'last_name' );

					if ( $first_name && $last_name && $first_name === $last_name ) {
						return new \WP_Error( 'invalid_name', __( 'First and last name cannot be the same.', 'groundhogg' ) );
					}

					if ( preg_match( '/[0-9_!¡?÷?¿\/\\+=@#$%ˆ&*(){}|~<>;:[\]]/u', $first_name ) ) {

						if ( current_user_can( 'edit_funnels' ) ) {
							return new \WP_Error( 'invalid_first_name', __( 'Names should not contain numbers or special symbols.', 'groundhogg' ) );
						}

						return new \WP_Error( 'invalid_first_name', __( 'Please provide a valid first name.', 'groundhogg' ) );

					}

					return true;
				},
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'last'         => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'text',
						'name' => 'last_name',
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$first_name = get_array_var( $posted_data, 'first_name' );
					$last_name  = get_array_var( $posted_data, 'last_name' );

					if ( $first_name && $last_name && $first_name === $last_name ) {
						return new \WP_Error( 'invalid_name', __( 'First and last name cannot be the same.', 'groundhogg' ) );
					}

					if ( preg_match( '/[0-9_!¡?÷?¿\/\\+=@#$%ˆ&*(){}|~<>;:[\]]/u', $last_name ) ) {

						if ( current_user_can( 'edit_funnels' ) ) {
							return new \WP_Error( 'invalid_last_name', __( 'Names should not contain numbers or special symbols.', 'groundhogg' ) );
						}

						return new \WP_Error( 'invalid_last_name', __( 'Please provide a valid last name.', 'groundhogg' ) );

					}

					return true;
				},
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'email'        => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'email',
						'name' => 'email',
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					return is_email( $posted_data[ $field['name'] ] ) ? true : new \WP_Error( 'invalid_email', __( 'Invalid email address', 'groundhogg' ) );
				},
				'sanitize' => function ( $field, $posted_data ) {
					return sanitize_email( $posted_data[ $field['name'] ] );
				},
			],
			'phone'        => [
				'render'   => function ( $field ) {
					$field = wp_parse_args( $field, [
						'phone_type' => 'primary'
					] );

					return basic_text_field( array_merge( $field, [
						'type' => 'tel',
						'name' => $field['phone_type'] . '_phone',
					] ) );
				},
				'validate' => '__return_true',
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'line1'        => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'text',
						'name' => 'line1',
					] ) );
				},
				'validate' => '__return_true',
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'line2'        => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'text',
						'name' => 'line2',
					] ) );
				},
				'validate' => '__return_true',
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'city'         => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'text',
						'name' => 'city',
					] ) );
				},
				'validate' => '__return_true',
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'state'        => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'text',
						'name' => 'state',
					] ) );
				},
				'validate' => '__return_true',
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'zip_code'     => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'text',
						'name' => 'zip_code',
					] ) );
				},
				'validate' => '__return_true',
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'country'      => [
				'render'   => function ( $field ) {

					$field = wp_parse_args( $field, [
						'id'          => '',
						'placeholder' => '',
						'className'   => '',
						'required'    => false,
						'value'       => '',
						'hide_label'  => false,
						'label'       => '',
					] );

					if ( empty( $field['id'] ) ) {
						$field['id'] = 'country';
					}

					return basic_field( $field, html()->dropdown( [
						'id'          => $field['id'],
						'name'        => 'country',
						'class'       => trim( 'gh-input ' . $field['className'] ),
						'option_none' => $field['placeholder'],
						'required'    => $field['required'],
						'selected'    => $field['value'],
						'options'     => utils()->location->get_countries_list()
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					return key_exists( $posted_data['country'], utils()->location->get_countries_list() ) ? true : new \WP_Error( 'invalid_country', __( 'Invalid country selected', 'groundhogg' ) );
				},
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'gdpr'         => [
				'render'   => function ( $field ) {

					$field = wp_parse_args( $field, [
						'id'        => '',
						'className' => '',
					] );

					$business_name = get_option( 'gh_business_name', get_bloginfo( 'name' ) );

					return html()->e( 'div', [
						'class' => trim( 'consent ' . $field['className'] ),
						'id'    => $field['id']
					], [
						html()->wrap(
							html()->checkbox( [
								'label'    => sprintf( __( 'I agree to %s\'s storage and processing of my personal data.', 'groundhogg' ), $business_name ) . ' <span class="required">*</span>',
								'id'       => 'data-processing-consent',
								'name'     => 'data_processing_consent',
								'required' => true,
								'value'    => 'yes',
							] ), 'div' ),
						html()->wrap(
							html()->checkbox( [
								'label'    => sprintf( __( 'I agree to receive marketing offers and updates from %s.', 'groundhogg' ), $business_name ),
								'id'       => 'marketing-consent',
								'name'     => 'marketing_consent',
								'required' => false,
								'value'    => 'yes',
							] ), 'div' )
					] );
				},
				'validate' => function ( $field, $posted_data ) {
					if ( get_array_var( $posted_data, 'data_processing_consent' ) !== 'yes' ) {
						return new \WP_Error( 'error', __( 'You must consent to storage and processing of your data.', 'groundhogg' ) );
					}

					return true;
				},
				'sanitize' => '__return_true'
			],
			'terms'        => [
				'render'   => function ( $field ) {

					$field = wp_parse_args( $field, [
						'id'        => '',
						'className' => '',
					] );

					return html()->e( 'div', [
						'class' => trim( 'terms ' . $field['className'] ),
						'id'    => $field['id']
					], [
						html()->checkbox( [
							'label'    => __( 'I agree to the terms & conditions.', 'groundhogg' ) . ' <span class="required">*</span>',
							'id'       => 'terms-and-conditions',
							'name'     => 'terms_and_conditions',
							'required' => true,
							'value'    => 'yes',
						] )
					] );
				},
				'validate' => function ( $field, $posted_data ) {
					if ( get_array_var( $posted_data, 'terms_and_conditions' ) !== 'yes' ) {
						return new \WP_Error( 'error', __( 'You must agree to the terms and conditions.', 'groundhogg' ) );
					}

					return true;
				},
				'sanitize' => '__return_true'
			],
			'text'         => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'text',
					] ) );
				},
				'validate' => '__return_true',
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'url'          => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'url',
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					return filter_var( $posted_data[ $field['name'] ], FILTER_VALIDATE_URL ) ? true : new \WP_Error( 'invalid_url', __( 'Invalid URL', 'groundhogg' ) );
				},
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'date'         => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'date',
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					return strtotime( $posted_data[ $field['name'] ] ) > 0 ? true : new \WP_Error( 'invalid_date', __( 'Invalid Date', 'groundhogg' ) );
				},
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'time'         => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'time',
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$d = \DateTime::createFromFormat( "Y-m-d H:i:s", "2017-12-01 {$posted_data[ $field['name'] ]}" );

					return $d && $d->format( 'H:i:s' ) == $posted_data[ $field['name'] ] ? true : new \WP_Error( 'invalid_time', __( 'Invalid Time', 'groundhogg' ) );
				},
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'number'       => [
				'render'   => function ( $field ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'number',
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					return is_numeric( $posted_data[ $field['name'] ] ) ? true : new \WP_Error( 'invalid_number', __( 'Invalid number.', 'groundhogg' ) );
				},
				'sanitize' => function ( $field, $posted_data ) {
					$num = $posted_data[ $field['name'] ];

					return strpos( $num, '.' ) !== false ? floatval( $num ) : intval( $num );
				},
			],
			'textarea'     => [
				'render'   => function ( $field ) {

					$field = wp_parse_args( $field, [
						'id'          => '',
						'name'        => '',
						'placeholder' => '',
						'className'   => '',
						'required'    => false,
						'value'       => '',
						'hide_label'  => false,
						'label'       => '',
					] );

					if ( empty( $field['id'] ) ) {
						$field['id'] = $field['name'];
					}

					return basic_field( $field, html()->textarea( [
						'id'          => $field['id'],
						'name'        => $field['name'],
						'class'       => trim( 'gh-input ' . $field['className'] ),
						'placeholder' => $field['placeholder'],
						'required'    => $field['required'],
						'value'       => $field['value'],
					] ) );
				},
				'validate' => '__return_true',
				'sanitize' => function ( $field, $posted_data ) {
					return sanitize_textarea_field( $posted_data[ $field['name'] ] );
				},
			],
			'dropdown'     => [
				'render'   => function ( $field ) {

					$field = wp_parse_args( $field, [
						'id'          => '',
						'name'        => '',
						'placeholder' => '',
						'className'   => '',
						'value'       => '',
						'label'       => '',
						'options'     => [],
						'required'    => false,
						'hide_label'  => false,
					] );

					if ( empty( $field['id'] ) ) {
						$field['id'] = $field['name'];
					}

					return basic_field( $field, html()->dropdown( [
						'id'          => $field['id'],
						'name'        => $field['name'],
						'class'       => trim( 'gh-input ' . $field['className'] ),
						'option_none' => $field['placeholder'],
						'required'    => $field['required'],
						'selected'    => $field['value'],
						'options'     => array_map( function ( $opt ) {
							return is_array( $opt ) ? $opt[0] : $opt;
						}, $field['options'] )
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$options = array_map( function ( $opt ) {
						return is_array( $opt ) ? $opt[0] : $opt;
					}, $field['options'] );

					return in_array( $posted_data[ $field['name'] ], $options ) ? true : new \WP_Error( 'invalid_selection', __( 'Invalid selection', 'groundhogg' ) );
				},
				'sanitize' => __NAMESPACE__ . '\sanitize_text',
			],
			'radio'        => [
				'render'   => function ( $field ) {
					$field = wp_parse_args( $field, [
						'id'          => '',
						'name'        => '',
						'placeholder' => '',
						'className'   => '',
						'value'       => '',
						'label'       => '',
						'options'     => [],
						'required'    => false,
					] );

					if ( $field['required'] ) {
						$field['label'] .= ' <span class="required">*</span>';
					}

					return html()->e( 'label', [
							'for' => $field['id']
						], $field['label'] ) . html()->e( 'div', [
							'class' => trim( 'gh-radio-buttons ' . $field['className'] )
						], array_map( function ( $opt ) use ( $field ) {

							return html()->wrap( html()->checkbox( [
								'type'  => 'radio',
								'label' => is_array( $opt ) ? $opt[0] : $opt,
								'name'  => $field['name'],
								'value' => is_array( $opt ) ? $opt[0] : $opt,
							] ) );

						}, $field['options'] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$options = array_map( function ( $opt ) {
						return is_array( $opt ) ? $opt[0] : $opt;
					}, $field['options'] );

					return in_array( $posted_data[ $field['name'] ], $options ) ? true : new \WP_Error( 'invalid_selection', __( 'Invalid selection', 'groundhogg' ) );
				},
				'sanitize' => __NAMESPACE__ . '\sanitize_text',

			],
			'checkboxes'   => [
				'render'   => function ( $field ) {

					$field = wp_parse_args( $field, [
						'id'          => '',
						'name'        => '',
						'placeholder' => '',
						'className'   => '',
						'value'       => '',
						'label'       => '',
						'options'     => [],
						'required'    => false,
					] );

					if ( $field['required'] ) {
						$field['label'] .= ' <span class="required">*</span>';
					}

					return html()->e( 'label', [
							'for' => $field['id']
						], $field['label'] ) . html()->e( 'div', [
							'class' => trim( 'gh-checkboxes ' . $field['className'] )
						], array_map( function ( $opt ) use ( $field ) {

							return html()->wrap( html()->checkbox( [
								'label' => is_array( $opt ) ? $opt[0] : $opt,
								'name'  => $field['name'] . '[]',
								'value' => is_array( $opt ) ? $opt[0] : $opt,
							] ) );

						}, $field['options'] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$options = array_map( function ( $opt ) {
						return is_array( $opt ) ? $opt[0] : $opt;
					}, $field['options'] );

					$selections = $posted_data[ $field['name'] ];

					return count( array_intersect( $selections, $options ) ) === count( $selections ) ? true : new \WP_Error( 'invalid_selections', __( 'Invalid selections', 'groundhogg' ) );
				},
				'sanitize' => function ( $field, $posted_data ) {
					return map_deep( $posted_data[ $field['name'] ], 'sanitize_text_field' );
				},
			],
			'checkbox'     => [
				'render'   => function ( $field ) {

					$field = wp_parse_args( $field, [
						'id'        => '',
						'name'      => '',
						'className' => '',
						'required'  => false,
						'value'     => '',
						'label'     => '',
					] );

					if ( $field['required'] ) {
						$field['label'] .= ' <span class="required">*</span>';
					}

					return html()->checkbox( [
						'label'    => $field['label'],
						'id'       => $field['id'],
						'name'     => $field['name'],
						'class'    => trim( 'gh-checkbox-input ' . $field['className'] ),
						'required' => $field['required'],
						'value'    => $field['value'] ?: '1',
					] );
				},
				'validate' => '__return_true'
			],
			'file'         => [],
			'custom_field' => [
				'render'   => function ( $field ) {
					$property = $field['property'];
					$property = Properties::instance()->get_field( $property );
					if ( ! $property ) {
						return '';
					}

					return Form_v2::render_input( array_merge( $property, [
						'value'     => $field['value'],
						'id'        => $field['id'],
						'className' => $field['className'],
						'required'  => $field['required'],
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$property = $field['property'];
					$property = Properties::instance()->get_field( $property );
					if ( ! $property ) {
						return new \WP_Error( 'invalid_property', 'somethign went wrong' );
					}

					return Form_v2::validate_input( array_merge( $property, [
						'value'     => $field['value'],
						'id'        => $field['id'],
						'className' => $field['className'],
						'required'  => $field['required'],
					] ), $posted_data );
				}
			],
			'html'         => [
				'render'   => function ( $field ) {

					$field = wp_parse_args( $field, [
						'id'        => '',
						'className' => '',
						'html'      => '',
					] );

					return html()->e( 'div', [
						'id'    => $field['id'],
						'class' => trim( $field['className'] ),
					], $field['html'] );
				},
				'validate' => '__return_true'
			],
			'button'       => [
				'render'   => function ( $field ) {

					$field = wp_parse_args( $field, [
						'id'        => '',
						'text'      => '',
						'className' => '',
					] );

					return html()->button( [
						'id'    => $field['id'],
						'class' => trim( $field['className'] . ' gh-submit' ),
						'type'  => 'submit',
						'text'  => $field['text']
					] );
				},
				'validate' => '__return_true'
			],
			'recaptcha'    => [],
		];

		foreach ( $fields as $type => $callbacks ) {

			if ( empty( $callbacks ) || ! is_callable( $callbacks['render'] ) || ! is_callable( $callbacks['validate'] ) ) {
				continue;
			}

			self::register_field( $type, $callbacks['render'], $callbacks['validate'] );
		}

		do_action( 'groundhogg/form/register_fields' );

	}

	/**
	 * Register a form field
	 *
	 * @param $type     string
	 * @param $render   callable
	 * @param $validate callable
	 *
	 * @return void
	 */
	public static function register_field( $type, $render, $validate ) {

		if ( ! is_callable( $render ) || ! $validate ) {
			return;
		}


		self::$fields[ $type ] = [
			'type'     => $type,
			'render'   => $render,
			'validate' => $validate,
		];
	}

	/**
	 * Manager constructor.
	 */
	public function __construct( $atts ) {

		$atts = shortcode_atts( [
			'class' => '',
			'id'    => 0
		], $atts );

		// Init the fields for the first time if empty
		if ( empty( self::$fields ) ) {
			self::register_fields();
		}

		// Init step as normal
		parent::__construct( $atts['id'] );
	}

	public function get_uuid() {
		return $this->get_meta( 'uuid' );
	}

	public function get_shortcode() {
		return sprintf( '[gh_form id="%d"]', $this->get_id() );
	}

	public function get_iframe_embed_code() {
		$form_iframe_url = managed_page_url( sprintf( 'forms/iframe/%s/', $this->get_uuid() ) );

		return sprintf( '<script id="%s" type="text/javascript" src="%s"></script>', 'groundhogg_form_' . $this->get_id(), $form_iframe_url );
	}

	public function get_submission_url() {
		return managed_page_url( sprintf( 'forms/%s/submit/', $this->get_uuid() ) );
	}

	public function get_hosted_url() {
		return managed_page_url( sprintf( 'forms/%s/', $this->get_uuid() ) );
	}

	/**
	 * Get the form as raw HTML for an embed code
	 *
	 * @return string
	 */
	public function get_html_embed_code() {

		if ( ! $this->exists() ) {
			return sprintf( "<p>%s</p>", __( "<b>Configuration Error:</b> This form has been deleted." ) );
		}

		$form = html()->e( 'link', [
			'rel'  => 'stylesheet',
			'href' => GROUNDHOGG_ASSETS_URL . 'css/frontend/form.css'
		] );

		$form .= '<div class="gh-form-wrapper">';

		$atts = [
			'method'  => 'post',
			'class'   => 'gh-form',
			'target'  => '_parent',
			'action'  => $this->get_submission_url(),
			'enctype' => 'multipart/form-data',
			'name'    => $this->get_step_title()
		];

		$form .= sprintf( "<form %s>", array_to_atts( $atts ) );

		if ( ! empty( $this->attributes['id'] ) ) {
			$form .= "<input type='hidden' name='gh_submit_form_key' value='" . encrypt( $this->get_id() ) . "'>";
			$form .= "<input type='hidden' name='gh_submit_form' value='" . $this->get_id() . "'>";
		}

		$form .= $this->get_field_html();

		$form .= '</form>';

		$form .= '</div>';

		return apply_filters( 'groundhogg/form/embed', $form, $this );
	}

	/**
	 * Validate a field
	 *
	 * @param $field
	 * @param $posted_data array
	 *
	 * @return false|mixed|string
	 */
	public static function validate_input( $field, $posted_data ) {
		$type = $field['type'];

		$field_type = get_array_var( self::$fields, $type );

		if ( ! $field_type ) {
			return '';
		}

		return call_user_func( $field_type['validate'], $field, $posted_data );
	}

	/**
	 * @param $field
	 *
	 * @return false|mixed|string
	 */
	public static function render_input( $field ) {
		$type = $field['type'];

		$field_type = get_array_var( self::$fields, $type );

		if ( ! $field_type ) {
			return '';
		}

		return call_user_func( $field_type['render'], $field );
	}

	/**
	 *
	 * @param $field
	 *
	 * @return string
	 */
	function render_field( $field ) {

		$type = $field['type'];

		$field_type = get_array_var( self::$fields, $type );

		if ( ! $field_type ) {
			return '';
		}

		$inner_html = self::render_input( $field );

		$map = [
			'1/1' => 'col-1-of-1',
			'1/2' => 'col-1-of-2',
			'1/3' => 'col-1-of-3',
			'1/4' => 'col-1-of-4',
			'2/3' => 'col-2-of-3',
			'3/4' => 'col-3-of-4',
		];

		return html()->e( 'div', [
			'class' => 'gh-form-column ' . $map[ get_array_var( $field, 'column_width', '1/1' ) ]
		], $inner_html );
	}

	/**
	 * Get the HTML For the fields
	 *
	 * @return string
	 */
	function get_field_html() {

		$config = $this->get_meta( 'form' );
		$fields = $config['fields'];

		$html = implode( '', array_map( [ $this, 'render_field' ], $fields ) );

		$recaptcha = get_array_var( $config, 'recaptcha' );
		$button    = get_array_var( $config, 'button' );

		if ( isset_not_empty( $recaptcha, 'enabled' ) ) {
			$html .= $this->render_field( $recaptcha );
		}

		$html .= $this->render_field( $button );

		return $html;
	}


	/**
	 * Do the shortcode
	 *
	 * @return string
	 */
	public function shortcode() {

		wp_enqueue_style( 'groundhogg-form' );

		$form = '<div class="gh-form-wrapper">';

		/* Errors from a previous submission */
		$form .= form_errors( true );

		if ( ! $this->exists() ) {
			return sprintf( "<p>%s</p>", __( "<b>Configuration Error:</b> This form has been deleted." ) );
		}

		$submit_via_ajax = $this->get_meta( 'enable_ajax' );

		if ( $submit_via_ajax ) {
			wp_enqueue_script( 'groundhogg-ajax-form' );
			wp_enqueue_style( 'groundhogg-loader' );
		}

		$atts = [
			'method'  => 'post',
			'class'   => 'gh-form ' . ( $submit_via_ajax ? ' ajax-submit' : '' ),
			'target'  => '_parent',
			'enctype' => 'multipart/form-data',
			'name'    => wp_strip_all_tags( $this->get_step_title() ),
			'id'      => $this->get_uuid()
		];

		if ( get_query_var( 'doing_iframe' ) ) {
			$atts['action'] = $this->get_submission_url();
		}

		$form .= sprintf( "<form %s>", array_to_atts( $atts ) );

		$form .= "<input type='hidden' name='gh_submit_form_key' value='" . encrypt( $this->get_id() ) . "'>";
		$form .= "<input type='hidden' name='gh_submit_form' value='" . $this->get_id() . "'>";

		$form .= '<div class="gh-form-fields">';

		$form .= $this->get_field_html();

		$form .= '</div>';
		$form .= '</form>';

		if ( is_user_logged_in() && current_user_can( 'edit_funnels' ) ) {
			$form .= sprintf( "<div class='gh-form-edit-link'><a href='%s'>%s</a></div>", admin_page_url( 'gh_funnels', [
				'action' => 'edit',
				'funnel' => $this->get_funnel_id()
			], $this->get_id() ), __( '(Edit Form)' ) );
		}

		$form .= '</div>';

		return apply_filters( 'groundhogg/form/shortcode', $form, $this );
	}

	/**
	 * Just return the shortcode
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->shortcode();
	}

	/**
	 * Override builtin STEP serialization
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'ID'            => $this->get_id(),
			'name'          => $this->get_title(),
			'rendered'      => $this->shortcode(),
			'embed_methods' => [
				'html'   => $this->get_html_embed_code(),
				'iframe' => $this->get_iframe_embed_code(),
				'url'    => $this->get_submission_url()
			]
		];
	}
}
