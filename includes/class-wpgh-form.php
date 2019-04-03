<?php

/**
 * Form
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Form
{
    /**
     * Form attributes
     *
     * @var array
     */
    var $a;

    /**
     * Form content
     *
     * @var string
     */
    var $content;

    /**
     * Form ID, also ID of the step
     *
     * @var int
     */
    var $id;

    /**
     * Array of field IDs given all the filds in the form
     *
     * @var array
     */
    var $fields = array();

    /**
     * This will contain a config object and all the settings of the form to compare against for security checks later on...
     *
     * @var array
     */
    var $config = array();

    /**
     * Full rendering of the form
     *
     * @var string
     */
    var $rendered;

	/**
	 * @var bool
	 */
    var $iframe_compat = false;

    /**
     * Whether to auto populate a form
     *
     * @var bool
     */
    var $auto_populate = false;

    /**
     * Where contact details are pulled from
     *
     * @var null WPGH_Contact
     */
    var $source_contact = null;

    /**
     * Whether the form is in preview mode
     *
     * @var bool
     */
    var $doing_preview = false;

    /**
     * Whether a previous submission failed.
     *
     * @var bool
     */
    var $submission_failed = false;

    public function __construct( $atts )
    {
        $this->a = shortcode_atts(array(
            'class'     => '',
            'id'        => 0
        ), $atts);

        if ( is_admin() && current_user_can( 'edit_contacts' ) && key_exists( 'contact', $_GET ) ){
            $this->auto_populate = true;
            $this->source_contact = wpgh_get_contact( intval( $_GET[ 'contact' ] ) );
        }

        $this->id = intval( $this->a[ 'id' ] );
        $this->add_scripts();
    }

    /**
     * Get data from a failed submission
     *
     * @param $key string
     * @return bool|string
     */
    private function get_submission_data( $key ){

        if ( key_exists( $key, WPGH()->submission->data ) ){
            return esc_html( WPGH()->submission->$key );
        }

        return false;

    }

	/**
	 * Set whether the form should have Iframe compatibility.
	 *
	 * @param $bool
	 */
    public function set_iframe_compat( $bool ){
    	$this->iframe_compat = (bool) $bool;
    }

	/**
	 * Add relevant form scripts.
	 */
    private function add_scripts()
    {
    	wp_enqueue_style( 'wpgh-frontend', WPGH_ASSETS_FOLDER . 'css/frontend.css', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/css/frontend.css' ) );
    }

    /**
     * Setup the shortcodes for the fields. ensures that
     * field shortcodes can only be called withing the form shortcode
     */
    private function setup_shortcodes()
    {
	    add_shortcode( 'col',        array( $this, 'column'     ) );
	    add_shortcode( 'row',        array( $this, 'row'        ) );
	    add_shortcode( 'first_name', array( $this, 'first_name'  ) );
	    add_shortcode( 'first',      array( $this, 'first_name'  ) );
        add_shortcode( 'last_name',  array( $this, 'last_name'   ) );
        add_shortcode( 'last',       array( $this, 'last_name'   ) );
        add_shortcode( 'file',       array( $this, 'file'        ) );
        add_shortcode( 'date',       array( $this, 'date'        ) );
        add_shortcode( 'time',       array( $this, 'time'        ) );
        add_shortcode( 'email',      array( $this, 'email'       ) );
        add_shortcode( 'phone',      array( $this, 'phone'       ) );
        add_shortcode( 'address',    array( $this, 'address'     ) );
        add_shortcode( 'text',       array( $this, 'text'        ) );
        add_shortcode( 'textarea',   array( $this, 'textarea'    ) );
        add_shortcode( 'number',     array( $this, 'number'      ) );
        add_shortcode( 'select',     array( $this, 'select'      ) );
        add_shortcode( 'dropdown',   array( $this, 'select'      ) );
        add_shortcode( 'radio',      array( $this, 'radio'       ) );
        add_shortcode( 'checkbox',   array( $this, 'checkbox'    ) );
        add_shortcode( 'terms',      array( $this, 'terms'       ) );
        add_shortcode( 'gdpr',       array( $this, 'gdpr'        ) );
        add_shortcode( 'recaptcha',  array( $this, 'recaptcha'   ) );
        add_shortcode( 'submit',     array( $this, 'submit'      ) );
        add_shortcode( 'email_preferences',  array( $this, 'email_preferences' ) );

        do_action( 'wpgh_setup_form_shortcodes', $this );
    }

    /**
     * remove the short codes when they are no longer relevant
     * field shortcodes can only be called withing the form shortcode
     */
    private function destroy_shortcodes()
    {
	    remove_shortcode( 'row'          );
	    remove_shortcode( 'col'          );
	    remove_shortcode( 'last_name'    );
	    remove_shortcode( 'last'         );
	    remove_shortcode( 'file'         );
	    remove_shortcode( 'date'         );
	    remove_shortcode( 'time'         );
	    remove_shortcode( 'first'        );
	    remove_shortcode( 'first_name'   );
        remove_shortcode( 'email'        );
        remove_shortcode( 'phone'        );
        remove_shortcode( 'address'      );
        remove_shortcode( 'text'         );
        remove_shortcode( 'number'       );
        remove_shortcode( 'select'       );
        remove_shortcode( 'dropdown'     );
        remove_shortcode( 'radio'        );
        remove_shortcode( 'checkbox'     );
        remove_shortcode( 'terms'        );
        remove_shortcode( 'recaptcha'    );
        remove_shortcode( 'email_preferences' );
        remove_shortcode( 'submit'       );

	    do_action( 'wpgh_destroy_form_shortcodes' );
    }

    /**
     * Check to ensure the form is real
     *
     * @return bool
     */
    private function is_form()
    {
//        return !empty( $this->a[ 'id' ] );

        return true;
    }

    private function field_wrap( $content )
    {
        return sprintf( "<div class='gh-form-field'>%s</div>", $content );
    }

    public function row( $atts, $content ){

        $a = shortcode_atts( array(
            'id'    => '',
            'class' => ''
        ), $atts );

//    	return sprintf( "<div id='%s' class='gh-form-row %s'>%s<div style=\"clear:both;\"></div></div>", $a['id'], $a['class'], do_shortcode( $content ) );
    	return sprintf( "<div id='%s' class='gh-form-row clearfix %s'>%s</div>", $a['id'], $a['class'], do_shortcode( $content ) );

    }
    public function column( $atts, $content ){

    	$a = shortcode_atts( array(
    	    'size'  => false,
    	    'width' => '1/2',
            'id'    => '',
            'class' => ''
        ), $atts );

    	//backwards compat for columns with the size attr
    	if ( $a[ 'size' ] ){
    	    $a[ 'width' ] = $a[ 'size' ];
        }

    	switch ( $a[ 'width' ] ){
		    case '1/1':
		    	$width = 'col-1-of-1';
		    	break;
            default:
		    case '1/2':
			    $width = 'col-1-of-2';
			    break;
		    case '1/3':
			    $width = 'col-1-of-3';
			    break;
		    case '2/3':
			    $width = 'col-2-of-3';
			    break;
		    case '1/4':
			    $width = 'col-1-of-4';
			    break;
		    case '3/4':
			    $width = 'col-3-of-4';
			    break;
	    }

	    return sprintf( "<div id='%s' class='gh-form-column %s %s'>%s</div>", $a['id'], $a['class'], $width, do_shortcode( $content ) );

    }

    /**
     * Returns a basic input structure for the majority of the fields
     *
     * @param $atts
     * @return string
     */
    private function input_base( $atts )
    {
        /* return nothing if the form doesn't exist */
        if ( ! $this->is_form() )
        {
            return '';
        }

        $a = shortcode_atts( array(
            'type'          => 'text',
            'label'         => '',
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'value'         => '',
            'placeholder'   => '',
            'title'         => '',
            'attributes'    => '',
            'required'      => false,
        ), $atts );

        $a[ 'name' ] = sanitize_key( strtolower( str_replace( ' ', '_', $a[ 'name' ] ) ) );

        if ( empty( $a[ 'name' ] ) ){

            $a[ 'name' ] = !empty( $a[ 'label' ] )? sanitize_key( $a[ 'label' ] ) : sanitize_key( $a[ 'placeholder' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = !empty( $a[ 'label' ] )? sanitize_key( $a[ 'label' ] ) : sanitize_key( $a[ 'placeholder' ] );
        }

        $this->fields[] = $a[ 'name' ];

        if ( ! isset( $this->config[ $a[ 'name' ] ] ) ){
            $this->config[ $a[ 'name' ] ] = $a;
        }

        $required = ( $a[ 'required' ] && $a[ 'required' ] !== "false" ) ? 'required' : '';

        /* Auto populate for admin submissions */
        if ( $this->auto_populate ){
            $name = $a[ 'name' ];
            if ( $this->source_contact->$name ){
                $a[ 'value' ] = $this->source_contact->$name;
            }
        }

        if ( $this->submission_failed ){
            $a[ 'value' ] = $this->get_submission_data( $a[ 'name' ] );
        }

        $field = sprintf(
            "<label class='gh-input-label'>%s <input type='%s' name='%s' id='%s' class='gh-input %s' value='%s' placeholder='%s' title='%s' %s %s></label>",
            $a[ 'label' ],
            esc_attr( $a[ 'type' ] ),
            esc_attr( $a[ 'name' ] ),
            esc_attr( $a[ 'id' ] ),
            esc_attr( $a[ 'class' ] ),
            esc_attr( $a[ 'value' ] ),
            esc_attr( $a[ 'placeholder' ] ),
            esc_attr( $a[ 'title' ] ),
            $a[ 'attributes' ],
            $required
        );

        return $this->field_wrap( $field );
    }

    /**
     * HTML for plain text field
     *
     * @param $atts
     * @return string
     */
    public function text( $atts )
    {
        return $this->input_base( $atts );
    }

    /**
     * Return the first name field html
     *
     * @param $atts
     * @return string
     */
    public function first_name( $atts )
    {
        $a = shortcode_atts( array(
            'type'          => 'text',
            'label'         => _x( 'First Name *', 'form_default', 'groundhogg' ),
            'name'          => 'first_name',
            'id'            => 'first_name',
            'class'         => 'gh-first-name',
            'value'         => '',
            'placeholder'   => '',
            'attributes'    => 'pattern="[A-Za-z \-\']+"',
            'title'         => _x( 'Do not include numbers or special characters.', 'form_default', 'groundhogg' ),
            'required'      => false,
        ), $atts );

        $this->config[ $a[ 'name' ] ] = $a ;

        return $this->input_base( $a );
    }

    /**
     * Return the last name field HTML
     *
     * @param $atts
     * @return string
     */
    public function last_name( $atts )
    {
        $a = shortcode_atts( array(
            'type'          => 'text',
            'label'         => _x( 'Last Name *', 'form_default', 'groundhogg' ),
            'name'          => 'last_name',
            'id'            => 'last_name',
            'class'         => 'gh-last-name',
            'value'         => '',
            'placeholder'   => '',
            'attributes'    => 'pattern="[A-Za-z \-\']+"',
            'title'         => _x( 'Do not include numbers or special characters.', 'form_default', 'groundhogg' ),
            'required'      => false,
        ), $atts );

        $this->config[ $a[ 'name' ] ] = $a ;

        return $this->input_base( $a );
    }

    /**
     * Return the email field HTML
     *
     * @param $atts
     * @return string
     */
    public function email( $atts )
    {
        $a = shortcode_atts( array(
            'type'          => 'email',
            'label'         => _x( 'Email *', 'form_default', 'groundhogg' ),
            'name'          => 'email',
            'id'            => 'email',
            'class'         => 'gh-email',
            'value'         => '',
            'placeholder'   => '',
            'attributes'    => '',
            'required'      => true,
        ), $atts );

        $this->config[ $a[ 'name' ] ] = $a ;

        return $this->input_base( $a );
    }

    /**
     * Return HTML for the phone field
     *
     * @param $atts
     * @return string
     */
    public function phone( $atts )
    {
        $a = shortcode_atts( array(
            'type'          => 'tel',
            'label'         => _x( 'Phone *', 'form_default', 'groundhogg' ),
            'name'          => 'primary_phone',
            'id'            => 'primary_phone',
            'class'         => 'gh-tel',
            'value'         => '',
            'placeholder'   => '',
            'attributes'    => '',
            'required'      => false,
        ), $atts );

        $this->config[ $a[ 'name' ] ] = $a;

        return $this->input_base( $a );
    }

    /**
     * Return HTML for a file input
     *
     * @param $atts
     * @return string
     */
    public function file( $atts )
    {
        $a = shortcode_atts( array(
            'type'          => 'file',
            'label'         => _x( 'File *', 'form_default', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => 'gh-file-uploader',
            'max_file_size' => wp_max_upload_size(),
            'file_types'    => '',
            'required'      => false,
            'attributes'    => '',

        ), $atts );

        if ( ! empty( $a[ 'file_types' ] ) ){
            $a[ 'attributes' ] .= sprintf( ' accept="%s"', esc_attr( $a[ 'file_types' ] ) );
        }

        $this->config[ $a[ 'name' ] ] = $a;

        return  $this->input_base( $a );

    }

    /**
     * Return HTML for a date input
     *
     * @param $atts
     * @return string
     */
    public function date( $atts )
    {
        $a = shortcode_atts( array(
            'type'          => 'text',
            'label'         => _x( 'Date *', 'form_default', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'max_date'      => '',
            'min_date'      => '',
            'date_format'   => 'yy-mm-dd',
            'required'      => false,
            'attributes'    => '',

        ), $atts );

        if ( ! empty( $a[ 'max_date' ] ) ){
            $a[ 'attributes' ] .= sprintf( ' max="%s"', esc_attr( $a[ 'max_date' ] ) );
        }

        if ( ! empty( $a[ 'min_date' ] ) ){
            $a[ 'attributes' ] .= sprintf( ' min="%s"', esc_attr( $a[ 'min_date' ] ) );
        }

        $a[ 'id' ] = uniqid( 'date_' );

        $this->config[ $a[ 'name' ] ] = $a;

        $base = $this->input_base( $a );

        $base = sprintf( "%s<script>jQuery(function($){\$('#%s').datepicker({changeMonth: true,changeYear: true,minDate: '%s', maxDate: '%s',dateFormat:'%s'})});</script>", $base, $a[ 'id' ], esc_attr( $a[ 'min_date' ] ), esc_attr( $a[ 'max_date' ] ), esc_attr( $a[ 'date_format' ] ) );

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'jquery-ui' );

        return $base;
    }

    /**
     * Return HTML for a time input
     *
     * @param $atts
     * @return string
     */
    public function time( $atts )
    {
        $a = shortcode_atts( array(
            'type'          => 'time',
            'label'         => _x( 'Time *', 'form_default', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'max_time'      => '',
            'min_time'      => '',
            'required'      => false,
            'attributes'    => '',

        ), $atts );

        if ( ! empty( $a[ 'max_time' ] ) ){
            $a[ 'attributes' ] .= sprintf( ' max="%s"', esc_attr( $a[ 'max_time' ] ) );
        }

        if ( ! empty( $a[ 'min_time' ] ) ){
            $a[ 'attributes' ] .= sprintf( ' min="%s"', esc_attr( $a[ 'min_time' ] ) );
        }

        $this->config[ $a[ 'name' ] ] = $a;

        return $this->input_base( $a );
    }

    /**
     * Output html for the number field
     *
     * @param $atts
     * @return string
     */
    public function number( $atts )
    {
        $a = shortcode_atts( array(
            'type'          => 'number',
            'label'         => _x( 'Number *', 'form_default', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'value'         => '',
            'placeholder'   => '',
            'max'           => '',
            'min'           => '',
            'attributes'    => '',
            'required'      => false,
        ), $atts );

        if ( ! empty( $a[ 'max' ] ) ){
            $a[ 'attributes' ] .= sprintf( ' max="%d"', $a[ 'max' ] );
        }

        if ( ! empty( $a[ 'min' ] ) ){
            $a[ 'attributes' ] .= sprintf( ' min="%d"', $a[ 'min' ] );
        }

        return $this->input_base( $a );
    }

    /**
     * Return a simple address block
     *
     * @param $atts
     * @return string
     */
    public function address( $atts )
    {
        $a = shortcode_atts( array(
            'label'         => _x( 'Address *', 'form_default', 'groundhogg' ),
            'class'         => 'gh-address',
            'enabled'       => 'all',
            'name_prefix'   => '',
            'required'      => false,
        ), $atts );

        $name_prefix = sanitize_key( $a[ 'name_prefix' ] );

        if ( $name_prefix ){
            $name_prefix .= '_';
        }

        $section = sprintf( "<div class='%s'><label class='gh-input-label'>%s</label>", $a[ 'class' ], $a[ 'label' ] );

        $section .= $this->row( array(), $this->column( array( 'size' => '2/3' ), $this->input_base(
                array(
                    'type'          => 'text',
                    'label'         => _x( 'Street Address 1', 'form_default', 'groundhogg' ),
                    'name'          => $name_prefix . 'street_address_1',
                    'id'            => $name_prefix . 'street_address_1',
                    'placeholder'   => '123 Any St.',
                    'title'         => _x( 'Street Address 1', 'form_default', 'groundhogg' ),
                    'required'      => $a[ 'required' ],
                )
            ) ) .

            $this->column( array( 'size' => '1/3' ), $this->input_base(
                array(
                    'type'          => 'text',
                    'label'         => _x( 'Street Address 2', 'form_default', 'groundhogg' ),
                    'name'          => $name_prefix . 'street_address_2',
                    'id'            => $name_prefix . 'street_address_2',
                    'placeholder'   => 'Unit A',
                    'title'         => _x( 'Street Address 2', 'form_default', 'groundhogg' ),
                    'required'      => $a[ 'required' ],
                )
            ) ) );

        $section .= $this->row( array(),  $this->input_base(
            array(
                'type'          => 'text',
                'label'         => _x( 'City', 'form_default', 'groundhogg' ),
                'name'          => $name_prefix . 'city',
                'id'            => $name_prefix . 'city',
                'placeholder'   => 'New York',
                'title'         => _x( 'City', 'form_default', 'groundhogg' ),
                'required'      => $a[ 'required' ],
            )
        ) );

        $section .= $this->row( array(), $this->column( array( 'size' => '1/2' ),$this->input_base(
                array(
                    'type'          => 'text',
                    'label'         => _x( 'State/Province', 'form_default', 'groundhogg' ),
                    'name'          => $name_prefix . 'region',
                    'id'            => $name_prefix . 'region',
                    'placeholder'   => 'New York',
                    'title'         => _x( 'State/Province', 'form_default', 'groundhogg' ),
                    'required'      => $a[ 'required' ],
                )
            ) ) . $this->column( array( 'size' => '1/2' ), $this->select(
                array(
                    'label'         => _x( 'Country *', 'form_default', 'groundhogg' ),
                    'name'          => $name_prefix . 'country',
                    'id'            => $name_prefix . 'country',
                    'class'         => '',
                    'options'       => wpgh_get_countries_list(),
                    'attributes'    => '',
                    'title'         => __( 'Country' ),
                    'default'       => _x( 'Please select a country', 'form_default', 'groundhogg' ),
                    'multiple'      => false,
                    'required'      => $a[ 'required' ],
                ) )
            ) );

        $section.= $this->input_base(
            array(
                'type'          => 'text',
                'label'         => _x( 'Postal/Zip Code', 'form_default', 'groundhogg' ),
                'name'          => $name_prefix . 'postal_zip',
                'id'            => $name_prefix . 'postal_zip',
                'placeholder'   => '10001',
                'title'         => _x( 'Postal/Zip Code', 'form_default', 'groundhogg' ),
                'required'      => $a[ 'required' ],
            )
        );

        $section.= "</div>";

        return $section;

    }

    /**
     * Return HTML for a textarea field
     *
     * @param $atts
     * @return string
     */
    public function textarea( $atts )
    {
        /* return nothing if the form doesn't exist */
        if ( ! $this->is_form() )
        {
            return '';
        }

        $a = shortcode_atts( array(
            'label'         => '',
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'value'         => '',
            'placeholder'   => '',
            'title'         => '',
            'attributes'    => '',
            'required'      => false,
        ), $atts );

        $a[ 'name' ] = sanitize_key( strtolower( str_replace( ' ', '_', $a[ 'name' ] ) ) );

        if ( empty( $a[ 'name' ] ) ){
            $a[ 'name' ] = !empty( $a[ 'label' ] )? sanitize_key( $a[ 'label' ] ) : sanitize_key( $a[ 'placeholder' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = !empty( $a[ 'label' ] )? sanitize_key( $a[ 'label' ] ) : sanitize_key( $a[ 'placeholder' ] );
        }

        $this->fields[] = $a[ 'name' ];
        $this->config[ $a[ 'name' ] ] = $a;

        $required = ( $a[ 'required' ] && $a[ 'required' ] !== "false"  && $a[ 'required' ] !== "0" ) ? 'required' : '';

        if ( $this->auto_populate ){
            $name = $a[ 'name' ];
            if ( $this->source_contact->$name ){
                $a[ 'value' ] = $this->source_contact->$name;
            }
        }

        if ( $this->submission_failed ){
            $a[ 'value' ] = $this->get_submission_data( $a[ 'name' ] );
        }

        $field = sprintf(
            "<label class='gh-input-label'>%s <textarea name='%s' id='%s' class='gh-input %s' placeholder='%s' title='%s' %s %s>%s</textarea></label>",
            $a[ 'label' ],
            esc_attr( $a[ 'name' ] ),
            esc_attr( $a[ 'id' ] ),
            esc_attr( $a[ 'class' ] ),
            esc_attr( $a[ 'placeholder' ] ),
            esc_attr( $a[ 'title' ] ),
            $a[ 'attributes' ],
            $required,
            esc_attr( $a[ 'value' ] )
        );

        return $this->field_wrap( $field );
    }

    /**
     * Return html for the select
     *
     * @param $atts
     * @return string
     */
    function select( $atts )
    {
        /* return nothing if the form doesn't exist */
        if ( ! $this->is_form() )
        {
            return '';
        }

        $a = shortcode_atts( array(
            'label'         => _x( 'Select *', 'form_default', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'options'       => '',
            'attributes'    => '',
            'title'         => '',
            'default'       => _x( 'Please select one', 'form_default', 'groundhogg' ),
            'multiple'      => false,
            'required'      => false,
        ), $atts );

        $a[ 'name' ] = sanitize_key( strtolower( str_replace( ' ', '_', $a[ 'name' ] ) ) );

        if ( empty( $a[ 'name' ] ) ){
            $a[ 'name' ] = !empty( $a[ 'label' ] )? sanitize_key( $a[ 'label' ] ) : sanitize_key( $a[ 'default' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = !empty( $a[ 'label' ] )? sanitize_key( $a[ 'label' ] ) : sanitize_key( $a[ 'default' ] );
        }

        $required = ( $a[ 'required' ] && $a[ 'required' ] !== "false" ) ? 'required' : '';
        $multiple = $a[ 'multiple' ] ? 'multiple' : '';

        $optionHTML = sprintf( "<option value=''>%s</option>", $a[ 'default' ] );

        if ( ! empty( $a[ 'options' ] ) )
        {
            $options = is_array( $a[ 'options' ] )? $a[ 'options' ] : explode( ',', $a[ 'options' ] );
            $options = array_map( 'trim', $options );

            foreach ( $options as $i => $option ){

                $value = is_string( $i ) ? $i : $option;

                /**
                 * Check if tag should be applied
                 *
                 * @since 1.1
                 */
                if ( strpos( $value, '|' ) ){
                    $parts = explode( '|', $value );
                    $value = $parts[0];
                    $tag = intval( $parts[1] );
                    $a[ 'tag_map' ][ base64_encode($value) ] = $tag;
                }

                if ( strpos( $option, '|' ) ){
                    $parts = explode( '|', $value );
                    $option = $parts[0];
                }

                $selected = '';
                if ( $this->auto_populate ){
                    $name = $a[ 'name' ];
                    if ( $this->source_contact->$name === $value ){
                        $selected = 'selected';
                    }
                }

                if ( $this->submission_failed ){
                    if ( $value === $this->get_submission_data( $a[ 'name' ] ) ){
                        $selected = 'selected';
                    }
                }

                $optionHTML .= sprintf( "<option value='%s' %s>%s</option>", esc_attr( $value ), $selected, $option );
            }

        }

        $this->fields[] = $a[ 'name' ];
        $this->config[ $a[ 'name' ] ] = $a ;

        $field = sprintf(
            "<label class='gh-input-label'>%s <select name='%s' id='%s' class='gh-input %s' title='%s' %s %s %s>%s</select></label>",
            $a[ 'label' ],
            esc_attr( $a[ 'name' ] ),
            esc_attr( $a[ 'id' ] ),
            esc_attr( $a[ 'class' ] ),
            esc_attr( $a[ 'title' ] ),
            $a[ 'attributes' ],
            $required,
            $multiple,
            $optionHTML
        );

        return $this->field_wrap( $field );
    }

    /**
     * Return html for the radio options
     *
     * @param $atts
     * @return string
     */
    function radio( $atts )
    {
        /* return nothing if the form doesn't exist */
        if ( ! $this->is_form() )
        {
            return '';
        }

        $a = shortcode_atts( array(
            'label'         => _x( 'Radio *', 'form_default', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'options'       => '',
            'required'      => false,
        ), $atts );

        $a[ 'name' ] = sanitize_key( strtolower( str_replace( ' ', '_', $a[ 'name' ] ) ) );

        if ( empty( $a[ 'name' ] ) ){
            $a[ 'name' ] = sanitize_key( $a[ 'label' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = sanitize_key( $a[ 'label' ] );
        }

        $required = ( $a[ 'required' ] && $a[ 'required' ] !== "false" ) ? 'required' : '';

        $optionHTML = '';

        if ( ! empty( $a[ 'options' ] ) )
        {

            $options = is_string(  $a[ 'options' ] )? explode( ',', $a[ 'options' ] ) :  $a[ 'options' ] ;

            foreach ( $options as $i => $option ){

                $value = is_string( $i ) ? $i : $option;

	            /**
	             * Check if tag should be applied
	             *
	             * @since 1.1
	             */
                if ( strpos( $value, '|' ) ){
                    $parts = explode( '|', $value );
                    $value = $parts[0];
                    $tag = intval( $parts[1] );
                    $a[ 'tag_map' ][ base64_encode($value) ] = $tag;
                }

                if ( strpos( $option, '|' ) ){
                    $parts = explode( '|', $value );
                    $option = $parts[0];
                }

                $checked = '';
                if ( $this->auto_populate ){
                    $name = $a[ 'name' ];
                    if ( $this->source_contact->$name === $value ){
                        $checked = 'checked';
                    }
                }

                if ( $this->submission_failed ){
                    if ( $value === $this->get_submission_data( $a[ 'name' ] ) ){
                        $checked = 'checked';
                    }
                }

                $optionHTML .= sprintf( "<div class='gh-radio-wrapper'><label class='gh-radio-label'><input class='gh-radio %s' type='radio' name='%s' id='%s' value='%s' %s %s> %s</label></div>",
                    esc_attr( $a[ 'class' ] ),
                    esc_attr( $a[ 'name' ] ),
                    esc_attr( $a[ 'id' ] ) . '-' . $i,
                    esc_attr( $value ),
                    $required,
                    $checked,
                    $option
                );

            }

        }

        $this->fields[] = $a[ 'name' ];
        $this->config[ $a[ 'name' ] ] = $a ;

        $field = sprintf(
            "<label class='gh-input-label'>%s</label>%s",
            $a[ 'label' ],
            $optionHTML
        );

        return $this->field_wrap( $field );
    }

    /**
     * Checkbox html
     *
     * @param $atts
     * @return string
     */
    public function checkbox( $atts )
    {
        /* return nothing if the form doesn't exist */
        if ( ! $this->is_form() )
        {
            return '';
        }

        $a = shortcode_atts( array(
            'label'         => '',
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'value'         => '1',
            'tag'           => 0,
            'title'         => '',
            'attributes'    => '',
            'required'      => false,
        ), $atts );

        $a[ 'name' ] = sanitize_key( strtolower( str_replace( ' ', '_', $a[ 'name' ] ) ) );

        if ( empty( $a[ 'name' ] ) ){
            $a[ 'name' ] = sanitize_key( $a[ 'label' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = sanitize_key( $a[ 'label' ] );
        }

        $required = ( $a[ 'required' ] && $a[ 'required' ] !== "false" ) ? 'required' : '';

        $value = esc_attr( $a[ 'value' ] );

        if ( strpos( $value, '|' ) ){
            $parts = explode( '|', $value );
            $value = $parts[0];
            $tag = intval( $parts[1] );
            $a[ 'tag_map' ][ base64_encode($value) ] = $tag;
        }

        if ( $a[ 'tag' ] ){
            $a[ 'tag_map' ][ base64_encode($value) ] = intval( $a[ 'tag' ] );
        }

        $checked = '';
        if ( $this->auto_populate ){
            $name = $a[ 'name' ];
            if ( $this->source_contact->$name === $value ){
                $checked = 'checked';
            }
        }

        if ( $this->submission_failed ){
            if ( $value === $this->get_submission_data( $a[ 'name' ] ) ){
                $checked = 'checked';
            }
        }

        $this->fields[] = $a[ 'name' ];
        $this->config[ $a[ 'name' ] ] = $a ;

        $field = sprintf(
            "<label class='gh-checkbox-label'><input type='checkbox' name='%s' id='%s' class='gh-checkbox %s' value='%s' title='%s' %s %s %s> %s</label>",
            esc_attr( $a[ 'name' ] ),
            esc_attr( $a[ 'id' ] ),
            esc_attr( $a[ 'class' ] ),
            esc_attr( $value ),
            esc_attr( $a[ 'title' ] ),
            $a[ 'attributes' ],
            $required,
            $checked,
            $a[ 'label' ]
        );

        return $this->field_wrap( $field );
    }

    /**
     * Terms agreement
     *
     * @param $atts
     * @return string
     */
    public function terms( $atts )
    {
        $a = shortcode_atts( array(
            'label'         => _x( 'I agree to the <i>terms of service</i>.', 'form_default', 'groundhogg' ),
            'name'          => 'agree_terms',
            'id'            => 'agree_terms',
            'class'         => 'gh-terms',
            'value'         => 'yes',
            'tag'           => 0,
            'title'         => _x( 'Please agree to the terms of service.', 'form_default', 'groundhogg' ),
            'required'      => true,
        ), $atts );

        $a = apply_filters( 'groundhogg/form/terms', $a );

        return $this->checkbox( $a );
    }

    /**
     * GDPR agreement
     *
     * @param $atts
     * @return string
     */
    public function gdpr( $atts )
    {
        $a = shortcode_atts( array(
            'label'         => _x( 'I consent to having my personal information collected, and to receive marketing and transactional information related to my request.', 'form_default', 'groundhogg' ),
            'name'          => 'gdpr_consent',
            'id'            => 'gdpr_consent',
            'class'         => 'gh-gdpr',
            'value'         => 'yes',
            'tag'           => 0,
            'title'         => _x( 'I Consent', 'form_default', 'groundhogg' ),
            'required'      => true,
        ), $atts );

        $a = apply_filters( 'groundhogg/form/gdpr', $a );

        return $this->checkbox( $a );
    }

    /**
     * Return recaptcha html
     *
     * @return string
     */
    public function recaptcha( $atts )
    {
        /* return nothing if the form doesn't exist */
        if ( ! $this->is_form() )
        {
            return '';
        }

        $a = shortcode_atts( array(
            'theme'         => false,
            'captcha-theme' => 'light',
            'size'          => false,
            'captcha-size'  => 'normal',
        ), $atts );

        if ( $a[ 'theme' ] ){
            $a[ 'captcha-theme' ] = $a[ 'theme' ];
        }

        if ( $a[ 'size' ] ){
            $a[ 'captcha-theme' ] = $a[ 'size' ];
        }

        if ( ! is_admin() )
            wp_enqueue_script( 'google-recaptcha-v2', 'https://www.google.com/recaptcha/api.js', array(), true );

        $html = sprintf( '<div class="g-recaptcha" data-sitekey="%s" data-theme="%s" data-size="%s"></div>', wpgh_get_option( 'gh_recaptcha_site_key', '' ), $a['captcha-theme'], $a['captcha-size'] );

        $this->fields[] = 'g-recaptcha';
        $this->config[ 'g-recaptcha' ] = $a ;

        return $this->field_wrap( $html );
    }

    /**
     * Offer up the email preferences form
     *
     * @return string
     */
    public function email_preferences( $atts )
    {

        $contact = WPGH()->tracking->get_contact();

        if ( ! $contact || ! $contact->exists() ){
            return _x( 'There is no email to manage.', 'form_default', 'groundhogg' );
        }

        $html = "<div class='gh-email-preferences-form'>";
        $html .= wp_nonce_field( 'change_email_preferences', 'email_preferences_nonce', false, false );

        $last_change = $contact->get_meta( 'preferences_changed' );

        if ( $last_change && ( time() - $last_change ) < 30 ){

            $html.= sprintf( "<div class='notice' style='color: white; background: #3ed920;padding: 6px;margin-bottom: 10px;'>%s</div>",
                _x( 'Your preferences have been changed!', 'form_default', 'groundhogg' )
            );

        }

        $options = array(
            'none'          => _x( 'I love this company, you can communicate with me whenever you feel like.', 'form_default', 'groundhogg' ),
            'weekly'        => _x( 'It\'s getting a bit much. Communicate with me weekly.', 'form_default', 'groundhogg' ),
            'monthly'       => _x( 'Distance makes the heart grow fonder. Communicate with me monthly.', 'form_default', 'groundhogg' ),
            'unsubscribe'   => _x( 'I no longer wish to receive any form of communication. Unsubscribe me!', 'form_default', 'groundhogg' )
        );

        $options = apply_filters( 'wpgh_email_preferences', $options );
        $options = apply_filters( 'groundhogg/form/email_preferences/options', $options );

        /* Email Preference Option */
        $args = array(
            'label'     => _x( 'Manage Email Preferences For <b>' . $contact->email . '</b>:', 'form_default', 'groundhogg' ),
            'id'        => 'email_preferences',
            'name'      => 'email_preferences',
            'options'   => $options,
            'selected'  => 'none',
            'class'     => 'email-preference',
            'required'  => 'true'
        );

        $args = apply_filters( 'groundhogg/form/email_preferences', $args );
        $html .= $this->radio( $args );

        /* Delete Everything Option */

        if ( wpgh_is_gdpr() ){
            $args = array(
                'label'     => _x( ' Please also delete all personal information on record.', 'form_default', 'groundhogg' ),
                'id'        => 'delete_everything',
                'name'      => 'delete_everything',
                'value'     => 'yes',
            );

            $args = apply_filters( 'groundhogg/form/email_preferences/gdpr', $args );
            $html .= $this->checkbox( $args );
            /* only show checkbox if unsubscribing */
            $html .= "<script>
jQuery( function($){ 
    $( '#delete_everything' ).parent().css( 'display', 'none' );
    $('input[name=email_preferences]').on( 'change', function(){ 
        if ( $(this).is( ':checked' ) && $(this).val() === 'unsubscribe' ){ 
            $( '#delete_everything' ).parent().fadeIn() 
        } else { 
            $( '#delete_everything' ).parent().fadeOut() 
        } 
    } ) 
})</script>";
        }

        $html .= "</div>";

        return $html;
    }

    /**
     * Submit button html
     *
     * @param $atts
     * @param $content
     * @return string
     */
    public function submit( $atts, $content = '' )
    {
        $a = shortcode_atts( array(
            'id'            => 'gh-submit',
            'class'         => 'gh-submit',
            'text'          => __( 'Submit' ),
        ), $atts );

        if ( ! empty( $content ) )
        {
            $a['text'] = $content;
        }

        /* Don't apply when doing a preview */
        if ( is_admin() && ! $this->doing_preview ){
            $a[ 'class' ] .= 'button button-primary';
        }

        $html = sprintf(
                "<div class='gh-button-wrapper'><button type='submit' id='%s' class='gh-submit-button %s'>%s</button></div>",
            esc_attr( $a[ 'id' ] ),
            esc_attr( $a[ 'class' ] ),
            $a[ 'text' ]
        );

        return $this->field_wrap( $html );

    }

    /**
     * Do the shortcode
     *
     * @param $atts
     * @param $content
     * @return string
     */
    public function shortcode()
    {
        $form = '<div class="gh-form-wrapper">';

        /* Errors from a previous submission */
        if ( WPGH()->submission->has_errors() ){

            $this->submission_failed = true;
            $errors = WPGH()->submission->get_errors();
            $err_html = "";

            foreach ( $errors as $error ){
                $err_html .= sprintf( '<li id="%s">%s</li>', $error->get_error_code(), $error->get_error_message() );
            }

            $err_html = sprintf( "<ul class='gh-form-errors'>%s</ul>", $err_html );
            $form .= sprintf( "<div class='gh-form-errors-wrapper'>%s</div>", $err_html );

        }

	    $target = $this->iframe_compat ? "target=\"_parent\"" : "";
        $form .= "<form method='post' class='gh-form " . $this->a[ 'class' ] . "' " . $target . " enctype=\"multipart/form-data\" >";
        $form .= wp_nonce_field( 'gh_submit', 'gh_submit_nonce', true, false );

        if ( ! empty( $this->a[ 'id' ] ) ){
            $form .= "<input type='hidden' name='step_id' value='" . $this->a['id'] . "'>";
        }

        $this->setup_shortcodes();

        if ( $this->id && WPGH()->steps->exists( $this->id ) ){
            $content = WPGH()->step_meta->get_meta( $this->id, 'form', true );
        } else {
            $content = '';
        }

        $content = do_shortcode( $content );

        if ( empty( $content ) ){
            $content = sprintf( "<p>%s</p>" , __( "<b>Configuration Error:</b> This form has either been deleted or has not content yet." ) );
        }

        $form .= $content;

        $this->destroy_shortcodes();

        $form .= '</form>';

        if ( is_user_logged_in() && current_user_can( 'edit_funnels' ) ){

        	$funnel_id = WPGH()->steps->get_column_by( 'funnel_id', 'ID', $this->id );

        	$form .= sprintf( "<p><a href='%s'>%s</a></p>", admin_url( 'admin.php?page=gh_funnels&action=edit&funnel=' . $funnel_id ), __( '(Edit Form)' ) );
        }

        $form .= '</div>';

        /* Save the expected field to post meta so we can access them on the POST end */
        if ( $this->id && WPGH()->steps->exists( $this->id ) ){

            WPGH()->step_meta->update_meta( $this->id, 'expected_fields', $this->fields );
            WPGH()->step_meta->update_meta( $this->id, 'config', $this->config );

        }

        $form = apply_filters( 'wpgh_form_shortcode', $form, $this );
        $form = apply_filters( 'groundhogg/form/after', $form, $this );

        return $form;
    }

    /**
     * Show review version of the form
     */
    public function preview()
    {
        $this->doing_preview = true;

        $form = '<div class="gh-form-wrapper">';

        $this->setup_shortcodes();

        if ( $this->id && WPGH()->steps->exists( $this->id ) ){

            $content = WPGH()->step_meta->get_meta( $this->id, 'form', true );

        } else {

            $content = '';

        }

        $form .= do_shortcode( $content );

        $this->destroy_shortcodes();

        $form .= '</div>';

        $form = str_replace( 'required', '', $form );
//        $form = apply_filters( 'wpgh_form_shortcode', $form, $this );
//        $form = apply_filters( 'groundhogg/form/after', $form, $this );
        $form = apply_filters( 'wpgh_form_shortcode_preview', $form, $this );
        $form = apply_filters( 'groundhogg/form/preview/after', $form, $this );

        return $form;
    }

    /**
     * Just return the shortcode
     *
     * @return string
     */
    public function __toString()
    {
        return $this->shortcode();
    }

}