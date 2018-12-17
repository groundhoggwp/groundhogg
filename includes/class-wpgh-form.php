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

    public function __construct( $atts )
    {
        $this->a = shortcode_atts(array(
            'class'     => '',
            'id'        => 0
        ), $atts);

        $this->id = intval( $this->a[ 'id' ] );

        $this->add_scripts();
    }

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

    	return sprintf( "<div id='%s' class='gh-form-row %s'>%s</div>", $a['id'], $a['class'], do_shortcode( $content ) );

    }
    public function column( $atts, $content ){

    	$a = shortcode_atts( array(
    	    'size'  => '1/2',
            'id'    => '',
            'class' => ''
        ), $atts );

    	switch ( $a[ 'size' ] ){

		    case '1/1':
		    	$width = 'col-1-of-1';
		    	break;
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
		    default:
			    $width = 'col-1-of-2';
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
     * Return the first name field html
     *
     * @param $atts
     * @return string
     */
    public function first_name( $atts )
    {
        $a = shortcode_atts( array(
            'type'          => 'text',
            'label'         => __( 'First Name *', 'groundhogg' ),
            'name'          => 'first_name',
            'id'            => 'first_name',
            'class'         => 'gh-first-name',
            'value'         => '',
            'placeholder'   => '',
            'attributes'    => 'pattern="[A-Za-z \-\']+"',
            'title'         => __( 'Do not include numbers or special characters.', 'groundhogg' ),
            'required'      => true,
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
            'label'         => __( 'Last Name *', 'groundhogg' ),
            'name'          => 'last_name',
            'id'            => 'last_name',
            'class'         => 'gh-last-name',
            'value'         => '',
            'placeholder'   => '',
            'attributes'    => 'pattern="[A-Za-z \-\']+"',
            'title'         => __( 'Do not include numbers or special characters.', 'groundhogg' ),
            'required'      => true,
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
            'label'         => __( 'Email *', 'groundhogg' ),
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
            'label'         => __( 'Phone *', 'groundhogg' ),
            'name'          => 'primary_phone',
            'id'            => 'primary_phone',
            'class'         => 'gh-tel',
            'value'         => '',
            'placeholder'   => '',
            'attributes'    => '',
            'required'      => true,
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
            'label'         => __( 'File *', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'max_file_size' => 0,
            'file_types'    => '',
            'required'      => true,
            'attributes'    => '',

        ), $atts );

        if ( ! empty( $a[ 'file_types' ] ) ){
            $a[ 'attributes' ] .= sprintf( ' accept="%s"', esc_attr( $a[ 'file_types' ] ) );
        }

        $this->config[ $a[ 'name' ] ] = $a;

        return $this->input_base( $a );
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
            'type'          => 'date',
            'label'         => __( 'Date *', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'max_date'      => '',
            'min_date'      => '',
            'required'      => true,
            'attributes'    => '',

        ), $atts );

        if ( ! empty( $a[ 'max_date' ] ) ){
            $a[ 'attributes' ] .= sprintf( ' max="%s"', esc_attr( $a[ 'max_date' ] ) );
        }

        if ( ! empty( $a[ 'min_date' ] ) ){
            $a[ 'attributes' ] .= sprintf( ' min="%s"', esc_attr( $a[ 'min_date' ] ) );
        }

        $this->config[ $a[ 'name' ] ] = $a;

        return $this->input_base( $a );
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
            'label'         => __( 'Time *', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'max_time'      => '',
            'min_time'      => '',
            'required'      => true,
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
     * Return a simple address block
     *
     * @param $atts
     * @return string
     */
    public function address( $atts )
    {
        $a = shortcode_atts( array(
            'label'         => __( 'Address *', 'groundhogg' ),
            'class'         => 'gh-address',
            'enabled'       => 'all',
            'required'      => true,
        ), $atts );

        $section = sprintf( "<div class='%s'><label class='gh-input-label'>%s</label>", $a[ 'class' ], $a[ 'label' ] );

        $section .= $this->row( array(), $this->column( array( 'size' => '2/3' ), $this->input_base(
            array(
                'type'          => 'text',
                'label'         => __( 'Street Address 1', 'groundhogg' ),
                'name'          => 'street_address_1',
                'id'            => 'street_address_1',
                'placeholder'   => '123 Any St.',
                'title'         => __( 'Street Address 1', 'groundhogg' ),
                'required'      => $a[ 'required' ],
            )
        ) ) .

        $this->column( array( 'size' => '1/3' ), $this->input_base(
            array(
                'type'          => 'text',
                'label'         => __( 'Street Address 2', 'groundhogg' ),
                'name'          => 'street_address_2',
                'id'            => 'street_address_2',
                'placeholder'   => 'Unit A',
                'title'         => __( 'Street Address 2', 'groundhogg' ),
                'required'      => $a[ 'required' ],
            )
        ) ) );

        $section .= $this->row( array(),  $this->input_base(
            array(
                'type'          => 'text',
                'label'         => __( 'City', 'groundhogg' ),
                'name'          => 'city',
                'id'            => 'city',
                'placeholder'   => 'New York',
                'title'         => __( 'City', 'groundhogg' ),
                'required'      => $a[ 'required' ],
            )
        ) );

        $section .= $this->row( array(), $this->column( array( 'size' => '1/2' ),$this->input_base(
            array(
                'type'          => 'text',
                'label'         => __( 'State/Province', 'groundhogg' ),
                'name'          => 'region',
                'id'            => 'region',
                'placeholder'   => 'New York',
                'title'         => __( 'State/Province', 'groundhogg' ),
                'required'      => $a[ 'required' ],
            )
        ) ) . $this->column( array( 'size' => '1/2' ), $this->select(
            array(
                'label'         => __( 'Country *', 'groundhogg' ),
                'name'          => 'country',
                'id'            => 'country',
                'class'         => '',
                'options'       => wpgh_get_countries_list(),
                'attributes'    => '',
                'title'         => __( 'Country' ),
                'default'       => __( 'Please select a country', 'groundhogg' ),
                'multiple'      => false,
                'required'      => $a[ 'required' ],
            ) )
        ) );

        $section.= $this->input_base(
            array(
                'type'          => 'text',
                'label'         => __( 'Postal/Zip Code', 'groundhogg' ),
                'name'          => 'postal_zip',
                'id'            => 'postal_zip',
                'placeholder'   => '10001',
                'title'         => __( 'Postal/Zip Code', 'groundhogg' ),
                'required'      => $a[ 'required' ],
            )
        );

        $section.= "</div>";

        return $section;

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
            'required'      => true,
        ), $atts );

        if ( empty( $a[ 'name' ] ) ){
            $a[ 'name' ] = !empty( $a[ 'label' ] )? sanitize_key( $a[ 'label' ] ) : sanitize_key( $a[ 'placeholder' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = !empty( $a[ 'label' ] )? sanitize_key( $a[ 'label' ] ) : sanitize_key( $a[ 'placeholder' ] );
        }

        $this->fields[] = $a[ 'name' ];
        $this->config[ $a[ 'name' ] ] = $a;

        $required = ( $a[ 'required' ] && $a[ 'required' ] !== "false"  && $a[ 'required' ] !== "0" ) ? 'required' : '';

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
     * Output html for the number field
     *
     * @param $atts
     * @return string
     */
    public function number( $atts )
    {
        $a = shortcode_atts( array(
            'type'          => 'number',
            'label'         => __( 'Number *', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'value'         => '',
            'placeholder'   => '',
            'max'           => '',
            'min'           => '',
            'attributes'    => '',
            'required'      => true,
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
            'label'         => __( 'Select *', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'options'       => '',
            'attributes'    => '',
            'title'         => '',
            'default'       => __( 'Please select one', 'groundhogg' ),
            'multiple'      => false,
            'required'      => true,
        ), $atts );

        if ( empty( $a[ 'name' ] ) ){
            $a[ 'name' ] = !empty( $a[ 'label' ] )? sanitize_key( $a[ 'label' ] ) : sanitize_key( $a[ 'default' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = !empty( $a[ 'label' ] )? sanitize_key( $a[ 'label' ] ) : sanitize_key( $a[ 'default' ] );
        }

        $this->fields[] = $a[ 'name' ];
        $this->config[ $a[ 'name' ] ] = $a ;

        $required = ( $a[ 'required' ] && $a[ 'required' ] !== "false" ) ? 'required' : '';
        $multiple = $a[ 'multiple' ] ? 'multiple' : '';

        $optionHTML = sprintf( "<option value=''>%s</option>", $a[ 'default' ] );

        if ( ! empty( $a[ 'options' ] ) )
        {
            $options = is_array( $a[ 'options' ] )? $a[ 'options' ] : explode( ',', $a[ 'options' ] );
            $options = array_map( 'trim', $options );

            foreach ( $options as $option ){

                $optionHTML .= sprintf( "<option value='%s'>%s</option>", esc_attr( $option ), $option );

            }

        }

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
            'label'         => __( 'Radio *', 'groundhogg' ),
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'options'       => '',
            'required'      => true,
        ), $atts );

        if ( empty( $a[ 'name' ] ) ){
            $a[ 'name' ] = sanitize_key( $a[ 'label' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = sanitize_key( $a[ 'label' ] );
        }

        $this->fields[] = $a[ 'name' ];
        $this->config[ $a[ 'name' ] ] = $a ;

        $required = ( $a[ 'required' ] && $a[ 'required' ] !== "false" ) ? 'required' : '';

        $optionHTML = '';

        if ( ! empty( $a[ 'options' ] ) )
        {

            $options = is_string(  $a[ 'options' ] )? explode( ',', $a[ 'options' ] ) :  $a[ 'options' ] ;

            foreach ( $options as $i => $option ){

                $value = is_string( $i ) ? $i : $option;

                $optionHTML .= sprintf( "<div class='gh-radio-wrapper'><label class='gh-radio-label'><input class='gh-radio %s' type='radio' name='%s' id='%s' value='%s' %s> %s</label></div>",
                    esc_attr( $a[ 'class' ] ),
                    esc_attr( $a[ 'name' ] ),
                    esc_attr( $a[ 'id' ] ) . '-' . $i,
                    esc_attr( $value ),
                    $required,
                    $option
                );

            }

        }

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
            'title'         => '',
            'attributes'    => '',
            'required'      => false,
        ), $atts );

        if ( empty( $a[ 'name' ] ) ){
            $a[ 'name' ] = sanitize_key( $a[ 'label' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = sanitize_key( $a[ 'label' ] );
        }

        $this->fields[] = $a[ 'name' ];
        $this->config[ $a[ 'name' ] ] = $a ;

        $required = ( $a[ 'required' ] && $a[ 'required' ] !== "false" ) ? 'required' : '';

        $field = sprintf(
            "<label class='gh-checkbox-label'><input type='checkbox' name='%s' id='%s' class='gh-checkbox %s' value='%s' title='%s' %s %s> %s</label>",
            esc_attr( $a[ 'name' ] ),
            esc_attr( $a[ 'id' ] ),
            esc_attr( $a[ 'class' ] ),
            esc_attr( $a[ 'value' ] ),
            esc_attr( $a[ 'title' ] ),
            $a[ 'attributes' ],
            $required,
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
            'label'         => __( 'I agree to the <i>terms of service</i>.', 'groundhogg' ),
            'name'          => 'agree_terms',
            'id'            => 'agree_terms',
            'class'         => 'gh-terms',
            'value'         => 'yes',
            'title'         => __( 'Please agree to the terms of service', 'groundhogg' ),
            'required'      => true,
        ), $atts );

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
            'label'         => __( 'I consent to having my personal information collected, and to receive marketing and transactional information related to my request. ', 'groundhogg' ),
            'name'          => 'gdpr_consent',
            'id'            => 'gdpr_consent',
            'class'         => 'gh-gdpr',
            'value'         => 'yes',
            'title'         => __( 'I Consent', 'groundhogg' ),
            'required'      => true,
        ), $atts );

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
            'theme'         => 'light',
            'size'          => 'normal',
        ), $atts );


        if ( ! is_admin() )
            wp_enqueue_script( 'google-recaptcha-v2', 'https://www.google.com/recaptcha/api.js' );

        $html = sprintf( '<div class="g-recaptcha" data-sitekey="%s" data-theme="%s" data-size="%s"></div>', wpgh_get_option( 'gh_recaptcha_site_key', '' ), $a[ 'theme'], $a['size'] );

        $this->fields[] = 'g-recaptcha';
        $this->config[ $a[ 'g-recaptcha' ] ] = $a ;

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

            return __( 'You cannot manage the email preferences if no email exists.', 'groundhogg' );

        }

        $html = "<div class='gh-email-preferences-form'>";
        $html .= wp_nonce_field( 'change_email_preferences', 'email_preferences_nonce', false, false );

        $last_change = $contact->get_meta( 'preferences_changed' );

        if ( $last_change && ( time() - $last_change ) < 30 ){

            $html.= sprintf( "<div class='notice' style='color: white; background: #3ed920;padding: 6px;margin-bottom: 10px;'>%s</div>",
                __( 'Your preferences have been changed!', 'groundhogg' )
            );

        }

        $options = array(
            'none'          => __( 'I love this company, you can communicate with me whenever you feel like.', 'groundhogg'),
            'weekly'        => __( 'It\'s getting a bit much. Communicate with me weekly.', 'groundhogg'),
            'monthly'       => __( 'Distance makes the heart grow fonder. Communicate with me monthly.', 'groundhogg'),
            'unsubscribe'   => __( 'I no longer wish to receive any form of communication. Unsubscribe me!', 'groundhogg')
        );

        $options = apply_filters( 'wpgh_email_preferences', $options );

        /* Email Preference Option */
        $args = array(
            'label'     => __( 'Manage Email Preferences For <b>' . $contact->email . '</b>:', 'groundhogg' ),
            'id'        => 'email_preferences',
            'name'      => 'email_preferences',
            'options'   => $options,
            'selected'  => 'none',
            'class'     => 'email-preference',
            'required'  => 'true'
        );

        $html .= $this->radio( $args );

        /* Delete Everything Option */

        if ( wpgh_is_gdpr() ){
            $args = array(
                'label'     => __( ' Please also delete all personal information on record.', 'groundhogg' ),
                'id'        => 'delete_everything',
                'name'      => 'delete_everything',
                'value'     => 'yes',
            );
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
            'id'            => 'submit',
            'class'         => 'gh-submit',
            'text'          => __( 'Submit' ),
        ), $atts );

        if ( ! empty( $content ) )
        {
            $a['text'] = $content;
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

//        $form .= esc_html( $this->content );
//        $form .= htmlentities( $this->content );
//        $form .= htmlentities( "\"hi\"" );

        $form .= "<form method='post' class='gh-form " . $this->a[ 'class' ] ."' enctype=\"multipart/form-data\">";

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

        $form .= do_shortcode( $content );

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