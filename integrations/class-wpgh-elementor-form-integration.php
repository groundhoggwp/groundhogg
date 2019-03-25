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

        // Get submitted Form data
        $raw_fields = $record->get( 'fields' );

        // Normalize the Form Data
        $fields = [];
        foreach ( $raw_fields as $id => $field ) {
            $fields[ $id ] = $field['value'];
        }

        if ( ! empty( $fields[ 'name' ] ) ){
        	$parts = wpgh_split_name( $fields[ 'name' ] );
        	$fields[ 'first_name' ] = $parts[ 0 ];
        	$fields[ 'last_name' ] = $parts[ 1 ];
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

        wpgh_after_form_submit_handler( $contact );

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