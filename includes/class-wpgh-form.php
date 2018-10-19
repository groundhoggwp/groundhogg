<?php

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
     * Full rendering of the form
     *
     * @var string
     */
    var $rendered;

    public function __construct( $atts, $content )
    {
        $this->a = shortcode_atts(array(
            'success'   => '',
            'class'     => '',
            'id'        => ''
        ), $atts);

        $this->id = intval( $this->a[ 'id' ] );

        $this->content = $content;
    }

    /**
     * Setup the shortcodes for the fields. ensures that
     * field shortcodes can only be called withing the form shortcode
     */
    private function setup_shortcodes()
    {
        add_shortcode( 'gh_first_name', array( $this, 'first_name'  ) );
        add_shortcode( 'gh_last_name',  array( $this, 'last_name'   ) );
        add_shortcode( 'gh_email',      array( $this, 'email'       ) );
        add_shortcode( 'gh_phone',      array( $this, 'phone'       ) );
        add_shortcode( 'gh_address',    array( $this, 'address'     ) );
        add_shortcode( 'gh_text',       array( $this, 'text'        ) );
        add_shortcode( 'gh_textarea',   array( $this, 'textarea'    ) );
        add_shortcode( 'gh_number',     array( $this, 'number'      ) );
        add_shortcode( 'gh_select',     array( $this, 'select'      ) );
        add_shortcode( 'gh_radio',      array( $this, 'radio'       ) );
        add_shortcode( 'gh_checkbox',   array( $this, 'checkbox'    ) );
        add_shortcode( 'gh_terms',      array( $this, 'terms'       ) );
        add_shortcode( 'gh_gdpr',       array( $this, 'gdpr'        ) );
        add_shortcode( 'gh_recaptcha',  array( $this, 'recaptcha'   ) );
        add_shortcode( 'gh_submit',     array( $this, 'submit'      ) );
        add_shortcode( 'gh_email_preferences',  array( $this, 'email_preferences' ) );
    }

    /**
     * remove the short codes when they are no longer relevant
     * field shortcodes can only be called withing the form shortcode
     */
    private function destroy_shortcodes()
    {
        remove_shortcode( 'gh_first_name'   );
        remove_shortcode( 'gh_last_name'    );
        remove_shortcode( 'gh_email'        );
        remove_shortcode( 'gh_phone'        );
        remove_shortcode( 'gh_address'      );
        remove_shortcode( 'gh_text'         );
        remove_shortcode( 'gh_number'       );
        remove_shortcode( 'gh_select'       );
        remove_shortcode( 'gh_radio'        );
        remove_shortcode( 'gh_checkbox'     );
        remove_shortcode( 'gh_terms'        );
        remove_shortcode( 'gh_recaptcha'    );
        remove_shortcode( 'gh_email_preferences'    );
        remove_shortcode( 'gh_submit'       );
    }

    /**
     * Check to ensure the form is real
     *
     * @return bool
     */
    private function is_form()
    {
        return !empty( $this->a[ 'id' ] );
    }

    private function field_wrap( $content )
    {
        return sprintf( "<div class='gh-form-field'><p>%s</p></div>", $content );
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
            'required'      => true,
        ), $atts );

        if ( empty( $a[ 'name' ] ) ){
            $a[ 'name' ] = sanitize_key( $a[ 'label' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = sanitize_key( $a[ 'label' ] );
        }

        $this->fields[] = $a[ 'name' ];

        $required = $a[ 'required' ] ? 'required' : '';

        $field = sprintf(
            "<label class='gh-input-label'>%s <input type='%s' name='%s' id='%s' class='%s' value='%s' placeholder='%s' title='%s' %s %s></label>",
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
            'name'          => 'phone',
            'id'            => 'phone',
            'class'         => 'gh-tel',
            'value'         => '',
            'placeholder'   => '',
            'attributes'    => '',
            'required'      => true,
        ), $atts );

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

        $section.= $this->input_base(
            array(
                'type'          => 'text',
                'label'         => __( 'Street Address 1', 'groundhogg' ),
                'name'          => 'street_address_1',
                'id'            => 'street_address_1',
                'placeholder'   => '123 Any St.',
                'title'         => __( 'Street Address 1', 'groundhogg' ),
                'required'      => $a[ 'required' ],
            )
        );

        $section.= $this->input_base(
            array(
                'type'          => 'text',
                'label'         => __( 'Street Address 2', 'groundhogg' ),
                'name'          => 'street_address_2',
                'id'            => 'street_address_2',
                'placeholder'   => '123 Any St.',
                'title'         => __( 'Street Address 2', 'groundhogg' ),
                'required'      => $a[ 'required' ],
            )
        );

        $section.= $this->input_base(
            array(
                'type'          => 'text',
                'label'         => __( 'City', 'groundhogg' ),
                'name'          => 'city',
                'id'            => 'city',
                'placeholder'   => 'New York',
                'title'         => __( 'City', 'groundhogg' ),
                'required'      => $a[ 'required' ],
            )
        );

        $section.= $this->input_base(
            array(
                'type'          => 'text',
                'label'         => __( 'State/Province', 'groundhogg' ),
                'name'          => 'region',
                'id'            => 'region',
                'placeholder'   => 'New York',
                'title'         => __( 'State/Province', 'groundhogg' ),
                'required'      => $a[ 'required' ],
            )
        );

        $section.= $this->input_base(
            array(
                'type'          => 'text',
                'label'         => __( 'Postal/Zip Code', 'groundhogg' ),
                'name'          => 'postal_zip',
                'id'            => 'postal_zip',
                'placeholder'   => 'New York',
                'title'         => __( 'Postal/Zip Code', 'groundhogg' ),
                'required'      => $a[ 'required' ],
            )
        );

        $section.= $this->select(
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
            $a[ 'name' ] = sanitize_key( $a[ 'label' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = sanitize_key( $a[ 'label' ] );
        }

        $this->fields[] = $a[ 'name' ];

        $required = $a[ 'required' ] ? 'required' : '';

        $field = sprintf(
            "<label class='gh-input-label'>%s <textarea name='%s' id='%s' class='%s' placeholder='%s' title='%s' %s %s>%s</textarea></label>",
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
            $a[ 'name' ] = sanitize_key( $a[ 'label' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = sanitize_key( $a[ 'label' ] );
        }

        $this->fields[] = $a[ 'name' ];

        $required = $a[ 'required' ] ? 'required' : '';
        $multiple = $a[ 'multiple' ] ? 'multiple' : '';

        $optionHTML = sprintf( "<option value=''>%s</option>", $a[ 'default' ] );

        if ( ! empty( $a[ 'options' ] ) )
        {
            $options = explode( ',', $a[ 'options' ] );
            $options = array_map( 'trim', $options );

            foreach ( $options as $option ){

                $optionHTML .= sprintf( "<option value='%s'>%s</option>", esc_attr( $option ), $option );

            }

        }

        $field = sprintf(
            "<label class='gh-input-label'>%s <select name='%s' id='%s' class='%s' title='%s' %s %s %s>%s</select></label>",
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
            'multiple'      => false,
            'required'      => true,
        ), $atts );

        if ( empty( $a[ 'name' ] ) ){
            $a[ 'name' ] = sanitize_key( $a[ 'label' ] );
        }

        if ( empty( $a[ 'id' ] ) ){
            $a[ 'id' ] = sanitize_key( $a[ 'label' ] );
        }

        $this->fields[] = $a[ 'name' ];

        $required = $a[ 'required' ] ? 'required' : '';

        $optionHTML = '';

        if ( ! empty( $a[ 'options' ] ) )
        {

            $options = is_string(  $a[ 'options' ] )? explode( ',', $a[ 'options' ] ) :  $a[ 'options' ] ;

            foreach ( $options as $i => $option ){

                $value = is_string( $i ) ? $i : $option;

                $optionHTML .= sprintf( "<div class='gh-radio-wrapper %s'><label><input type='radio' name='%s' id='%s' value='%s' %s> %s</label></div>",
                    esc_attr( $a[ 'class' ] ),
                    esc_attr( $a[ 'name' ] ),
                    esc_attr( $a[ 'id' ] ),
                    esc_attr( $value ),
                    $required,
                    $option
                );

            }

        }

        $field = sprintf(
            "<label class='gh-radio-label'>%s</label>%s",
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

        $required = $a[ 'required' ] ? 'required' : '';

        $field = sprintf(
            "<label class='gh-checkbox-label'><input type='checkbox' name='%s' id='%s' class='%s' value='%s' title='%s' %s %s> %s</label>",
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
    public function recaptcha()
    {
        /* return nothing if the form doesn't exist */
        if ( ! $this->is_form() )
        {
            return '';
        }

        if ( ! is_admin() )
            wp_enqueue_script( 'google-recaptcha-v2', 'https://www.google.com/recaptcha/api.js' );

        $html = sprintf( '<div class="g-recaptcha" data-sitekey="%s"></div>', get_option( 'gh_recaptcha_site_key', '' ) );

        $this->fields[] = 'g-recaptcha';

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
        $html .= wp_nonce_field( 'change_email_preferences', 'email_preferences_nonce', true, false );

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
    $( '#delete_everything' ).fadeOut();
    $('.email-preference').change( function(){ 
        if ( $(this).is( ':selected' ) && $(this).val() === 'unsubscribe' ){ 
            $( '#delete_everything' ).fadeIn() 
        } else { 
            $( '#delete_everything' ).fadeOut() 
        } 
    } ) 
})</script>";

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
    public function submit( $atts, $content )
    {
        $a = shortcode_atts( array(
            'id'            => 'submit',
            'class'         => 'gh-submit',
        ), $atts );

        if ( empty( $content ) )
        {
            $content = __( 'Submit' );
        }

        return sprintf(
                "<button type='submit' id='%s' class='gh-submit-button %s'>%s</button>",
            esc_attr( $a[ 'id' ] ),
            esc_attr( $a[ 'class' ] ),
            do_shortcode( $content )
        );

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

        $form .= "<form method='post' class='gh-form " . $this->a[ 'class' ] ."' action='" . esc_url_raw( $this->a['success'] ) . "'>";

        $form .= wp_nonce_field( 'gh_submit', 'gh_submit_nonce', true, false );

        $form .= "<input type='hidden' name='step_id' value='" . $this->a['id'] . "'>";

        $this->setup_shortcodes();

        $form .= do_shortcode( $this->content );

//        $form .= do_shortcode( wp_unslash( $this->content ) );

        //$this->destroy_shortcodes();

        $form .= '</form>';

        $form .= '</div>';

        /* Save the expected field to post meta so we can access them on the POST end */
        if ( get_the_ID() )
            update_post_meta(  get_the_ID(), 'gh_fields_' . $this->id , $this->fields );

        $form = apply_filters( 'wpgh_form_shortcode', $form );

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