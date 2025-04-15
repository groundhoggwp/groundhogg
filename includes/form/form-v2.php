<?php

namespace Groundhogg\Form;


use Groundhogg\Contact;
use Groundhogg\Properties;
use Groundhogg\Step;
use Groundhogg\Submission;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\array_filter_splice;
use function Groundhogg\array_find;
use function Groundhogg\array_to_atts;
use function Groundhogg\current_contact_and_logged_in_user_match;
use function Groundhogg\do_replacements;
use function Groundhogg\file_access_url;
use function Groundhogg\format_custom_field;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_current_contact;
use function Groundhogg\get_db;
use function Groundhogg\get_default_field_label;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use function Groundhogg\is_a_contact;
use function Groundhogg\is_recaptcha_enabled;
use function Groundhogg\isset_not_empty;
use function Groundhogg\managed_page_url;
use function Groundhogg\parse_tag_list;
use function Groundhogg\process_events;
use function Groundhogg\utils;
use function Groundhogg\Ymd;

/**
 * Wrapper for most field types
 *
 * @param $field
 * @param $input
 *
 * @return mixed|string
 */
function basic_field_with_label( $field, $input ) {

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
 * Returns a basic input field
 *
 * @param $field
 * @param $contact
 *
 * @return string
 */
function basic_input( $field, $contact, $tag = 'input' ) {

	$property = $field['name'];

	return call_user_func( [ html(), $tag ], [
		'id'          => $field['id'],
		'type'        => $field['type'],
		'name'        => $field['name'],
		'class'       => trim( 'gh-input ' . $field['className'] ),
		'placeholder' => $field['placeholder'],
		'required'    => $field['required'],
		'value'       => $contact ? ( $contact->$property ?: $field['value'] ) : $field['value'],
	] );
}

/**
 * Wrapper for basic text fields
 *
 * @param              $field
 * @param bool|Contact $contact
 *
 * @return mixed|string
 */
function basic_text_field( $field, $contact = false, $tag = 'input' ) {

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

	return basic_field_with_label( $field, basic_input( $field, $contact, $tag ) );
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
	if ( isset( $posted_data[ $field['name'] ] ) ) {
		$meta[ $field['name'] ] = sanitize_text_field( $posted_data[ $field['name'] ] );
	}
}

/**
 * Retrieve data from the submission given the name of the field
 *
 * @param Submission $submission
 * @param            $field
 *
 * @return array|mixed
 */
function standard_meta_retrieve( Submission $submission, $field ) {
	return $submission->get_meta( $field['name'] );
}

/**
 * Helper to push tags to the big tags array
 *
 * @param $tags array
 * @param $add  mixed
 *
 * @return void
 */
function push_tags( &$tags, $add ) {
	$tags = array_merge( $tags, parse_tag_list( $add ) );
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

	if ( isset_not_empty( $field, 'multiple' ) ) {
		standard_multiselect_callback( $field, $posted_data, $data, $meta, $tags );

		return;
	}

	$selected               = $posted_data[ $field['name'] ];
	$meta[ $field['name'] ] = $selected;

	// Maybe add tags based on selection
	$options = $field['options'];

	// Find the associated selected option
	$_selected = array_find( $options, function ( $option ) use ( $selected ) {
		return is_array( $option ) && $option[0] === $selected;
	} );

	// if found and is array with tag, add the tag
	if ( $_selected && ! empty( $_selected[1] ) ) {
		push_tags( $tags, $_selected[1] );
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

	$selections = $posted_data[ $field['name'] ];

	if ( ! is_array( $selections ) ) {
		return;
	}

	$options = array_map( function ( $option ) {
		return is_array( $option ) ? $option[0] : $option;
	}, $field['options'] );

	$selections             = map_deep( array_intersect( $selections, $options ), 'sanitize_text_field' );
	$meta[ $field['name'] ] = $selections;

	// Find associated tags and apply
	foreach ( $selections as $selection ) {

		$_selected = array_find( $field['options'], function ( $option ) use ( $selection ) {
			return is_array( $option ) && $option[0] === $selection;
		} );

		if ( $_selected && ! empty( $_selected[1] ) ) {
			push_tags( $tags, $_selected[1] );
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
	$name = isset_not_empty( $field, 'name' ) ? $field['name'] : $field['type'];

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
			// First Name
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
				'retrieve' => function ( Submission $submission ) {
					return $submission->first_name;
				}
			],

			// Last Name
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
				'retrieve' => function ( Submission $submission ) {
					return $submission->last_name;
				}
			],

			// Email
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
				},
				'retrieve' => function ( Submission $submission ) {
					return $submission->email;
				}
			],

			// Phone Number
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

					$field = wp_parse_args( $field, [
						'phone_type' => 'primary'
					] );

					$type = $field['phone_type'] . '_phone';

					return isset( $posted_data[ $type ] ) && ! empty( $posted_data[ $type ] );
				},
				'before'   => function ( $field, $posted_data, &$args, &$meta ) {
					$field = wp_parse_args( $field, [
						'phone_type' => 'primary'
					] );

					$type          = $field['phone_type'] . '_phone';
					$meta[ $type ] = sanitize_text_field( $posted_data->$type );
				},
				'retrieve' => function ( Submission $submission, $field ) {
					$field = wp_parse_args( $field, [
						'phone_type' => 'primary'
					] );

					$type = $field['phone_type'] . '_phone';

					return $submission->$type;
				}
			],

			// Address Line 1
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
				},
				'retrieve' => function ( Submission $submission ) {
					return $submission->street_address_1;
				}
			],

			// Address Line 2
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
				},
				'retrieve' => function ( Submission $submission ) {
					return $submission->street_address_2;
				}
			],

			// Address City
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
				},
				'retrieve' => function ( Submission $submission ) {
					return $submission->city;
				}
			],

			// Address State
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
				},
				'retrieve' => function ( Submission $submission ) {
					return $submission->region;
				}
			],

			// Address Zip
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
				},
				'retrieve' => function ( Submission $submission ) {
					return $submission->postal_zip;
				}
			],

			// Address Country
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

					return basic_field_with_label( $field, html()->dropdown( [
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
				},
				'retrieve' => function ( Submission $submission ) {
					return $submission->country ? utils()->location->get_countries_list( $submission->country ) : '';
				}
			],

			// GDPR Checkboxes
			'gdpr'         => [
				'render'   => function ( $field, $contact ) {

					$field = wp_parse_args( $field, [
						'id'        => '',
						'className' => '',
					] );

					return html()->e( 'div', [
						'class' => trim( 'consent ' . $field['className'] ),
						'id'    => $field['id']
					], [
						html()->wrap(
							html()->checkbox( [
								'label'    => get_default_field_label( 'gdpr_consent' ) . ' <span class="required">*</span>',
								'id'       => 'data-processing-consent',
								'name'     => 'data_processing_consent',
								'required' => true,
								'value'    => 'yes',
								'checked'  => $contact && $contact->has_gdpr_consent()
							] ), 'div' ),
						html()->wrap(
							html()->checkbox( [
								'label'    => get_default_field_label( 'marketing_consent' ),
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
				'before'   => function ( $field, $posted_data, &$args, &$meta, &$tags, &$submission ) {
					$submission['gdpr_consent']      = __( 'Yes' );
					$submission['marketing_consent'] = $posted_data->marketing_consent === 'yes' ? __( 'Yes' ) : __( 'No' );
				},
				'after'    => function ( $field, Posted_Data $posted_data, Contact $contact ) {
					$contact->set_gdpr_consent();

					if ( $posted_data->marketing_consent === 'yes' ) {
						$contact->set_marketing_consent();
					}
				},
				'required' => '__return_true',
				'retrieve' => function ( Submission $submission ) {
					return implode( '', [
						__( 'Data Processing Consent: Yes', 'groundhogg' ),
						'<br/>',
						$submission->marketing_consent === 'Yes' ? __( 'Marketing Consent: Yes', 'groundhogg' ) : __( 'Marketing Consent: No', 'groundhogg' )
					] );
				}
			],

			// Terms & Conditions checkbox
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
							'label'    => get_default_field_label( 'terms_agreement' ) . ' <span class="required">*</span>',
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
				'before'   => function ( $field, $posted_data, &$args, &$meta, &$tags, &$submission ) {
					$submission['terms'] = __( 'Yes' );
				},
				'after'    => function ( $field, Posted_Data $posted_data, Contact $contact ) {
					$contact->set_terms_agreement();
				},
				'required' => '__return_true',
				'retrieve' => function ( Submission $submission ) {
					return __( 'Terms: Yes', 'groundhogg' );
				}
			],

			// Generic Text
			'text'         => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'text',
					] ), $contact );
				},
				'before'   => __NAMESPACE__ . '\standard_meta_callback',
				'retrieve' => __NAMESPACE__ . '\standard_meta_retrieve',
			],

			// Generic Hidden
			'hidden'       => [
				'render'   => function ( $field, $contact ) {

					if ( empty( $field['id'] ) ) {
						$field['id'] = $field['name'];
					}

					$field['type'] = 'hidden';

					return basic_input( $field, $contact );
				},
				'before'   => __NAMESPACE__ . '\standard_meta_callback',
				'retrieve' => __NAMESPACE__ . '\standard_meta_retrieve',
			],

			// Generic Email
			'custom_email' => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'email',
					] ), $contact );
				},
				'validate' => function ( $field, $posted_data ) {
					$email = $posted_data[ $field['name'] ];

					return is_email( $email ) ? true : new \WP_Error( 'invalid_email', __( 'Invalid email address', 'groundhogg' ) );
				},
				'before'   => function ( $field, $posted_data, &$args, &$meta ) {
					$meta[ $field['name'] ] = sanitize_email( $posted_data[ $field['name'] ] );
				},
				'retrieve' => function ( Submission $submission, $field ) {
					$value = standard_meta_retrieve( $submission, $field );

					return html()->e( 'a', [
						'href' => 'mailto:' . $value
					], esc_html( $value ) );
				}
			],
			// Generic Tel
			'tel'          => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'tel',
					] ), $contact );
				},
				'before'   => __NAMESPACE__ . '\standard_meta_callback',
				'retrieve' => function ( Submission $submission, $field ) {
					$value = standard_meta_retrieve( $submission, $field );

					return html()->e( 'a', [
						'href' => 'tel:' . $value
					], esc_html( $value ) );
				}
			],
			// Generic URL
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
				'retrieve' => function ( Submission $submission, $field ) {
					$value = standard_meta_retrieve( $submission, $field );

					return html()->e( 'a', [
						'href' => $value
					], esc_html( $value ) );
				}
			],

			// Generic Date
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
				'retrieve' => function ( Submission $submission, $field ) {
					$value    = standard_meta_retrieve( $submission, $field );
					$dateTime = new DateTimeHelper( $value );

					return $dateTime->wpDateFormat();
				}
			],

			// Generic Date & Time
			'datetime'     => [
				'render'   => function ( $field, $contact ) {
					return basic_text_field( array_merge( $field, [
						'type' => 'datetime-local',
					] ), $contact );
				},
				'validate' => function ( $field, $posted_data ) {
					return strtotime( $posted_data[ $field['name'] ] ) > 0 ? true : new \WP_Error( 'invalid_date', __( 'Invalid Date', 'groundhogg' ) );
				},
				'before'   => __NAMESPACE__ . '\standard_meta_callback',
				'retrieve' => function ( Submission $submission, $field ) {
					$value    = standard_meta_retrieve( $submission, $field );
					$dateTime = new DateTimeHelper( $value );

					return $dateTime->wpDateTimeFormat();
				}
			],

			// Generic Time
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
				'retrieve' => function ( Submission $submission, $field ) {
					$value    = standard_meta_retrieve( $submission, $field );
					$dateTime = new DateTimeHelper( $value );

					return $dateTime->wpTimeFormat();
				}
			],

			// Generic Number
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
				'retrieve' => __NAMESPACE__ . '\standard_meta_retrieve',
			],

			// Generic Textarea
			'textarea'     => [
				'render'   => function ( $field, $contact ) {

					unset( $field['type'] );

					return basic_text_field( $field, $contact, 'textarea' );
				},
				'validate' => '__return_true',
				'before'   => function ( $field, $posted_data, &$data, &$meta ) {
					$meta[ $field['name'] ] = sanitize_textarea_field( $posted_data[ $field['name'] ] );
				},
				'retrieve' => __NAMESPACE__ . '\standard_meta_retrieve',
			],

			// Generic Dropdown
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

					$multiple = isset_not_empty( $field, 'multiple' );
					$name     = $field['name'];

					if ( $multiple ) {
						$name .= '[]';
					}

					$selected = false;

					if ( $field['value'] ) {
						$selected = array_map( 'trim', explode( ',', do_replacements( $field['value'], $contact ) ) );
					}

					return basic_field_with_label( $field, html()->dropdown( [
						'id'          => $field['id'],
						'name'        => $name,
						'class'       => trim( 'gh-input ' . $field['className'] ),
						'option_none' => $field['placeholder'],
						'required'    => $field['required'],
						'selected'    => $contact ? $contact->get_meta( $field['name'] ) : $selected,
						'options'     => array_combine( $options, $options ),
						'multiple'    => $multiple,
					] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$options = array_map( function ( $opt ) {
						return is_array( $opt ) ? $opt[0] : $opt;
					}, $field['options'] );

					$value = $posted_data[ $field['name'] ];

					// multiple options
					if ( isset_not_empty( $field, 'multiple' ) ) {
						if ( ! is_array( $value ) ) {
							return new \WP_Error( 'invalid_selection', __( 'Invalid selection', 'groundhogg' ) );
						}

						// All given values are in the options
						return count( $value ) === count( array_intersect( $options, $value ) ) ? true : new \WP_Error( 'invalid_selection', __( 'Invalid selection', 'groundhogg' ) );
					}

					return in_array( $value, $options ) ? true : new \WP_Error( 'invalid_selection', __( 'Invalid selection', 'groundhogg' ) );
				},
				'before'   => __NAMESPACE__ . '\standard_dropdown_callback',
				'retrieve' => function ( Submission $submission, $field ) {
					$value = standard_meta_retrieve( $submission, $field );

					return is_array( $value ) ? implode( ', ', $value ) : $value;
				}
			],

			// Generic Radio
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

					$selected = $contact ? $contact->get_meta( $field['name'] ) : do_replacements( $field['value'], $contact );

					$i = 0;

					return html()->e( 'label', [
							'for' => $field['id']
						], $field['label'] ) . html()->e( 'div', [
							'id'    => $field['id'],
							'class' => trim( 'gh-radio-buttons ' . $field['className'] )
						], array_map( function ( $opt ) use ( $field, $selected, &$i ) {

							$i ++;

							$opt = is_array( $opt ) ? $opt[0] : $opt;

							return html()->e( 'div', [
								'class' => 'radio-item'
							], html()->checkbox( [
								'type'    => 'radio',
								'label'   => $opt,
								'id'      => $field['id'] ? $field['id'] . '-' . $i : '',
								'name'    => $field['name'],
								'value'   => $opt,
								'checked' => $selected === $opt
							] ) );

						}, $field['options'] ) );
				},
				'validate' => function ( $field, $posted_data ) {
					$options = array_map( function ( $opt ) {
						return is_array( $opt ) ? $opt[0] : $opt;
					}, $field['options'] );

					return empty( $posted_data[ $field['name'] ] ) || in_array( $posted_data[ $field['name'] ], $options ) ? true : new \WP_Error( 'invalid_selection', __( 'Invalid selection', 'groundhogg' ) );
				},
				'before'   => __NAMESPACE__ . '\standard_dropdown_callback',
				'retrieve' => __NAMESPACE__ . '\standard_meta_retrieve',
			],

			// Generic Checkboxes
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

					$selected = $contact ? $contact->get_meta( $field['name'] ) : do_replacements( $field['value'], $contact );

					// Force to array
					if ( ! is_array( $selected ) ) {
						$selected = array_map( 'trim', explode( ',', $selected ) );
					}

					$i = 0;

					return html()->e( 'label', [
							'for' => $field['id']
						], $field['label'] ) . html()->e( 'div', [
							'class' => trim( 'gh-checkboxes ' . $field['className'] ),
							'id'    => $field['id'],
						], array_map( function ( $opt ) use ( $field, $contact, $selected, &$i ) {

							$i ++;
							$opt = is_array( $opt ) ? $opt[0] : $opt;

							return html()->e( 'div', [
								'class' => 'checkbox-item'
							], html()->checkbox( [
								'label'   => $opt,
								'id'      => $field['id'] ? $field['id'] . '-' . $i : '',
								'name'    => $field['name'] . '[]',
								'value'   => $opt,
								'checked' => in_array( $opt, $selected )
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
				'before'   => __NAMESPACE__ . '\standard_multiselect_callback',
				'retrieve' => function ( Submission $submission, $field ) {
					$value = standard_meta_retrieve( $submission, $field );

					if ( ! is_array( $value ) ) {
						return $value;
					}

					return implode( ', ', $value );
				}
			],

			// Generic Checkbox
			'checkbox'     => [
				'render'   => function ( $field, $contact ) {

					$field = wp_parse_args( $field, [
						'id'        => '',
						'name'      => '',
						'className' => '',
						'required'  => false,
						'value'     => '1',
						'label'     => '',
						'checked'   => false,
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
						'checked'  => $contact && $contact->get_meta( $field['name'] ) == ( $field['value'] ?: 1 ) || $field['checked'],
						'value'    => $field['value'] ?: '1',
					] );
				},
				'validate' => '__return_true',
				'before'   => function ( $field, $posted_data, &$data, &$meta ) {
					// if the field is not set, it should be set to ''
					$meta[ $field['name'] ] = isset( $posted_data[ $field['name'] ] ) ? sanitize_text_field( $posted_data[ $field['name'] ] ) : '';
				},
				'after'    => function ( $field, $posted_data, $contact ) {
					if ( $posted_data->isset_not_empty( $field['name'] ) && ! empty( $field['tags'] ) ) {
						$contact->apply_tag( $field['tags'] );
					}
				},
				'retrieve' => __NAMESPACE__ . '\standard_meta_retrieve',
			],

			// File Upload
			'file'         => [
				'render'   => function ( $field, $contact ) {

					$field = wp_parse_args( $field, [
						'id'         => '',
						'name'       => '',
						'className'  => '',
						'required'   => false,
						'value'      => '',
						'label'      => '',
						'file_types' => [],
					] );

					$input = html()->input( [
						'type'     => 'file',
						'id'       => $field['id'],
						'name'     => $field['name'],
						'class'    => trim( implode( ' ', [ 'gh-file-uploader', $field['className'] ] ) ),
						'accept'   => implode( ',', array_map( function ( $type ) {
							return '.' . $type;
						}, $field['file_types'] ) ),
						'required' => $field['required'],
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

				},
				'validate' => function ( $field, $posted_data ) {
					$file_types = $field['file_types'];

					$validate = wp_check_filetype( $_FILES[ $field['name'] ]['name'] );

					if ( ! empty( $file_types ) && ! in_array( $validate['ext'], $file_types ) ) {
						return new \WP_Error( 'invalid_file_type', "Invalid file type given." );
					}

					return true;

				},
				'after'    => function ( $field, $posted_data, Contact $contact, &$submission ) {

					// file was not uploaded
					if ( ! isset( $_FILES[ $field['name'] ] ) ) {
						return;
					}

					$file = $contact->upload_file( $_FILES[ $field['name'] ] );

					// Something went wrong
					if ( is_wp_error( $file ) ) {
						return;
					}

					$submission[ $field['name'] ] = $file['file']; // store the filename
				},
				'required' => function ( $field, $posted_data ) {
					return isset_not_empty( $_FILES, $field['name'] );
				},
				'retrieve' => function ( Submission $submission, $field ) {
					$filename = basename( $submission->get_meta( $field['name'] ) );

					// No file was provided.
					if ( empty( $filename ) ) {
						return __( 'Not provided.', 'groundhogg' );
					}

					$access_url = file_access_url( '/uploads/' . $submission->get_contact()->get_upload_folder_basename() . '/' . $filename );

					if ( current_user_can( 'view_contact', $submission->get_contact() ) ) {
						$access_url = add_query_arg( 'contact', $submission->get_contact_id(), $access_url );
					}

					return html()->e( 'a', [
						'href' => $access_url
					], $filename );
				},
			],

			// Birthday
			'birthday'     => [
				'render'   => function ( $field, $contact ) {

					$selected_day   = false;
					$selected_year  = false;
					$selected_month = false;

					if ( $contact ) {

						$birthday       = $contact->get_meta( 'birthday' );
						$birthday_parts = [];

						if ( $birthday ) {
							$birthday_parts = explode( '-', $birthday );
						}

						list ( $selected_year, $selected_month, $selected_day ) = $birthday_parts;
					}

					$field = wp_parse_args( $field, [
						'id'        => 'birthday',
						'className' => '',
						'required'  => false,
						'label'     => '',
					] );

					$years  = array_reverse( range( date( 'Y' ) - 100, date( 'Y' ) ) );
					$years  = array_combine( $years, $years );
					$days   = range( 1, 31 );
					$days   = array_combine( $days, $days );
					$months = [];

					for ( $i = 1; $i <= 12; $i ++ ) {
						$timestamp    = mktime( 0, 0, 0, $i, 1, date( 'Y' ) );
						$months[ $i ] = date_i18n( "F", $timestamp );
					}

					$year  = html()->dropdown( [
						'name'        => 'birthday[year]',
						'id'          => 'birthday_year',
						'options'     => $years,
						'multiple'    => false,
						'option_none' => __( 'Year', 'groundhogg' ),
						'required'    => $field['required'],
						'class'       => 'gh-input',
						'selected'    => $selected_year
					] );
					$month = html()->dropdown( [
						'name'        => 'birthday[month]',
						'id'          => 'birthday_month',
						'options'     => $months,
						'multiple'    => false,
						'option_none' => __( 'Month', 'groundhogg' ),
						'required'    => $field['required'],
						'class'       => 'gh-input',
						'selected'    => $selected_month
					] );
					$day   = html()->dropdown( [
						'id'          => 'birthday_day',
						'name'        => 'birthday[day]',
						'options'     => $days,
						'multiple'    => false,
						'option_none' => __( 'Day', 'groundhogg' ),
						'required'    => $field['required'],
						'class'       => 'gh-input',
						'selected'    => $selected_day
					] );

					$date_format = get_option( 'date_format' );

					switch ( $date_format ) {
						case 'F j, Y':
						case 'm/d/Y':
							$inputs = [
								$month,
								$day,
								$year
							];
							break;
						case 'd/m/Y':
							$inputs = [
								$day,
								$month,
								$year
							];
							break;
						default:
							$inputs = [
								$year,
								$month,
								$day,
							];
							break;

					}

					$input = html()->e( 'div', [
						'id'    => $field['id'],
						'class' => [ 'gh-birthday gh-input-group', $field['className'] ]
					], $inputs );

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

				},
				'validate' => function ( $field, $posted_data ) {

					$input = $posted_data->birthday;

					if ( empty( $input ) ) {
						return false;
					}

					[ 'month' => $month, 'day' => $day, 'year' => $year ] = (array) $input;

					if ( ! checkdate( $month, $day, $year ) ) {
						return new \WP_Error( 'invalid_date', 'Please provide a valid date!' );
					}

					return true;
				},
				'before'   => function ( $field, $posted_data, &$data, &$meta ) {

					$input = $posted_data->birthday;

					if ( empty( $input ) ) {
						return false;
					}

					[ 'month' => $month, 'day' => $day, 'year' => $year ] = (array) $input;

					$date             = Ymd( mktime( 0, 0, 0, $month, $day, $year ) );
					$meta['birthday'] = $date;
				},
				'required' => function ( $field, $posted_data ) {
					$input = $posted_data->birthday;

					return ! empty( $input );
				},
				'retrieve' => function ( Submission $submission, $field ) {
					$value    = standard_meta_retrieve( $submission, $field );
					$dateTime = new DateTimeHelper( $value );

					return $dateTime->wpDateFormat();
				}
			],

			// Custom Fields
			'custom_field' => [
				'render'   => function ( $field, $contact ) {
					$property = $field['property'];
					$property = Properties::instance()->get_field( $property );
					if ( ! $property ) {
						return '';
					}

					return Form_v2::render_input( array_merge( $property, [
						'value'     => get_array_var( $field, 'value' ),
						'id'        => get_array_var( $field, 'id' ),
						'label'     => get_array_var( $field, 'label' ),
						'className' => get_array_var( $field, 'className' ),
						'required'  => get_array_var( $field, 'required' ),
					] ), $contact );
				},
				'validate' => function ( $field, $posted_data ) {
					$property = $field['property'];
					$property = Properties::instance()->get_field( $property );
					if ( ! $property ) {
						return new \WP_Error( 'invalid_property', 'something went wrong' );
					}

					return Form_v2::validate_input( array_merge( $property, [
						'value'     => $field['value'],
						'id'        => $field['id'],
						'className' => $field['className'],
						'required'  => $field['required'],
					] ), $posted_data );
				},
				'before'   => function ( $field, $posted_data, &$data, &$meta, &$tags, &$submission ) {
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
					] ), $posted_data, $data, $meta, $tags, $submission );
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
				'retrieve' => function ( Submission $submission, $field ) {
					$property = $field['property'];
					$property = Properties::instance()->get_field( $property );
					if ( ! $property ) {
						return false;
					}

					$name = $property['name'];

					return format_custom_field( $property, $submission->$name );
				}
			],

			// HTML & Text
			'html'         => [
				'render'   => function ( $field ) {

					$field = wp_parse_args( $field, [
						'id'        => '',
						'className' => '',
						'html'      => '',
					] );

					return html()->e( 'div', [
						'id'    => $field['id'],
						'class' => trim( $field['className'] ) . ' custom-html',
					], $field['html'], false );
				},
				'validate' => '__return_true'
			],

			// Button (1 per form)
			'button'       => [
				'render'   => function ( $field ) {

					$field = wp_parse_args( $field, [
						'id'        => '',
						'text'      => '',
						'className' => '',
					] );

					return html()->button( [
						'id'    => $field['id'],
						'class' => [ $field['className'], 'gh-submit', 'gh-button', 'primary' ],
						'type'  => 'submit',
						'text'  => $field['text']
					] );
				},
				'validate' => '__return_true'
			],

			// Google Recaptcha
			'recaptcha'    => [
				'render'   => function ( $field ) {

					$version = get_option( 'gh_recaptcha_version', 'v2' ) ?: 'v2';

					if ( $version === 'v2' ) {

						return html()->e( 'div', [
							'class'        => 'g-recaptcha',
							'data-sitekey' => get_option( 'gh_recaptcha_site_key', '' ),
							'data-theme'   => get_array_var( $field, 'captcha_theme', 'light' ),
							'data-size'    => get_array_var( $field, 'captcha_size', 'normal' ),
						], '', false );

					} else {

						return html()->e( 'div', [
							'class' => 'gh-recaptcha-v3',
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
				'required' => __NAMESPACE__ . '\basic_required_check',
				'retrieve' => '__return_null',
			] );

			self::register_field( $type,
				$callbacks['render'],
				$callbacks['validate'],
				$callbacks['before'],
				$callbacks['after'],
				$callbacks['required'],
				$callbacks['retrieve']
			);
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
		string $type,
		callable $render,
		callable $validate,
		callable $before,
		callable $after,
		callable $required,
		callable $retrieve
	) {

		if ( ! is_callable( $render ) ) {
			return;
		}

		self::$fields[ $type ] = [
			'type'                      => $type,
			'render'                    => $render,
			'validate'                  => $validate,
			'before_create_contact'     => $before,
			'after_create_contact'      => $after,
			'check_required'            => $required,
			'retrieve_submission_value' => $retrieve,
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

		if ( get_url_var( 'preview' ) && current_user_can( 'edit_funnels' ) ) {
			$this->merge_changes();
		}
	}

	/**
	 * Are we working with an existing contact record here?
	 *
	 * @return bool
	 */
	public function has_contact() {
		return is_a_contact( $this->contact );
	}

	/**
	 * Get the form uuid
	 *
	 * @return string
	 */
	public function get_uuid() {
		return $this->get_slug();
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

	public function get_name() {
		return $this->get_meta( 'form_name' ) ?: wp_strip_all_tags( $this->get_step_title() );
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
			'name'    => $this->get_name(),
			'data-id' => $this->get_id(),
		];

		$form .= sprintf( "<form %s>", array_to_atts( $atts ) );

		$form .= $this->get_field_html();

		if ( ! empty( $this->hidden_fields ) ) {
			$form .= $this->get_hidden_fields();
		}

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

		if ( empty( $field ) ) {
			return '';
		}

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
		], $inner_html, false );
	}

	protected $hidden_fields = [];

	/**
	 * Get the HTML For the fields
	 *
	 * @return string
	 */
	function get_field_html() {

		$config = $this->get_meta( 'form' );

		$config = json_decode( wp_json_encode( $config ), true );

		$fields = get_array_var( $config, 'fields', [] );

		// Filter out hidden fields
		$this->hidden_fields = array_filter_splice( $fields, function ( $field ) {
			return $field['type'] === 'hidden';
		} );

		$html = implode( '', array_map( [ $this, 'render_field' ], $fields ) );

		$recaptcha = get_array_var( $config, 'recaptcha', [] );
		$button    = get_array_var( $config, 'button', [] );

		if ( isset_not_empty( $recaptcha, 'enabled' ) && is_recaptcha_enabled() && get_option( 'gh_recaptcha_version' ) === 'v2' ) {
			$html .= $this->render_field( $recaptcha );
		}

		$html .= $this->render_field( $button );

		return $html;
	}

	/**
	 * Fetch any hidden fields for the form.
	 *
	 * @return string
	 */
	function get_hidden_fields() {
		if ( empty( $this->hidden_fields ) ) {
			return '';
		}

		$html = '';

		foreach ( $this->hidden_fields as $hidden_field ) {
			$html .= self::render_input( $hidden_field, $this->contact ?: get_contactdata() );
		}

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

		wp_enqueue_script( 'groundhogg-form-v2' );

		$attrs = [
			'method'  => 'post',
			'class'   => 'gh-form gh-form-v2',
			'target'  => '_parent',
			'enctype' => 'multipart/form-data',
			'name'    => $this->get_name(),
			'id'      => "gh-form-{$this->get_id()}",
			'data-id' => $this->get_id(),
		];

		$theme        = $this->get_meta( 'theme' );
		$accent_color = $this->get_meta( 'accent_color' );

		if ( ! $accent_color ) {
			$accent_color = '#000000'; // default to black
		}

		if ( $theme && $theme !== 'default' ) {
			$attrs['class'] .= ' ' . $theme;
			$attrs['style'] = [ '--gh-accent-color' => $accent_color ];
		}

		if ( get_query_var( 'doing_iframe' ) ) {
			$attrs['action'] = $this->get_submission_url();
		}

		$form .= sprintf( "<form %s>", array_to_atts( $attrs ) );

		$form .= '<div class="gh-form-fields">';

		$form .= do_replacements( $this->get_field_html(), $this->contact ?: get_contactdata() );

		$form .= '</div>';

		if ( ! empty( $this->hidden_fields ) ) {
			$form .= do_replacements( $this->get_hidden_fields(), $this->contact ?: get_contactdata() );
		}

		$config    = $this->get_meta( 'form' );
		$recaptcha = get_array_var( $config, 'recaptcha', [] );

		if ( isset_not_empty( $recaptcha, 'enabled' ) && is_recaptcha_enabled() && get_option( 'gh_recaptcha_version' ) === 'v3' ) {
			$form .= self::render_input( $recaptcha );
		}

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
	 * @param $data       array contact data
	 * @param $meta       array contact meta
	 * @param $tags       array contact tags
	 * @param $submission array any information to add to the submission
	 *
	 * @return false|mixed|string
	 */
	public static function before_create_contact( $field, $posted_data, &$data, &$meta, &$tags, &$submission ) {
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
			&$submission,
		] );
	}

	/**
	 * Validates a field
	 *
	 * @param             $field
	 * @param Posted_Data $posted_data
	 * @param Contact     $contact
	 * @param array       $submission
	 *
	 * @return false|mixed|string
	 */
	public static function after_create_contact( $field, Posted_Data $posted_data, Contact $contact, &$submission ) {
		$type = $field['type'];

		$field_type = get_array_var( self::$fields, $type );

		if ( ! $field_type || ! is_callable( $field_type['after_create_contact'] ) ) {
			return false;
		}

		return call_user_func_array( $field_type['after_create_contact'], [
			$field,
			$posted_data,
			$contact,
			&$submission
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
		$url = $this->get_meta( 'success_page' );
		$url = do_replacements( $url, get_contactdata() );

		// No https? must be a relative URL
		if ( ! preg_match( '@https?://@', $url ) ) {
			$url = home_url( $url );
		}

		/**
		 * @param $url  string
		 * @param $form Form_v2
		 */
		return apply_filters( 'groundhogg/form/v2/success_url', $url, $this );
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
	 * Return all fields
	 *
	 * @return mixed
	 */
	public function get_fields() {
		$config = json_decode( wp_json_encode( $this->get_meta( 'form' ) ), true );

		if ( ! is_array( $config ) || ! isset( $config['fields'] ) ) {
			return [];
		}

		return $config['fields'];
	}

	/**
	 * Submit the form
	 *
	 * @return Contact|bool
	 */
	public function submit() {

		// check if active
		if ( ! $this->is_active() ) {
			$this->add_error( 'inactive', __( 'This form is not accepting submissions.', 'groundhogg' ) );

			return false;
		}

		$posted_data = new Posted_Data();

		// Ensure array and not stdClass
		$config    = json_decode( wp_json_encode( $this->get_meta( 'form' ) ), true );
		$fields    = $config['fields'];
		$recaptcha = $config['recaptcha'];

		if ( $recaptcha['enabled'] ) {
			$fields[] = $recaptcha;
		}

		foreach ( $fields as $field ) {

			$isset = false;

			if ( isset_not_empty( $field, 'required' ) ) {

				$isset = self::check_field_isset( $field, $posted_data );

				if ( ! $isset ) {
					$this->add_error( new \WP_Error( 'field-required', __( 'This field is required' ), $field['label'] ) );
					continue;
				}
			}

			$result = null;

			if ( $isset ) {
				$result = self::validate_field( $field, $posted_data );
			}

			if ( is_wp_error( $result ) ) {
				$result->add_data( $field['label'] );
				$this->add_error( $result );
			}
		}

		if ( $this->has_errors() ) {
			return false;
		}

		$data                  = [];
		$meta                  = [];
		$tags                  = [];
		$submission_additional = []; // arbitrary additional information to add to the submission record

		foreach ( $fields as $field ) {
			self::before_create_contact( $field, $posted_data, $data, $meta, $tags, $submission_additional );
		}

		do_action_ref_array( 'groundhogg/form/v2/before_create_contact', [
			$posted_data,
			&$data,
			&$meta,
			&$tags,
			$this
		] );

		$email = get_array_var( $data, 'email' );

		if ( ! $email ) {
			$contact = is_a_contact( $this->contact ) ? $this->contact : get_current_contact();
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
			self::after_create_contact( $field, $posted_data, $contact, $submission_additional );
		}

		do_action_ref_array( 'groundhogg/form/v2/after_create_contact', [
			$posted_data,
			$contact
		] );

		// Create the submission
		$submission = new Submission();
		$submission->create( [
			'step_id'    => $this->get_id(),
			'contact_id' => $contact->get_id(),
			'name'       => $this->get_name()
		] );

		// Add the submission data.
		$submission_data = array_merge( $data, $meta, $submission_additional );
		$submission->add_posted_data( $submission_data );

		/**
		 * Trigger any benchmarks from here
		 *
		 * @param $submission Submission
		 * @param $form       Form_v2
		 * @param $contact    Contact
		 */
		do_action( 'groundhogg/form/v2/submit', $submission, $this, $contact );

		if ( $this->benchmark_enqueue( $contact, [
			'submission_id' => $submission->get_id(),
//			'form_id'       => $this->get_id()
		] ) ) {
			process_events( [ $contact ] );
		}

		return $contact;
	}

	/**
	 * Given a submission and a field, retrieve the value for the field from the submission
	 *
	 * @param Submission $submission
	 * @param            $field
	 *
	 * @return mixed|string
	 */
	public function retrieve_field_submission_answer( Submission $submission, $field ) {

		$type = $field['type'];

		$field_type = get_array_var( self::$fields, $type );

		if ( ! $field_type ) {
			return '';
		}

		return call_user_func( $field_type['retrieve_submission_value'], $submission, $field );
	}

	/**
	 * Given a submission, provide an array of field answers that includes the field label
	 *
	 * @param Submission $submission     the submission to pull answers from
	 * @param bool       $include_hidden whether to include hidden fields in the response
	 *
	 * @return array[]
	 */
	public function get_submission_answers( Submission $submission, bool $include_hidden = false ) {

		$fields = $this->get_fields();

		$answers = [];

		foreach ( $fields as $field ) {

			// ignore html blocks
			if ( $field['type'] === 'html' ) {
				continue;
			}

			// ignore hidden fields
			if ( $field['type'] === 'hidden' && ! $include_hidden ) {
				continue;
			}

			$answers[] = [
				'label' => isset_not_empty( $field, 'label' ) ? $field['label'] : ( $field['name'] ?? $field['type'] ),
				'value' => $this->retrieve_field_submission_answer( $submission, $field )
			];
		}

		return $answers;
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

	/**
	 * @param $offset
	 *
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->posted[ $offset ] );
	}

	/**
	 * @param $offset
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->posted[ $offset ];
	}

	/**
	 * @param $offset
	 * @param $value
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$this->posted[ $offset ] = $value;
	}

	/**
	 * @param $offset
	 *
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		unset( $this->posted[ $offset ] );
	}

	/**
	 * @return array|mixed|string
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->posted;
	}

	/**
	 * Wrapper for isset_not_empty
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function isset_not_empty( $key ) {
		return isset_not_empty( $this->posted, $key );
	}
}
