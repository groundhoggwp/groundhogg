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

	    // Create a map
	    $map = [];

	    // Normalize the Form Data
	    $fields = [];
	    $raw_fields = $record->get( 'fields' );
	    foreach ( $raw_fields as $id => $field ) {

//            // Generate the field map
//            $map_id = sprintf( 'map_%s', $id );
//            if ( key_exists( $map_id, $settings ) ){
//                $map[ $id ] = $settings[ $map_id ];
//            }

            $fields[ $id ] = $field['value'];
        }

        // Ensure that mapped fields exist.
        // TODO Revisit this at a later date.
        if ( ! empty( $map ) || false ){
            $contact = wpgh_generate_contact_with_map( $fields, $map );
            if ( $contact ){
                $contact->apply_tag( wp_parse_id_list( $settings['groundhogg_tags'] ) );
            }
            wpgh_after_form_submit_handler( $contact );

            //Stop here.
            return;
        }

        ######### BACKWARDS COMPAT BEYOND THIS POINT ###########

        // If the map don't exist, use the old integration.
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

        wpgh_after_form_submit_handler( $contact );
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


        // Disable new field mapping for now.
        if ( false ) {

            $fields = $widget->get_settings('form_fields' );

            foreach ($fields as $field) {

                $field_label = $field['field_label'];
                $field_id = $field['_id'];

                $map_id = sprintf('map_%s', $field_id);
                $setting_label = sprintf(__("Map %s", 'groundhogg'), $field_label);

                $widget->add_control(
                    $map_id,
                    [
                        'label' => $setting_label,
                        'label_block' => true,
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'multiple' => true,
                        'options' => wpgh_get_mappable_fields(),
                        'default' => get_key_from_column_label( $field_id ),
                    ]
                );
            }
        }

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