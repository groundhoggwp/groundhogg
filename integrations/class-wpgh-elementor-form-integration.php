<?php
/**
 * Class WPGH_Elementor_Form_Integration
 * @see https://developers.elementor.com/custom-form-action/
 * Custom elementor form action after submit to add a subsciber to
 * Groundhogg list via API
 */
class WPGH_Elementor_Form_Integration extends \ElementorPro\Modules\Forms\Classes\Action_Base {
    /**
     * Get Name
     *
     * Return the action name
     *
     * @access public
     * @return string
     */
    public function get_name() {
        return 'groundhogg';
    }

    /**
     * Get Label
     *
     * Returns the action label
     *
     * @access public
     * @return string
     */
    public function get_label() {
        return __( 'Groundhogg', 'groundhogg' );
    }

    /**
     * Run
     *
     * Runs the action after submit
     *
     * @access public
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
     */
    public function run( $record, $ajax_handler ) {
        $settings = $record->get( 'form_settings' );

        //  Make sure that there is a tag to apply
        if ( empty( $settings['groundhogg_tags'] ) ) {
            return;
        }

        // Make sure that there is a Sendy Email field ID
        // which is required by Sendy's API to subsribe a user
        if ( empty( $settings['groundhogg_email_field'] ) ) {
            return;
        }

        // Get submitted Form data
        $raw_fields = $record->get( 'fields' );

        // Normalize the Form Data
        $fields = [];
        foreach ( $raw_fields as $id => $field ) {
            $fields[ $id ] = $field['value'];
        }

        $args = wp_parse_args( $fields, array(
            'first_name' => '',
            'last_name' => '',
            'email' => ''
        ) );

        if ( empty( $args[ 'email' ] ) ){
            return;
        }

        //magic time
        $id = WPGH()->contacts->add( $args );

        if ( ! $id ){
            return;
        }

        $contact = wpgh_get_contact( $id );

        $contact->update_meta( 'ip_address', wpgh_get_visitor_ip() );

        if ( ! $contact->get_meta( 'lead_source' ) ){
            $contact->update_meta( 'lead_source', WPGH()->tracking->lead_source );
        }

        if ( ! $contact->get_meta( 'source_page' ) ){
            $contact->update_meta( 'source_page', wpgh_get_referer() );
        }

        if ( isset( $fields[ 'agree_terms'] ) ){
            $contact->update_meta( 'terms_agreement', 'yes' );
            $contact->update_meta( 'terms_agreement_date', date_i18n( wpgh_get_option( 'date_format' ) ) );
            do_action( 'wpgh_agreed_to_terms', $contact, $this );
            unset( $fields[ 'agree_terms'] );
        }

        if ( isset( $fields['gdpr_consent'] ) ){
            $contact->update_meta( 'gdpr_consent', 'yes' );
            $contact->update_meta( 'gdpr_consent_date', date_i18n( wpgh_get_option( 'date_format' ) ) );
            do_action( 'wpgh_gdpr_consented', $contact, $this );
            unset( $fields['gdpr_consent'] );
        }

        /* If the contact previously unsubed then reopt them back in.  */
        if ( $contact->optin_status === WPGH_UNSUBSCRIBED ) {
            $contact->change_marketing_preference(WPGH_UNCONFIRMED );
        }

        $contact->update_meta( 'last_optin', time() );

        $ignore = array(
            'first_name',
            'last_name',
            'email'
        );

        foreach ( $fields as $key => $value ) {

            $key = sanitize_key( $key );

            if ( is_array( $value ) ){
                $value = implode( ', ', $value );
            }

            if ( strpos( $value, PHP_EOL  ) !== false ){
                $value = sanitize_textarea_field( stripslashes( $value ) );
            } else {
                $value = sanitize_text_field( stripslashes( $value ) );
            }

            if ( ! in_array( $key, $ignore ) ){
                $value = apply_filters( 'wpgh_sanitize_submit_value', $value, null );
                $contact->update_meta( $key, $value );
            }

        }

        $contact->apply_tag( wp_parse_id_list( $settings['groundhogg_tags'] ) );

    }

    /**
     * Register Settings Section
     *
     * Registers the Action controls
     *
     * @access public
     * @param \Elementor\Widget_Base $widget
     */
    public function register_settings_section( $widget ) {
        $widget->start_controls_section(
            'section_groundhogg',
            [
                'label' => __( 'Groundhogg', 'groundhogg' ),
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]
        );

        $tags = WPGH()->tags->get_tags();

        $tag_options = array();

        $default = 0;
        foreach ( $tags as $tag ){
            if ( ! $default ){$default = $tag->tag_id;}
            $tag_options[ $tag->tag_id ] = $tag->tag_name;
        }

        $widget->add_control(
            'groundhogg_tags',
            [
                'label' => __( 'Apply Groundhogg Tags', 'groundhogg' ),
                'label_block' => true,
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $tag_options,
                'default' => $default,
                'description' => __( 'Once a contact is created this tag will be applied.', 'groundhogg' ),
            ]
        );

        $widget->end_controls_section();

    }

    /**
     * On Export
     *
     * Clears form settings on export
     * @access Public
     * @param array $element
     */
    public function on_export( $element ) {
        unset(
            $element['groundhogg_tags']
        );
    }
}