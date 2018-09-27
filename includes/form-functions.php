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
        remove_shortcode( 'gh_text'         );
        remove_shortcode( 'gh_number'       );
        remove_shortcode( 'gh_select'       );
        remove_shortcode( 'gh_radio'        );
        remove_shortcode( 'gh_checkbox'     );
        remove_shortcode( 'gh_terms'        );
        remove_shortcode( 'gh_recaptcha'    );
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
            $options = explode( ',', $a[ 'options' ] );

            foreach ( $options as $i => $option ){

                $optionHTML .= sprintf( "<div class='gh-radio-wrapper %s'><label><input type='radio' name='%s' id='%s' value='%s' %s> %s</label></div>",
                    esc_attr( $a[ 'class' ] ),
                    esc_attr( $a[ 'name' ] ),
                    esc_attr( $a[ 'id' ] ),
                    esc_attr( $option ),
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
     * Submit button html
     *
     * @param $atts
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

/**
 * Alternate form shortcode
 *
 * @param $atts
 * @param $content
 *
 * @return string
 */
function wpgh_custom_form_shortcode( $atts, $content )
{
    $form = new WPGH_Form( $atts, $content );

    return sprintf( "%s", $form );
}

add_shortcode( 'gh_form', 'wpgh_custom_form_shortcode' );

/**
 * Prevent the shortcode API from texturizing the contents of [gh_form_alt]
 *
 * @param $list
 * @return array
 */
function wpgh_no_texturize_form( $list )
{
    $list[] = 'gh_form';
    return $list;
}

add_filter( 'no_texturize_shortcodes', 'wpgh_no_texturize_form' );


/**
 * Check if Recaptcha is enabled throughout the plugin.
 *
 * @return bool, whether it's enable or not.
 */
function wpgh_is_recaptcha_enabled()
{
    return in_array( 'on', get_option( 'gh_enable_recaptcha', array() ) );
}

/**
 * Listens for basic contact information whenever the post variable is exists.
 */
function wpgh_form_submit_listener()
{
    /* verify real user */
    if ( ! isset( $_POST[ 'gh_submit_nonce' ] ) )
        return;

    if( ! wp_verify_nonce( $_POST[ 'gh_submit_nonce' ], 'gh_submit' ) )
        wp_redirect( wp_get_referer() );

    unset( $_POST[ 'gh_submit_nonce' ] );

    /* Get the expected fields from the post meta */
    $step = intval( $_POST[ 'step_id' ] );
    $post_id = url_to_postid( wp_get_referer() );
    $expected_fields = get_post_meta( $post_id, 'gh_fields_' . $step, true );


    if ( wpgh_is_gdpr() && in_array( 'gdpr_consent', $expected_fields ) ){
        if ( ! isset( $_POST[ 'gdpr_consent' ] ) )
            wp_die( __( 'You must consent to sign up.', 'groundhogg' ) );
    }

    if ( wpgh_is_recaptcha_enabled() && in_array( 'g-recaptcha', $expected_fields )  )
    {
        if ( ! isset( $_POST[ 'g-recaptcha-response' ] ) )
            wp_redirect( wp_get_referer() );

        $file_name = sprintf( "https://www.google.com/recaptcha/api/siteverify?secret=%s&response=%s", get_option( 'gh_recaptcha_secret_key' ), $_POST['g-recaptcha-response'] );

        $verifyResponse = file_get_contents( $file_name );
        $responseData = json_decode( $verifyResponse );
        if( $responseData->success == false ){
            wp_die( __( 'You did not pass the robot test.', 'groundhogg' ) );
        }
    }

    unset( $_POST[ 'g-recaptcha-response' ] );

    /* verify email exists */
    if ( ! isset( $_POST['email'] ) || ! isset( $_POST[ 'step_id' ] ) )
        return;

    if ( isset( $_POST[ 'first_name' ] ) )
        $args['first'] = sanitize_text_field( $_POST[ 'first_name' ] );

    unset( $_POST[ 'first_name' ] );

    if ( isset( $_POST[ 'last_name' ] ) )
        $args['last'] = sanitize_text_field( $_POST[ 'last_name' ] );

    unset( $_POST[ 'last_name' ] );

    if ( isset( $_POST[ 'email' ] ) )
        $args['email'] = sanitize_email( $_POST[ 'email' ] );

    unset( $_POST[ 'email' ] );

    if ( isset( $_POST[ 'phone' ] ) )
        $args['phone'] = sanitize_text_field( $_POST[ 'phone' ] );

    unset( $_POST[ 'phone' ] );

    if ( ! is_email( $args['email'] ) )
        wp_redirect( wp_get_referer() );

    $args = wp_parse_args( $args, array(
        'first' => '',
        'last'  => '',
        'email' => '',
        'phone' => '',
    ));

    $id = wpgh_quick_add_contact( $args['email'], $args['first'], $args['last'], $args['phone'] );

    if ( ! $id ) {
        wp_die( __( 'Something went wrong... ' ) );
    }

    if ( is_wp_error( $id ) )
        wp_die( $id );

    $contact = new WPGH_Contact( $id );

    /* Set the IP address of the contact */
    wpgh_update_contact_meta( $id, 'ip_address', wpgh_get_visitor_ip() );

    /* Set the Leadsource if it doesn't exist */
    if ( ! wpgh_get_contact_meta( $id, 'source_page', true) )
        wpgh_update_contact_meta( $id, 'source_page', wp_get_referer() );

    if ( isset( $_COOKIE[ 'gh_leadsource' ] ) )
        wpgh_update_contact_meta( $id, 'leadsource', esc_url_raw( $_COOKIE[ 'gh_leadsource' ] ) );

    /* if the contact previously unsubscribed, set them to unconfirmed. */
    if ( $contact->get_optin_status() === WPGH_UNSUBSCRIBED )
        wpgh_update_contact( $id, 'optin_status', WPGH_UNCONFIRMED );

    /* get the terms agreement */
    if ( isset( $_POST[ 'agree_terms' ] ) ){

        wpgh_update_contact_meta( $id, 'terms_agreement', 'yes' );
        wpgh_update_contact_meta( $id, 'terms_agreement_date', date_i18n( get_option( 'date_format' ) ) );
        do_action( 'wpgh_agreed_to_terms', $contact->get_id() );

        unset( $_POST[ 'agree_terms' ] );
    }

    /* if gdpr is enabled, make sure that the consent box is checked */
    if ( wpgh_is_gdpr() && isset( $_POST[ 'gdpr_consent' ] ) ){

        wpgh_update_contact_meta( $id, 'gdpr_consent', 'yes' );
        wpgh_update_contact_meta( $id, 'gdpr_consent_date', date_i18n( get_option( 'date_format' ) ) );
        do_action( 'wpgh_gdpr_consented', $contact->get_id() );

        unset( $_POST[ 'gdpr_consent' ] );
    }

    /* set the last optin date */
    wpgh_update_contact_meta( $id, 'last_optin', time() );

    unset( $_POST[ 'step_id' ] );

    /* make sure the funnel for the step is active*/
    if ( ! wpgh_get_funnel_step_by_id( $step ) || ! wpgh_is_funnel_active( wpgh_get_step_funnel( $step ) ) )
        wp_die( __( 'This form is not accepting submissions right now.', 'groundhogg' ) );

    /* handle meta */
    foreach ( $_POST as $meta_name => $meta_value )
    {
        if ( in_array( $meta_name, $expected_fields ) ){
            wpgh_update_contact_meta( $id, sanitize_key( $meta_name ), sanitize_text_field( $meta_value ) );
        }
    }

    do_action( 'wpgh_form_submit', $step, $id );

    /* redirect to ensure cookie is set and can be used on the following page*/
    wp_redirect( $_SERVER['REQUEST_URI'] );
    die();
}

add_action( 'init', 'wpgh_form_submit_listener' );

/**
 * Ouput the html for the email preferences form.
 *
 * @return string
 */
function wpgh_email_preferences_form()
{

    $contact = wpgh_get_the_contact();

    if ( ! $contact )
        return __( 'No email to manage.' );

    ob_start();

    ?>
    <div class="gh-form-wrapper">
        <p><?php _e( 'Hi' )?> <strong><?php echo $contact->get_first(); ?></strong>,</p>
        <p><?php _e( 'You are managing your email preferences for the email address: ', 'groundhogg' ) ?> <strong><?php echo $contact->get_email(); ?></strong></p>
        <form id="email-preferences" class="gh-form" method="post" action="">
            <?php wp_nonce_field( 'change_email_preferences', 'email_preferences_nonce' ) ?>
            <?php if ( ! empty( $_POST ) ):
                ?><div class="gh-notice"><p><?php _e( 'Preferences Updated!', 'groundhogg' ); ?></p></div><?php
            endif;
            ?>
            <div class="gh-form-field">
                <p>
                    <label><input type="radio" name="preference" value="none" required> <?php _e( apply_filters( 'gh_no_limits_preferences_text', 'I love you guys. Send email whenever you want!' ), 'groundhogg' ); ?></label>
                </p>
            </div>
            <div class="gh-form-field">
                <p>
                    <label><input type="radio" name="preference" value="weekly" > <?php _e( apply_filters( 'gh_weekly_preferences_text', 'It\'s a bit much. Start sending me emails weekly.' ), 'groundhogg' ); ?></label>
                </p>
            </div>
            <div class="gh-form-field">
                <p>
                    <label><input type="radio" name="preference" value="monthly" > <?php _e( apply_filters( 'gh_monthly_preferences_text','Distance makes the heart grow fonder. Only send emails monthly.'), 'groundhogg' ); ?></label>
                </p>
            </div>
            <div class="gh-form-field">
                <p>
                    <label><input type="radio" name="preference" value="unsubscribe" > <?php _e( apply_filters( 'gh_unsubscribe_preferences_text','I no longer wish to receive any emails. Unsubscribe me!'), 'groundhogg' ); ?></label>
                </p>
            </div>
            <div class="gh-form-field">
                <p>
                    <input type='submit' name='change_preferences' value='<?php _e( apply_filters( 'gh_change_preferences_text', 'Change Preferences' ),'groundhogg'); ?>' >
                    <?php if ( wpgh_is_gdpr() ):?>
                        <input type='submit' name='delete_everything' value='<?php _e(apply_filters( 'gh_gdpr_delete_prteferences_text', 'Delete Everything You Know About Me' ), 'groundhogg'); ?>' >
                    <?php endif; ?>
                </p>
            </div>
        </form>
    </div>

    <?php

    $form = ob_get_contents();

    ob_end_clean();

    return $form;

}

add_shortcode( 'gh_email_preferences', 'wpgh_email_preferences_form' );

/**
 * Process changes to the subscription status of a contact.
 */
function wpgh_process_email_preferences_changes()
{
    if ( ! isset( $_POST[ 'email_preferences_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'email_preferences_nonce' ], 'change_email_preferences' ) )
        return;

    $contact = wpgh_get_the_contact();

    if ( ! $contact )
        return;

    if ( isset( $_POST[ 'delete_everything' ] ) )
    {

        do_action( 'wpgh_delete_everything', $contact->get_id() );

        wpgh_delete_contact( $contact->get_id() );

        $unsub_page = get_permalink( get_option( 'gh_unsubscribe_page' ) );

        do_action( 'wpgh_preference_unsubscribe', $contact->get_id() );

        wp_redirect( $unsub_page );
        die();
    }

    $preference = isset( $_POST[ 'preference' ] ) ? $_POST[ 'preference' ] : '';

    switch ( $preference ){
        case 'none':

            wpgh_update_contact( $contact->get_id(), 'optin_status', WPGH_CONFIRMED );

            do_action( 'wpgh_preference_none', $contact->get_id() );

            break;
        case 'weekly':

            wpgh_update_contact( $contact->get_id(), 'optin_status', WPGH_WEEKLY );

            do_action( 'wpgh_preference_weekly', $contact->get_id() );

            break;
        case 'monthly':

            wpgh_update_contact( $contact->get_id(), 'optin_status', WPGH_MONTHLY );

            do_action( 'wpgh_preference_monthly', $contact->get_id() );

            break;
        case 'unsubscribe':

            wpgh_update_contact( $contact->get_id(), 'optin_status', WPGH_UNSUBSCRIBED );

            $unsub_page = get_permalink( get_option( 'gh_unsubscribe_page' ) );

            do_action( 'wpgh_preference_unsubscribe', $contact->get_id() );

            wp_redirect( $unsub_page );
            die();
            break;
    }
}

add_action( 'init', 'wpgh_process_email_preferences_changes' );


/**
 * Output the form html based on the settings.
 *
 * @param $atts array the shortcode attributes
 * @return string the form html
 */
function wpgh_form_shortcode( $atts )
{
    $a = shortcode_atts( array(
        'fields' => 'first,last,email,phone,terms',
        'required' => 'first,last,email,phone,terms',
        'submit' => __( 'Submit' ),
        'success' => '',
        'labels' => 'on',
        'id' => 0,
        'classes' => '',
        'first' => __( 'First Name' ),
        'last' => __( 'Last Name' ),
        'email' => __( 'Email' ),
        'phone' => __( 'Phone' ),
        'terms' =>__( 'I agree to the Terms of Service.' , 'groundhogg' ),
        'gdpr' => __( 'I consent to receive marketing & transactional information from ' . get_option( 'gh_business_name' ) . '.' , 'groundhogg' )
    ), $atts );

    $fields = array_map( 'trim', explode( ',', $a['fields'] ) );
    $required_fields = array_map( 'trim', explode( ',', $a['required'] ) );

    $form = '<div class="gh-form-wrapper">';

    $form .= "<form method='post' class='gh-form " . $a[ 'classes' ] ."' action='" . esc_url_raw( $a['success'] ) . "'>";

    $form .= wp_nonce_field( 'gh_submit', 'gh_submit_nonce', true, false );

    $form .="<input type='hidden' name='step_id' value='" . $a['id'] . "'>";

    foreach ( $fields as $type ){

        $form .= '<div class="gh-form-field"><p>';

        $id = uniqid( 'gh-' );

        $required = in_array( $type, $required_fields )? 'required' : "" ;

        switch ( $type ) {
            case 'first':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . $a[ 'first' ];
                $form .= ' <input class="gh-form-input" type="text" name="first_name" id="' . $id . '" pattern="[A-Za-z \-\']+" title="' . __( 'Do not include numbers or special characters.', 'groundhogg' ) . '" placeholder="' . $a[ 'first' ] . '" '.$required. '>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'last':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' .  $a[ 'last' ];
                $form .= ' <input class="gh-form-input" type="text" name="last_name" id="' . $id . '" pattern="[A-Za-z \-\']+" title="' . __( 'Do not include numbers or special characters.', 'groundhogg' ) . '" placeholder="' . $a[ 'last' ]. '" '.$required. '>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'email':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . $a[ 'email' ];
                $form .= ' <input class="gh-form-input" type="email" name="email" id="' . $id . '" title="' . $a[ 'email' ] . '" placeholder="' . $a[ 'email' ] . '" '.$required. '>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'phone':
                if ( $a['labels'] === 'on' )
                    $form .= '<label>' . $a[ 'phone' ];
                $form .= ' <input class="gh-form-input" type="tel" name="phone" id="' . $id . '" title="' . __( 'Phone' ) . '" placeholder="' . $a[ 'phone' ] . '" '.$required. '>';
                if ( $a['labels'] === 'on' )
                    $form .= '</label>';
                break;
            case 'terms':
                $form .= '<label>';
                $form .= ' <input class="gh-form-input" type="checkbox" name="agree_terms" id="' . $id . '" title="' . __( 'Terms Agreement' ) . '" '.$required. '> ';
                $form .=  $a[ 'terms' ] . '</label>';
                break;
        }
        $form .= '</p></div>';
    }

    if ( wpgh_is_gdpr() )
    {

        $id = uniqid( 'gh-' );

        $form .= '<div class="gh-consent-field"><p>';

        $form .= '<label>';
        $form .= ' <input class="gh-form-input" type="checkbox" name="gdpr_consent" id="' . $id . '" title="' . __( 'Explicit Consent', 'groundhogg' ) . '" required> ';
        $form .=  $a[ 'gdpr' ] . '</label>';

        $form .= '</p></div>';
    }

    if ( wpgh_is_recaptcha_enabled() )
    {
        wp_enqueue_script( 'google-recaptcha-v2', 'https://www.google.com/recaptcha/api.js' );
        $form .= '<div class="gh-recaptcha-field"><p>';
        $form .= sprintf( '<div class="g-recaptcha" data-sitekey="%s"></div>', get_option( 'gh_recaptcha_site_key', '' ) );
        $form .= '</p></div>';
    }

    $form = apply_filters( 'wpgh_form_shortcode', $form );

    $form .= "<div class='gh-submit-field'><p><input type='submit' name='submit' value='" . $a['submit'] . "'></p></div>";
    $form .= '</form>';
    $form .= '</div>';

    return $form;
}

add_shortcode( 'gh_form_alt', 'wpgh_form_shortcode' );