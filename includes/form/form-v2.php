<?php

namespace Groundhogg\Form;


use Groundhogg\Contact;
use Groundhogg\Plugin;
use Groundhogg\Properties;
use Groundhogg\Step;
use Groundhogg\Submission;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_to_atts;
use function Groundhogg\current_contact_and_logged_in_user_match;
use function Groundhogg\do_replacements;
use function Groundhogg\encrypt;
use function Groundhogg\form_errors;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_current_contact;
use function Groundhogg\get_db;
use function Groundhogg\get_request_query;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;
use function Groundhogg\managed_page_url;
use function Groundhogg\utils;
use function Groundhogg\validate_mobile_number;

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
 * @param              $field
 * @param bool|Contact $contact
 *
 * @return mixed|string
 */
function basic_text_field( $field, $contact = false ) {

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
		'value'       => $contact ? ( $contact->get_meta( $field['name'] ) ?: '' ) : $field['value'],
	] );

	return basic_field( $field, $input );
}

/**
 * Sanitize a regular basic text field
 *
 * @param $field
 * @param $posted_data
 * @param $data array
 * @param $meta array
 */
function standard_meta_callback( $field, $posted_data, &$data, &$meta ) {
	$meta[ $field['name'] ] = sanitize_text_field( $posted_data[ $field['name'] ] );
}

/**
 * Callback for dropdowns & radio buttons
 *
 * @param $field       array
 * @param $posted_data Posted_Data
 * @param $data        array
 * @param $meta        array
 * @param $tags        array
 *
 * @return void
 */
function standard_dropdown_callback( $field, $posted_data, &$data, &$meta, &$tags ) {
	$selected               = sanitize_text_field( $posted_data[ $field['name'] ] );
	$meta[ $field['name'] ] = $selected;

	$options = $field['options'];

	foreach ( $options as $opt ) {
		if ( is_array( $opt ) ) {
			if ( $opt[0] === $selected ) {
				$tags[] = $opt[1];
			}
		}
	}

}

/**
 * Callback for multiselect & checkboxes
 *
 * @param $field       array
 * @param $posted_data Posted_Data
 * @param $data        array
 * @param $meta        array
 * @param $tags        array
 *
 * @return void
 */
function standard_multiselect_callback( $field, $posted_data, &$data, &$meta, &$tags ) {
	$selected               = map_deep( $posted_data[ $field['name'] ], 'sanitize_text_field' );
	$meta[ $field['name'] ] = $selected;

	$options = $field['options'];

	foreach ( $options as $opt ) {
		if ( is_array( $opt ) ) {
			if ( in_array( $opt[0], $selected ) ) {
				$tags[] = $opt[1];
			}
		}
	}
}

/**
 * Check if required data was passed
 *
 * @param             $field
 * @param Posted_Data $posted_data
 *
 * @return bool
 */
function basic_required_check( $field, Posted_Data $posted_data ) {
	$name = $field['name'] ?: $field['type'];

	return isset( $posted_data[ $name ] ) && ! empty( $posted_data[ $name ] );
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
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type'  => 'text',
						'name'  => 'first_name',
						'value' => $contact ? $contact->first_name : $field['value'],
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$first_name = $posted_data->first_name;
					$last_name  = $posted_data->last_name;

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
				'before'   => function ( $field, $posted_data, &$args ) {
					$args['first_name'] = sanitize_text_field( $posted_data->first_name );
				},
				'required' => function ( $field, $posted_data ) {
					return isset( $posted_data['first_name'] ) && ! empty( $posted_data['first_name'] );
				},
			],
			'last'         => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type'  => 'text',
						'name'  => 'last_name',
						'value' => $contact ? $contact->last_name : $field['value'],
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$first_name = $posted_data->first_name;
					$last_name  = $posted_data->last_name;

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
				'before'   => function ( $field, $posted_data, &$args ) {
					$args['last_name'] = sanitize_text_field( $posted_data->last_name );
				},
				'required' => function ( $field, $posted_data ) {
					return isset( $posted_data['last_name'] ) && ! empty( $posted_data['last_name'] );
				},
			],
			'email'        => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type'  => 'email',
						'name'  => 'email',
						'value' => $contact ? $contact->email : $field['value'],
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$email = $posted_data->email;

					return is_email( $email ) ? true : new \WP_Error( 'invalid_email', __( 'Invalid email address', 'groundhogg' ) );
				},
				'before'   => function ( $field, $posted_data, &$args ) {
					$args['email'] = sanitize_email( $posted_data->email );
				}
			],
			'phone'        => [
				'render'   => function ( $field, $contact ) {
					$field = wp_parse_args( $field, [
						'phone_type' => 'primary'
					] );

					$name = $field['phone_type'] . '_phone';

					return basic_text_field( array_merge( $field, [
						'type'  => 'tel',
						'name'  => $name,
						'value' => $contact ? $contact->$name : $field['value'],
					] ) );
				},
				'validate' => '__return_true',
				'required' => function ( $field, $posted_data ) {
					$type = $field['phone_type'] . '_phone';

					return isset( $posted_data[ $type ] ) && ! empty( $posted_data[ $type ] );
				},
				'before'   => function ( $field, $posted_data, &$args, &$meta ) {

					$type          = $field['phone_type'] . '_phone';
					$meta[ $type ] = sanitize_text_field( $posted_data->$type );

					// unformatted version
					$meta[ '__' . $type ] = preg_replace( "/[^0-9]/", "", $posted_data->$type );
				}
			],
			'line1'        => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type'  => 'text',
						'name'  => 'line1',
						'value' => $contact ? $contact->street_address_1 : $field['value'],
					] ) );
				},
				'validate' => '__return_true',
				'before'   => function ( $field, $posted_data, &$args, &$meta ) {
					$meta['street_address_1'] = sanitize_text_field( $posted_data->line1 );
				}
			],
			'line2'        => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type'  => 'text',
						'name'  => 'line2',
						'value' => $contact ? $contact->street_address_2 : $field['value'],
					] ) );
				},
				'validate' => '__return_true',
				'before'   => function ( $field, $posted_data, &$args, &$meta ) {
					$meta['street_address_2'] = sanitize_text_field( $posted_data->line2 );
				}
			],
			'city'         => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type'  => 'text',
						'name'  => 'city',
						'value' => $contact ? $contact->city : $field['value']
					] ) );
				},
				'validate' => '__return_true',
				'before'   => function ( $field, $posted_data, &$args, &$meta ) {
					$meta['city'] = sanitize_text_field( $posted_data->city );
				}
			],
			'state'        => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type'  => 'text',
						'name'  => 'state',
						'value' => $contact ? $contact->region : $field['value']
					] ) );
				},
				'validate' => '__return_true',
				'before'   => function ( $field, $posted_data, &$args, &$meta ) {
					$meta['region'] = sanitize_text_field( $posted_data->state );
				}
			],
			'zip_code'     => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type'  => 'text',
						'name'  => 'zip_code',
						'value' => $contact ? $contact->postal_zip : $field['value']
					] ) );
				},
				'validate' => '__return_true',
				'before'   => function ( $field, $posted_data, &$args, &$meta ) {
					$meta['postal_zip'] = sanitize_text_field( $posted_data->zip_code );
				}
			],
			'country'      => [
				'render'   => function ( $field, $contact ) {

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
						'selected'    => $contact ? $contact->country : $field['value'],
						'options'     => utils()->location->get_countries_list()
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					return key_exists( $posted_data['country'], utils()->location->get_countries_list() ) ? true : new \WP_Error( 'invalid_country', __( 'Invalid country selected', 'groundhogg' ) );
				},
				'before'   => function ( $field, $posted_data, &$args, &$meta ) {
					$meta['country'] = sanitize_text_field( $posted_data->country );
				}
			],
			'gdpr'         => [
				'render'   => function ( $field, $contact ) {

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
								'checked'  => $contact && $contact->has_gdpr_consent()
							] ), 'div' ),
						html()->wrap(
							html()->checkbox( [
								'label'    => sprintf( __( 'I agree to receive marketing offers and updates from %s.', 'groundhogg' ), $business_name ),
								'id'       => 'marketing-consent',
								'name'     => 'marketing_consent',
								'required' => false,
								'value'    => 'yes',
								'checked'  => $contact && $contact->has_gdpr_consent( 'marketing' )
							] ), 'div' )
					] );
				},
				'validate' => function ( $field, $posted_data ) {
					if ( $posted_data->data_processing_consent !== 'yes' ) {
						return new \WP_Error( 'error', __( 'You must consent to storage and processing of your data.', 'groundhogg' ) );
					}

					return true;
				},
				'after'    => function ( $field, Posted_Data $posted_data, Contact $contact ) {
					$contact->set_gdpr_consent();

					if ( $posted_data->marketing_consent === 'yes' ) {
						$contact->set_marketing_consent();
					}
				},
				'required' => '__return_true'
			],
			'terms'        => [
				'render'   => function ( $field, $contact ) {

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
							'checked'  => $contact && $contact->agreed_to_terms()
						] )
					] );
				},
				'validate' => function ( $field, $posted_data ) {
					if ( $posted_data->terms_and_conditions !== 'yes' ) {
						return new \WP_Error( 'error', __( 'You must agree to the terms and conditions.', 'groundhogg' ) );
					}

					return true;
				},
				'after'    => function ( $field, Posted_Data $posted_data, Contact $contact ) {
					$contact->set_terms_agreement();
				},
				'required' => '__return_true'
			],
			'text'         => [
				'render' => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'text',
					] ), $contact );
				},
				'before' => __NAMESPACE__ . '\standard_meta_callback',
			],
			'url'          => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'url',
					] ), $contact );
				},
				'validate' => function ( $field, $posted_data ) {
					return filter_var( $posted_data[ $field['name'] ], FILTER_VALIDATE_URL ) ? true : new \WP_Error( 'invalid_url', __( 'Invalid URL', 'groundhogg' ) );
				},
				'before'   => __NAMESPACE__ . '\standard_meta_callback',
			],
			'date'         => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'date',
					] ), $contact );
				},
				'validate' => function ( $field, $posted_data ) {
					return strtotime( $posted_data[ $field['name'] ] ) > 0 ? true : new \WP_Error( 'invalid_date', __( 'Invalid Date', 'groundhogg' ) );
				},
				'before'   => __NAMESPACE__ . '\standard_meta_callback',
			],
			'time'         => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'time',
					] ), $contact );
				},
				'validate' => function ( $field, $posted_data ) {
					$d = \DateTime::createFromFormat( "Y-m-d H:i:s", "2017-12-01 {$posted_data[ $field['name'] ]}" );

					return $d && $d->format( 'H:i:s' ) == $posted_data[ $field['name'] ] ? true : new \WP_Error( 'invalid_time', __( 'Invalid Time', 'groundhogg' ) );
				},
				'before'   => __NAMESPACE__ . '\standard_meta_callback',
			],
			'number'       => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'number',
					] ), $contact );
				},
				'validate' => function ( $field, $posted_data ) {
					return is_numeric( $posted_data[ $field['name'] ] ) ? true : new \WP_Error( 'invalid_number', __( 'Invalid number.', 'groundhogg' ) );
				},
				'before'   => function ( $field, $posted_data, &$data, &$meta ) {
					$num                    = $posted_data[ $field['name'] ];
					$meta[ $field['name'] ] = strpos( $num, '.' ) !== false ? floatval( $num ) : intval( $num );
				},
			],
			'textarea'     => [
				'render'   => function ( $field, $contact ) {

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
						'value'       => $contact ? $contact->get_meta( $field['name'] ) : $field['value'],
					] ) );
				},
				'validate' => '__return_true',
				'before'   => function ( $field, $posted_data, &$data, &$meta ) {
					$meta[ $field['name'] ] = sanitize_textarea_field( $posted_data[ $field['name'] ] );
				}
			],
			'dropdown'     => [
				'render'   => function ( $field, $contact ) {

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

					$options = array_map( function ( $opt ) {
						return is_array( $opt ) ? $opt[0] : $opt;
					}, $field['options'] );

					return basic_field( $field, html()->dropdown( [
						'id'          => $field['id'],
						'name'        => $field['name'],
						'class'       => trim( 'gh-input ' . $field['className'] ),
						'option_none' => $field['placeholder'],
						'required'    => $field['required'],
						'selected'    => $contact ? $contact->get_meta( $field['name'] ) : $field['value'],
						'options'     => array_combine( $options, $options )
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$options = array_map( function ( $opt ) {
						return is_array( $opt ) ? $opt[0] : $opt;
					}, $field['options'] );

					return in_array( $posted_data[ $field['name'] ], $options ) ? true : new \WP_Error( 'invalid_selection', __( 'Invalid selection', 'groundhogg' ) );
				},
				'before'   => __NAMESPACE__ . '\standard_dropdown_callback'
			],
			'radio'        => [
				'render'   => function ( $field, $contact ) {
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
						], array_map( function ( $opt ) use ( $field, $contact ) {

							$opt = is_array( $opt ) ? $opt[0] : $opt;

							return html()->wrap( html()->checkbox( [
								'type'    => 'radio',
								'label'   => $opt,
								'name'    => $field['name'],
								'value'   => $opt,
								'checked' => $contact ? $contact->get_meta( $field['name'] ) === $opt : $field['value'] === $opt
							] ) );

						}, $field['options'] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$options = array_map( function ( $opt ) {
						return is_array( $opt ) ? $opt[0] : $opt;
					}, $field['options'] );

					return empty( $posted_data[ $field['name'] ] ) || in_array( $posted_data[ $field['name'] ], $options ) ? true : new \WP_Error( 'invalid_selection', __( 'Invalid selection', 'groundhogg' ) );
				},
				'before'   => __NAMESPACE__ . '\standard_dropdown_callback'
			],
			'checkboxes'   => [
				'render'   => function ( $field, $contact ) {

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

					$selected = $contact ? $contact->get_meta( $field['name'] ) : [];

					// Force to array
					if ( ! is_array( $selected ) ) {
						$selected = [];
					}

					return html()->e( 'label', [
							'for' => $field['id']
						], $field['label'] ) . html()->e( 'div', [
							'class' => trim( 'gh-checkboxes ' . $field['className'] )
						], array_map( function ( $opt ) use ( $field, $contact, $selected ) {

							$opt = is_array( $opt ) ? $opt[0] : $opt;

							return html()->wrap( html()->checkbox( [
								'label'   => $opt,
								'name'    => $field['name'] . '[]',
								'value'   => $opt,
								'checked' => $contact ? in_array( $opt, $selected ) : $field['value'] === $opt
							] ) );

						}, $field['options'] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$options = array_map( function ( $opt ) {
						return is_array( $opt ) ? $opt[0] : $opt;
					}, $field['options'] );

					$selections = $posted_data[ $field['name'] ] ?: [];

					return count( array_intersect( $selections, $options ) ) === count( $selections ) ? true : new \WP_Error( 'invalid_selections', __( 'Invalid selections', 'groundhogg' ) );
				},
				'before'   => __NAMESPACE__ . '\standard_multiselect_callback'
			],
			'checkbox'     => [
				'render'   => function ( $field, $contact ) {

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
						'checked'  => $contact && $contact->get_meta( $field['name'] ) == ( $field['value'] ?: 1 ),
						'value'    => $field['value'] ?: '1',
					] );
				},
				'validate' => '__return_true',
				'before'   => __NAMESPACE__ . '\standard_meta_callback'
			],
			'file'         => [],
			'custom_field' => [
				'render'   => function ( $field, $contact ) {
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
					] ), $contact );
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
				},
				'before'   => function ( $field, $posted_data, &$data, &$meta, &$tags ) {
					$property = $field['property'];
					$property = Properties::instance()->get_field( $property );
					if ( ! $property ) {
						return;
					}

					return Form_v2::before_create_contact( array_merge( $property, [
						'value'     => $field['value'],
						'id'        => $field['id'],
						'className' => $field['className'],
						'required'  => $field['required'],
					] ), $posted_data, $data, $meta, $tags );
				},
				'required' => function ( $field, $posted_data ) {
					$property = $field['property'];
					$property = Properties::instance()->get_field( $property );
					if ( ! $property ) {
						return false;
					}

					return Form_v2::check_field_isset( array_merge( $property, [
						'value'     => $field['value'],
						'id'        => $field['id'],
						'className' => $field['className'],
						'required'  => $field['required'],
					] ), $posted_data );
				},
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
			'recaptcha'    => [
				'render'   => function ( $field ) {

					if ( current_user_can( 'edit_contacts' ) ) {
						return '';
					}

					$version = get_option( 'gh_recaptcha_version', 'v2' ) ?: 'v2';

					if ( $version === 'v2' ) {

						wp_enqueue_script( 'google-recaptcha' );

						return html()->e( 'div', [
							'class'        => 'g-recaptcha',
							'data-sitekey' => get_option( 'gh_recaptcha_site_key', '' ),
						], '', false );

					} else {
						wp_enqueue_script( 'groundhogg-google-recaptcha' );

						return html()->e( 'div', [
							'class' => 'g-recaptcha-v3',
							'style' => [
								'display' => 'none'
							]
						], '', false );
					}

				},
				'validate' => function ( $field, $posted_data ) {

					if ( current_user_can( 'edit_contacts' ) ) {
						return true;
					}

					$version = get_option( 'gh_recaptcha_version', 'v2' ) ?: 'v2';

					$file_name = sprintf(
						"https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s",
						get_option( 'gh_recaptcha_secret_key' ),
						$posted_data['g-recaptcha-response']
					);

					$verifyResponse = wp_remote_get( $file_name );
					$responseData   = json_decode( wp_remote_retrieve_body( $verifyResponse ) );

					$bot_error = new \WP_Error( 'captcha_verification_failed', _x( 'Failed reCAPTCHA verification. You are probably a robot.', 'submission_error', 'groundhogg' ) );

					if ( $responseData->success == false ) {
						return $bot_error;
					}

					// Check the score...
					if ( $version === 'v3' ) {
						$score = get_array_var( $responseData, 'score' );

						if ( ! $score ) {
							return $bot_error;
						}

						$score_threshold = floatval( apply_filters( 'groundhogg/recaptcha/v3/score_threshold', get_option( 'gh_recaptcha_v3_score_threshold', 0.5 ) ) );
						$score_threshold = $score_threshold ?: 0.5;

						if ( $score < $score_threshold ) {
							return $bot_error;
						}
					}


					return true;
				},
				'required' => '__return_true'
			],
		];

		foreach ( $fields as $type => $callbacks ) {

			$callbacks = wp_parse_args( $callbacks, [
				'render'   => '__return_empty_string',
				'validate' => '__return_true',
				'before'   => '__return_null',
				'after'    => '__return_null',
				'required' => __NAMESPACE__ . '\basic_required_check'
			] );

			self::register_field( $type, $callbacks['render'], $callbacks['validate'], $callbacks['before'], $callbacks['after'], $callbacks['required'] );
		}

		do_action( 'groundhogg/form/register_fields' );

	}

	/**
	 * Register a form field
	 *
	 * @param $type     string
	 * @param $render   callable
	 * @param $validate callable
	 * @param $after    callable
	 * @param $before   callable
	 *
	 * @return void
	 */
	public static function register_field(
		$type,
		$render,
		$validate,
		$before,
		$after,
		$required
	) {

		if ( ! is_callable( $render ) ) {
			return;
		}

		self::$fields[ $type ] = [
			'type'                  => $type,
			'render'                => $render,
			'validate'              => $validate,
			'before_create_contact' => $before,
			'after_create_contact'  => $after,
			'check_required'        => $required,
		];
	}

	/**
	 * Contact being used for the submission
	 *
	 * @var Contact
	 */
	protected $contact = false;

	/**
	 * Manager constructor.
	 */
	public function __construct( $atts ) {

		// Map to an array if only ID is passed
		if ( is_numeric( $atts ) ) {
			$atts = [
				'id' => $atts
			];
		}

		$atts = shortcode_atts( [
			'class'   => '',
			'id'      => 0,
			'contact' => 0,
			'fill'    => false
		], $atts );

		// Init the fields for the first time if empty
		if ( empty( self::$fields ) ) {
			self::register_fields();
		}

		$id = $atts['id'];

		if ( is_string( $id ) && ! is_numeric( $id ) ) {
			$id = absint( get_db( 'stepmeta' )->get_column_by( 'step_id', 'meta_value', $id ) );
		}

		// This will enable the auto population of form fields for admin submissions
		if ( $atts['contact'] ) {
			$contact = get_contactdata( $atts['contact'] );
			if ( $contact && current_user_can( 'edit_contact', $contact ) ) {
				$this->contact = $contact;
			}
		}

		if ( $atts['fill'] && current_contact_and_logged_in_user_match() ) {
			$this->contact = get_contactdata();
		}

		// Init step as normal
		parent::__construct( $id );
	}

	/**
	 * Get the form uuid
	 *
	 * @return string
	 */
	public function get_uuid() {
		return $this->get_meta( 'uuid' );
	}

	/**
	 * Render the shortcode tag
	 *
	 * @return string
	 */
	public function get_shortcode() {
		return sprintf( '[gh_form id="%d"]', $this->get_id() );
	}

	/**
	 * Iframe embed code which can be used on third party sites
	 *
	 * @return string
	 */
	public function get_iframe_embed_code() {
		$form_iframe_url = managed_page_url( sprintf( 'forms/iframe/%s/', $this->get_uuid() ) );

		return sprintf( '<script id="%s" type="text/javascript" src="%s"></script>', 'groundhogg_form_' . $this->get_id(), $form_iframe_url );
	}

	/**
	 * The submission url where the form can be directly accessed through the managed page
	 *
	 * @return string|void
	 */
	public function get_submission_url() {
		return managed_page_url( sprintf( 'forms/%s/submit/', $this->get_uuid() ) );
	}

	public function get_name(){
		return $this->get_meta('form_name') ?: wp_strip_all_tags( $this->get_step_title() );
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
			'name'    => $this->get_name()
		];

		$form .= sprintf( "<form %s>", array_to_atts( $atts ) );

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
	 * Render out the actual input field
	 * If the contact object is actually passed, we are assuming an admin submission in which case the value should be set to the current contact value
	 *
	 * @param              $field
	 * @param Contact|bool $contact
	 *
	 * @return false|mixed|string
	 */
	public static function render_input( $field, $contact = false ) {
		$type = $field['type'];

		$field_type = get_array_var( self::$fields, $type );

		if ( ! $field_type ) {
			return '';
		}

		return call_user_func( $field_type['render'], $field, $contact );
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

		$inner_html = self::render_input( $field, $this->contact );

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

		if ( ! $this->exists() ) {
			return sprintf( "<p>%s</p>", __( "<b>Configuration Error:</b> This form has been deleted." ) );
		}

		wp_enqueue_script( 'groundhogg-ajax-form' );

		$atts = [
			'method'  => 'post',
			'class'   => 'gh-form ajax-submit',
			'target'  => '_parent',
			'enctype' => 'multipart/form-data',
			'name'    => $this->get_name(),
			'id'      => $this->get_id()
		];

		if ( get_query_var( 'doing_iframe' ) ) {
			$atts['action'] = $this->get_submission_url();
		}

		$form .= sprintf( "<form %s>", array_to_atts( $atts ) );

		$form .= '<div class="gh-form-fields">';

		$form .= do_replacements( $this->get_field_html(), $this->contact ?: get_contactdata() );

		$form .= '</div>';
		$form .= '</form>';

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
			'name'          => $this->get_name(),
			'rendered'      => $this->shortcode(),
			'embed_methods' => [
				'html'   => $this->get_html_embed_code(),
				'iframe' => $this->get_iframe_embed_code(),
				'url'    => $this->get_submission_url()
			]
		];
	}

	/**
	 * Validates a field
	 *
	 * @param $field
	 * @param $posted_data
	 *
	 * @return bool|\WP_Error
	 */
	public static function validate_field( $field, $posted_data ) {
		$type = $field['type'];

		$field_type = get_array_var( self::$fields, $type );

		if ( ! $field_type ) {
			return false;
		}

		return call_user_func( $field_type['validate'], $field, $posted_data );
	}

	/**
	 * Checks whether a field isset or not if its required
	 *
	 * @param $field
	 * @param $posted_data
	 *
	 * @return bool|\WP_Error
	 */
	public static function check_field_isset( $field, $posted_data ) {
		$type = $field['type'];

		$field_type = get_array_var( self::$fields, $type );

		if ( ! $field_type ) {
			return false;
		}

		return call_user_func( $field_type['check_required'], $field, $posted_data );
	}

	/**
	 * Validates a field
	 *
	 * @param $field
	 * @param $posted_data
	 * @param $data array contact data
	 * @param $meta array contact meta
	 * @param $tags array contact tags
	 *
	 * @return false|mixed|string
	 */
	public static function before_create_contact( $field, $posted_data, &$data, &$meta, &$tags ) {
		$type = $field['type'];

		$field_type = get_array_var( self::$fields, $type );

		if ( ! $field_type ) {
			return false;
		}

		return call_user_func_array( $field_type['before_create_contact'], [
			$field,
			$posted_data,
			&$data,
			&$meta,
			&$tags,
		] );
	}

	/**
	 * Validates a field
	 *
	 * @param $field
	 * @param $posted_data
	 * @param $contact Contact
	 *
	 * @return false|mixed|string
	 */
	public static function after_create_contact( $field, $posted_data, $contact ) {
		$type = $field['type'];

		$field_type = get_array_var( self::$fields, $type );

		if ( ! $field_type ) {
			return false;
		}

		return call_user_func_array( $field_type['after_create_contact'], [
			$field,
			$posted_data,
			$contact,
		] );
	}

	/**
	 * Whether to submit this form via ajax
	 *
	 * @return bool
	 */
	public function is_ajax_submit() {
		return (bool) $this->get_meta( 'enable_ajax' );
	}

	/**
	 * The page where contacts are sent after a successful submission
	 *
	 * @return string
	 */
	public function get_success_url() {
		$message = $this->get_meta( 'success_page' );

		return do_replacements( $message, get_contactdata() );
	}

	/**
	 * The message shown to contacts after a successful submission
	 *
	 * @return string
	 */
	public function get_success_message() {
		$message = $this->get_meta( 'success_message' );

		return wpautop( do_replacements( $message, get_contactdata() ) );
	}

	/**
	 * Submit the form
	 *
	 * @return Contact|bool
	 */
	public function submit() {

		$posted_data = new Posted_Data();

		$config    = $this->get_meta( 'form' );
		$fields    = $config['fields'];
		$recaptcha = $config['recaptcha'];

		if ( $recaptcha['enabled'] ) {
			$fields[] = $recaptcha;
		}

		foreach ( $fields as $field ) {

			if ( isset_not_empty( $field, 'required' ) ) {

				$isset = self::check_field_isset( $field, $posted_data );

				if ( ! $isset ) {
					$this->add_error( new \WP_Error( 'field-required', __( 'This field is required' ), $field['label'] ) );
					continue;
				}
			}

			$result = self::validate_field( $field, $posted_data );

			if ( is_wp_error( $result ) ) {
				$result->add_data( $field['label'] );
				$this->add_error( $result );
			}
		}

		if ( $this->has_errors() ) {
			return false;
		}

		$data = [];
		$meta = [];
		$tags = [];

		foreach ( $fields as $field ) {
			self::before_create_contact( $field, $posted_data, $data, $meta, $tags );
		}

		$email = get_array_var( $data, 'email' );

		if ( ! $email ) {
			$contact = get_current_contact();
		} else {
			$contact = new Contact( $data );
		}

		if ( ! $contact || ! $contact->exists() ) {
			$this->add_error( 'db_error', __( 'Unable to create contact record.', 'groundhogg' ) );

			return false;
		}

		$contact->update( $data );
		$contact->update_meta( $meta );
		$contact->add_tag( $tags );

		foreach ( $fields as $field ) {
			self::after_create_contact( $field, $posted_data, $contact );
		}

		// Create the submission
		$submission = new Submission( [
			'step_id'    => $this->get_id(),
			'contact_id' => $contact->get_id()
		] );

		// Add the submission data.
		$submission_data = array_merge( $data, $meta );
		$submission->add_posted_data( $submission_data );

		return $contact;
	}

	public function get_impressions_count( $start, $end ) {
		return get_db( 'form_impressions' )->count( [
			'form_id' => $this->get_id(),
			'before'  => $end,
			'after'   => $start
		] );
	}

	public function get_submissions_count( $start, $end ) {
		return get_db( 'submissions' )->count( [
			'form_id' => $this->get_id(),
			'before'  => $end,
			'after'   => $start
		] );
	}


}

class Posted_Data implements \ArrayAccess, \JsonSerializable {

	/**
	 * @var array
	 */
	protected $posted = [];

	public function __construct( $posted = [] ) {

		if ( empty( $posted ) ) {
			$posted = wp_unslash( $_POST );
		}

		$this->posted = $posted;
	}

	public function __set( $name, $value ) {
		$this->posted[ $name ] = $value;
	}

	public function __get( $name ) {
		return get_array_var( $this->posted, $name );
	}

	public function offsetExists( $offset ) {
		return isset( $this->posted[ $offset ] );
	}

	public function offsetGet( $offset ) {
		return $this->posted[ $offset ];
	}

	public function offsetSet( $offset, $value ) {
		$this->posted[ $offset ] = $value;
	}

	public function offsetUnset( $offset ) {
		unset( $this->posted[ $offset ] );
	}

	public function jsonSerialize() {
		return $this->posted;
	}
}
