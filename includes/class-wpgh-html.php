<?php
/**
 * HTML
 *
 * Helper class for reusable html markup. Mostly input elements and form elements.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_HTML
{

    /**
     * WPGH_HTML constructor.
     *
     * Set up the ajax calls.
     */
    public function __construct()
    {

        add_action( 'wp_ajax_gh_get_contacts', array( $this, 'gh_get_contacts' ) );
        add_action( 'wp_ajax_gh_get_emails', array( $this, 'gh_get_emails' ) );
        add_action( 'wp_ajax_gh_get_tags', array( $this, 'gh_get_tags' ) );
        add_action( 'wp_ajax_gh_get_benchmarks', array( $this, 'gh_get_benchmarks' ) );
        add_action( 'wp_ajax_gh_get_meta_keys', array( $this, 'get_meta_keys' ) );

    }

    public function checkbox( $args )
    {
        $a = shortcode_atts( array(
            'label'         => '',
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'value'         => '1',
            'checked'       => false,
            'title'         => '',
            'attributes'    => '',
            'required'      => false,
        ), $args );

        $required = $a[ 'required' ] ? 'required' : '';
        $checked = $a[ 'checked' ] ? 'checked' : '';

        return sprintf(
            "<label class='gh-checkbox-label'><input type='checkbox' name='%s' id='%s' class='%s' value='%s' title='%s' %s %s %s> %s</label>",
            esc_attr( $a[ 'name' ] ),
            esc_attr( $a[ 'id' ] ),
            esc_attr( $a[ 'class' ] ),
            esc_attr( $a[ 'value' ] ),
            esc_attr( $a[ 'title' ] ),
            $a[ 'attributes' ],
            $checked,
            $required,
            $a[ 'label' ]
        );
    }

    public function button($args )
    {
        $a = wp_parse_args( $args, array(
            'type'      => 'button',
            'text'      => '',
            'name'      => '',
            'id'        => '',
            'class'     => 'button button-secondary',
            'value'     => '',
            'attributes' => '',
        ) );

        $html = sprintf(
            "<button type='%s' id='%s' class='%s' name='%s' value='%s' %s>%s</button>",
            esc_attr( $a[ 'type'    ] ),
            esc_attr( $a[ 'id'      ] ),
            esc_attr( $a[ 'class'   ] ),
            esc_attr( $a[ 'name'    ] ),
            esc_attr( $a[ 'value'   ] ),
            esc_attr( $a[ 'attributes'  ] ),
            esc_attr( $a[ 'text'  ] )
        );

        return apply_filters( 'wpgh_html_input', $html, $args );
    }

    /**
     * Output a simple input field
     *
     * @param $args
     * @return string
     */
    public function input( $args )
    {
        $a = wp_parse_args( $args, array(
            'type'  => 'text',
            'name'  => '',
            'id'    => '',
            'class' => 'regular-text',
            'value' => '',
            'attributes' => '',
            'placeholder' => ''
        ) );

        $html = sprintf(
            "<input type='%s' id='%s' class='%s' name='%s' value='%s' placeholder='%s' %s>",
            esc_attr( $a[ 'type'    ] ),
            esc_attr( $a[ 'id'      ] ),
            esc_attr( $a[ 'class'   ] ),
            esc_attr( $a[ 'name'    ] ),
            esc_attr( $a[ 'value'   ] ),
            esc_attr( $a[ 'placeholder' ] ),
            esc_attr( $a[ 'attributes'  ] )
        );

        return apply_filters( 'wpgh_html_input', $html, $args );
    }

    /**
     * Wrapper function for the INPUT
     *
     * @param $args
     * @return string
     */
    public function number( $args )
    {

        $a = wp_parse_args( $args, array(
            'type'  => 'number',
            'name'  => '',
            'id'    => '',
            'class' => 'regular-text',
            'value' => '',
            'attributes' => '',
            'placeholder' => '',
            'min'       => 0,
            'max'       => 99999,
        ) );

        if ( ! empty( $a[ 'max' ] ) ){
            $a[ 'attributes' ] .= sprintf( ' max="%d"', $a[ 'max' ] );
        }

        if ( ! empty( $a[ 'min' ] ) ){
            $a[ 'attributes' ] .= sprintf( ' min="%d"', $a[ 'min' ] );
        }

        return $this->input( $a );
    }

    /**
     * Output a simple textarea field
     *
     * @param $args
     * @return string
     */
    public function textarea( $args )
    {
        $a = wp_parse_args( $args, array(
            'name'  => '',
            'id'    => '',
            'class' => '',
            'value' => '',
            'cols'  => '100',
            'rows'  => '7',
            'placeholder'   => '',
            'attributes'    => ''
        ) );

        $html = sprintf(
            "<textarea id='%s' class='%s' name='%s' cols='%s' rows='%s' placeholder='%s' %s>%s</textarea>",
            esc_attr( $a[ 'id'      ] ),
            esc_attr( $a[ 'class'   ] ),
            esc_attr( $a[ 'name'    ] ),
            esc_attr( $a[ 'cols'    ] ),
            esc_attr( $a[ 'rows'    ] ),
            esc_attr( $a[ 'placeholder' ] ),
            $a[ 'attributes'    ],
            $a[ 'value'         ]
        );

        return apply_filters( 'wpgh_html_textarea', $html, $args );

    }

    /**
     * Output simple HTML for a dropdown field.
     *
     * @param $args
     * @return string
     */
    public function dropdown( $args )
    {
        $a = wp_parse_args( $args, array(
            'name'              => '',
            'id'                => '',
            'class'             => '',
            'options'           => array(),
            'selected'          => '',
            'multiple'          => false,
            'option_none'       => 'Please Select One',
            'attributes'        => '',
            'option_none_value' => '',
        ) );

        $multiple           = $a[ 'multiple' ]             ? 'multiple'        : '';
        $a[ 'selected' ]    = is_array( $a[ 'selected' ] ) ? $a[ 'selected' ]  : array( $a[ 'selected' ] );

        $optionHTML = '';

        if ( ! empty( $a[ 'option_none' ] ) ){
            $optionHTML .= sprintf( "<option value='%s'>%s</option>",
                esc_attr( $a[ 'option_none_value' ] ),
                sanitize_text_field( $a[ 'option_none' ] )
            );
        }

        if ( ! empty( $a[ 'options' ] ) && is_array( $a[ 'options' ] ) )
        {
            $options = array_map( 'trim', $a[ 'options' ] );

            foreach ( $options as $value => $name ){

                $selected = ( in_array( $value, $a[ 'selected' ] ) ) ? 'selected' : '';

                $optionHTML .= sprintf(
                    "<option value='%s' %s>%s</option>",
                    esc_attr( $value ),
                    $selected,
                    sanitize_text_field( $name )
                );

            }

        }

        $html = sprintf(
            "<select name='%s' id='%s' class='%s' %s %s>%s</select>",
            esc_attr( $a[ 'name' ] ),
            esc_attr( $a[ 'id' ] ),
            esc_attr( $a[ 'class' ] ),
            $a[ 'attributes' ],
            $multiple,
            $optionHTML
        );

        return apply_filters( 'wpgh_html_dropdown', $html, $args );

    }

    /**
     * Provide a dropdown for possible contact owners.
     * Includes all ADMINs, MARKETERS, and SALES MANAGERs
     *
     * @param $args
     * @return string
     */
    public function dropdown_owners( $args=array() )
    {

        $a = wp_parse_args( $args, array(
            'name'              => 'owner_id',
            'id'                => 'owner_id',
            'class'             => 'gh-owners',
            'options'           => array(),
            'selected'          => '',
            'multiple'          => false,
            'option_none'       => 'Please Select an Owner',
            'attributes'        => '',
            'option_none_value' => 0,
        ) );

        if ( empty( $a[ 'options' ] ) ){

            $owners = get_users( array( 'role__in' => array( 'administrator', 'marketer', 'sales_manager' ) ) );

            /**
             * @var $owner WP_User
             */
            foreach ( $owners as $owner ){

                $a[ 'options' ][ $owner->ID ] = sprintf( '%s (%s)', $owner->display_name, $owner->user_email );

            }

        }

        return $this->dropdown( $a );
    }

    /**
     * @param array $args
     *
     * @return string
     */
    public function round_robin( $args=array() )
    {
        $a = wp_parse_args( $args, array(
            'name'              => 'round_robin',
            'id'                => 'round_robin',
            'class'             => 'gh-select2',
            'data'              => array(),
            'selected'          => '',
            'multiple'          => true,
            'option_none'       => 'Please Select 1 or More Owners',
            'attributes'        => '',
            'option_none_value' => 0,
        ) );

        if ( empty( $a[ 'data' ] ) ){

            $owners = get_users( array( 'role__in' => array( 'administrator', 'marketer', 'sales_manager' ) ) );

            /**
             * @var $owner WP_User
             */
            foreach ( $owners as $owner ){

                $a[ 'data' ][ $owner->ID ] = sprintf( '%s (%s)', $owner->display_name, $owner->user_email );

            }

        }

        return $this->select2( $a );
    }

    /**
     * Select 2 html input
     *
     * @param $args
     *
     * @type $selected array list of $value which are selected
     * @type $data array list of $value => $text options for the select 2
     *
     * @return string
     */
    public function select2( $args )
    {
        $a = wp_parse_args( $args, array(
            'name'              => '',
            'id'                => '',
            'class'             => 'gh-select2',
            'data'              => array(),
            'selected'          => array(),
            'multiple'          => false,
            'placeholder'       => 'Please Select One',
            'attributes'        => '',
            'tags'              => false,
        ) );

        $multiple           = $a[ 'multiple' ]              ? 'multiple'             : '';
        $tags               = $a[ 'tags' ]                  ? 'data-tags="true"'     : '';

        $a[ 'selected' ]    = is_array( $a[ 'selected' ] )  ? $a[ 'selected' ]  : array( $a[ 'selected' ] );

        $optionHTML = '';

        if ( ! empty( $a[ 'data' ] ) && is_array( $a[ 'data' ] ) )
        {
            $options = array_map( 'trim', $a[ 'data' ] );

            $optionHTML .= sprintf(
                "<option value=''>%s</option>",
                $a[ 'placeholder' ]
            );

            foreach ( $options as $value => $name ){

                $selected = ( in_array( $value, $a[ 'selected' ] ) ) ? 'selected' : '';

                $optionHTML .= sprintf(
                    "<option value='%s' %s>%s</option>",
                    esc_attr( $value ),
                    $selected,
                    sanitize_text_field( $name )
                );

            }

        }

        $html = sprintf(
            "<select name='%s' id='%s' class='%s' data-placeholder='%s' %s %s %s>%s</select>",
            esc_attr( $a[ 'name' ] ),
            esc_attr( $a[ 'id' ] ),
            esc_attr( $a[ 'class' ] ),
            esc_attr( $a[ 'placeholder' ] ),
            $a[ 'attributes' ],
            $tags,
            $multiple,
            $optionHTML
        );

        wp_enqueue_script( 'select2' );
        wp_enqueue_style( 'select2' );
        wp_enqueue_script( 'wpgh-admin-js' );

        return apply_filters( 'wpgh_html_select2', $html, $args );

    }

    /**
     * Get json tag results for tag picker
     */
    public function gh_get_tags()
    {
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_tags' ) )
            wp_die( 'No access to tags.' );

        $value = isset( $_REQUEST[ 'q' ] ) ? sanitize_text_field( $_REQUEST[ 'q' ] ) : '';

        if ( empty( $value ) ){
            $tags = WPGH()->tags->get_tags();
        } else {
            $tags = WPGH()->tags->search( $value );
        }

        $json = array();

        foreach ( $tags as $i => $tag ) {

            $json[] = array(
                'id' => $tag->tag_id,
                'text' => sprintf( "%s (%s)", $tag->tag_name, $tag->contact_count )
            );

        }

        $results = array( 'results' => $json, 'more' => false );

        wp_die( json_encode( $results ) );
    }


    /**
     * Return the HTML for a tag picker
     *
     * @param $args
     * @return string
     */
    public function tag_picker( $args )
    {
        $a = wp_parse_args( $args, array(
            'name'              => 'tags[]',
            'id'                => 'tags',
            'class'             => 'gh-tag-picker',
            'data'              => array(),
            'selected'          => array(),
            'multiple'          => true,
            'placeholder'       => __( 'Please Select One Or More Tags', 'groundhogg' ),
            'tags'              => true,
        ) );

        if ( is_array( $a[ 'selected' ] ) ){

            foreach ( $a[ 'selected' ] as $tag_id ){

                if ( WPGH()->tags->exists( $tag_id ) ){

                    $tag = WPGH()->tags->get( $tag_id );

                    $a[ 'data' ][ $tag_id ] = sprintf( "%s (%s)", $tag->tag_name, $tag->contact_count );

                }

            }
        }


        return $this->select2( $a );
    }

    /**
     * Get json contact results for contact picker
     */
    public function gh_get_contacts()
    {
        if ( ! is_user_logged_in() || ! current_user_can( 'view_contacts' ) )
            wp_die( 'No access to contacts.' );

        $value = isset( $_REQUEST[ 'q' ] )? sanitize_text_field( $_REQUEST[ 'q' ] ) : '';

        $contacts = WPGH()->contacts->search( $value );

        $json = array();

        foreach ( $contacts as $i => $contact ) {

            $json[] = array(
                'id' => $contact->ID,
                'text' => sprintf( "%s %s (%s)", $contact->first_name, $contact->last_name, $contact->email )
            );

        }

        $results = array( 'results' => $json, 'more' => false );

        wp_die( json_encode( $results ) );    }


    /**
     * Return the HTML of a dropdown for contacts
     *
     * @param $args
     * @return string
     */
    public function dropdown_contacts( $args )
    {
        $a = wp_parse_args( $args, array(
            'name'              => 'contact_id',
            'id'                => 'contact_id',
            'class'             => 'gh-contact-picker',
            'data'              => array(),
            'selected'          => array(),
            'multiple'          => false,
            'placeholder'       => __( 'Please Select a Contact', 'groundhogg' ),
            'tags'              => false,
        ) );

        foreach ( $a[ 'selected' ] as $contact_id ){

            $contact = new WPGH_Contact( $contact_id );

            if ( $contact->exists() ) {

                $a[ 'data' ][ $contact_id ] = sprintf( "%s %s (%s)", $contact->first_name, $contact->last_name, $contact->email );

            }

        }


        return $this->select2( $a );
    }

    /**
     * Get json email results for email picker
     */
    public function gh_get_emails()
    {

        if ( ! is_user_logged_in() || ! current_user_can( 'edit_emails' ) )
            wp_die( 'No access to emails.' );

        if ( isset(  $_REQUEST[ 'q' ] ) ){
            $query_args[ 'search' ] = $_REQUEST[ 'q' ];
        }

        $query_args[ 'status' ] = 'ready';
        $data = WPGH()->emails->get_emails( $query_args );

        $query_args[ 'status' ] = 'draft';
        $data2 = WPGH()->emails->get_emails( $query_args );

        $data = array_merge( $data, $data2 );

        $json = array();

        foreach ( $data as $i => $email ) {

            $json[] = array(
                'id' => $email->ID,
                'text' => $email->subject . ' (' . $email->status . ')'
            );

        }

        $results = array( 'results' => $json, 'more' => false );

        wp_die( json_encode( $results ) );
    }

    /**
     * Return the html for an email picker
     *
     * @param $args
     * @return string
     */
    public function dropdown_emails( $args )
    {
        $a = wp_parse_args( $args, array(
            'name'              => 'email_id',
            'id'                => 'email_id',
            'class'             => 'gh-email-picker',
            'data'              => array(),
            'selected'          => array(),
            'multiple'          => false,
            'placeholder'       => __( 'Please Select an Email', 'groundhogg' ),
            'tags'              => false,
        ) );

        foreach ( $a[ 'selected' ] as $email_id ){

            if ( WPGH()->emails->exists( $email_id ) ){

                $email =  WPGH()->emails->get( $email_id );
                $a[ 'data' ][ $email_id ] = $email->subject . ' (' . $email->status . ')';

            }

        }

        return $this->select2( $a );
    }

	/**
	 * Get json email results for email picker
	 */
	public function gh_get_benchmarks()
	{

		if ( ! is_user_logged_in() || ! current_user_can( 'edit_funnels' ) )
			wp_die( 'No access to benchmarks.' );

		if ( isset(  $_REQUEST[ 'q' ] ) ){
			$query_args[ 'search' ] = $_REQUEST[ 'q' ];
		}

		$query_args[ 'step_group' ] = 'benchmark';
		$data = WPGH()->steps->get_steps( $query_args );

		$json = array();

		foreach ( $data as $i => $step ) {

			$json[] = array(
				'id' => $step->ID,
				'text' => $step->step_title . ' (' . str_replace( '_', ' ', $step->step_type ) . ')'
			);

		}

		$results = array( 'results' => $json, 'more' => false );

		wp_die( json_encode( $results ) );
	}

	/**
	 * Returns a picker for benchmarks.
	 * Included in core so that we don't need to include it in every extension we write.
	 *
	 * @param $args
	 *
	 * @return string
	 */
	public function benchmark_picker( $args=array() )
	{

		$a = wp_parse_args( $args, array(
			'name'              => 'benchmarks[]',
			'id'                => 'benchmarks',
			'class'             => 'gh-benchmark-picker',
			'data'              => array(),
			'selected'          => array(),
			'multiple'          => true,
			'placeholder'       => __( 'Please Select 1 or more Benchmarks', 'groundhogg' ),
			'tags'              => false,
		) );

		foreach ( $a[ 'selected' ] as $benchmark_id ){

			if ( WPGH()->steps->exists( $benchmark_id ) ){

				$step = new WPGH_Step( $benchmark_id );
				$a[ 'data' ][ $benchmark_id ] = $step->title . ' (' . str_replace( '_', ' ', $step->type ) . ')';

			}

		}

		return $this->select2( $a );
	}

    /**
     * Returns a select 2 compatible json object with contact data meta keys
     */
	public function get_meta_keys(){
        if ( ! is_user_logged_in() || ! current_user_can( 'view_contacts' ) )
            wp_die( 'No access to contacts.' );

        $json = array();

        $data = WPGH()->contact_meta->get_keys();

        foreach ( $data as $i => $key ) {

            $json[] = array(
                'id' => $key,
                'text' => $key
            );

        }

        $results = array( 'results' => $json, 'more' => false );

        wp_die( json_encode( $results ) );
    }

    /**
     * Get a meta key picker. useful for searching.
     *
     * @param array $args
     * @return string
     */
	public function meta_key_picker( $args=array() ){
        $a = wp_parse_args( $args, array(
            'name'              => 'key',
            'id'                => 'key',
            'class'             => 'gh-metakey-picker',
            'data'              => array(),
            'selected'          => array(),
            'multiple'          => false,
            'placeholder'       => __( 'Please Select 1 or more keys', 'groundhogg' ),
            'tags'              => false,
        ) );

        foreach ( $a[ 'selected' ] as $key ){

            $a[ 'data' ][ $key ] = $key;

        }

        return $this->select2( $a );
    }

    /**
     * Return HTML for a color picker
     *
     * @param $args
     * @return string
     */
    public function color_picker( $args )
    {
        $a = wp_parse_args( $args, array(
            'name'      => '',
            'id'        => '',
            'value'     => '',
            'default'   => ''
        ) );

        $html = sprintf(
            "<input type=\"text\" id=\"%s\" name=\%s\" class=\"wpgh-color\" value=\"%s\" data-default-color=\"%s\" />",
            esc_attr( $a[ 'id'      ] ),
            esc_attr( $a[ 'name'    ] ),
            esc_attr( $a[ 'value'   ] ),
            esc_attr( $a[ 'default' ] )
        );

        wp_enqueue_style( 'wp-color-picker' );

        return apply_filters( 'wpgh_html_color_picker', $html, $args );
    }

    public function font_picker( $args )
    {
        $a = wp_parse_args( $args, array(
            'name'      => '',
            'id'        => '',
            'selected'  => '',
            'fonts'     => array(
                'Arial, sans-serif'                                     => 'Arial',
                'Arial Black, Arial, sans-serif'                        => 'Arial Black',
                'Century Gothic, Times, serif'                          => 'Century Gothic',
                'Courier, monospace'                                    => 'Courier',
                'Courier New, monospace'                                => 'Courier New',
                'Geneva, Tahoma, Verdana, sans-serif'                   => 'Geneva',
                'Georgia, Times, Times New Roman, serif'                => 'Georgia',
                'Helvetica, Arial, sans-serif'                          => 'Helvetica',
                'Lucida, Geneva, Verdana, sans-serif'                   => 'Lucida',
                'Tahoma, Verdana, sans-serif'                           => 'Tahoma',
                'Times, Times New Roman, Baskerville, Georgia, serif'   => 'Times',
                'Times New Roman, Times, Georgia, serif'                => 'Times New Roman',
                'Verdana, Geneva, sans-serif'                           => 'Verdana',
            ),
        ) );

        /* set options so that parse args doesn't remove the fonts */
        $a[ 'options' ] = $a[ 'fonts' ];


        return $this->dropdown( $a );

    }

    public function image_picker( $args )
    {
        $a = wp_parse_args( $args, array(
            'id'        => '',
            'name'      => '',
            'class'     => '',
            'value'     => '',
        ) );

        $html = $this->input( array(
            'id'            => $a[ 'id' ],
            'name'          => $a[ 'id' ],
            'type'          => 'button',
            'value'         => __( 'Upload Image' ),
            'class'         => 'button gh-image-picker',
        ));

        $html.="<div style='margin-top: 10px;'></div>";

        $html .= $this->input( array(
            'id'    => $a[ 'id' ] . '-src',
            'name'  => $a[ 'id' ] . '-src',
            'placeholder' => __( 'Src' ),
            'class' => $a[ 'class' ]
        ) );

        $html .= $this->input( array(
            'id'    => $a[ 'id' ] . '-alt',
            'name'  => $a[ 'id' ] . '-alt',
            'placeholder' => __( 'Alt Tag' ),
            'class' => $a[ 'class' ]
        ) );

        $html .= $this->input( array(
            'id'    => $a[ 'id' ] . '-title',
            'name'  => $a[ 'id' ] . '-title',
            'placeholder' => __( 'Title' ),
            'class' => $a[ 'class' ]
        ) );

        wp_enqueue_media();
        wp_enqueue_script('gh-media-picker', WPGH_ASSETS_FOLDER . 'js/admin/media-picker.js', filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/media-picker.js' ) );

        return $html;
    }

}