<?php
namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HTML
 *
 * Helper class for reusable html markup. Mostly input steps and form steps.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class HTML
{

    const INPUT             = 'input';
    const NUMBER            = 'number';
    const BUTTON            = 'button';
    const TOGGLE            = 'toggle';
    const CHECKBOX          = 'checkbox';
    const MODAL_LINK        = 'modal_link';
    const RANGE             = 'range';
    const TEXTAREA          = 'textarea';
    const ROUND_ROBIN       = 'round_robin';
    const SELECT2           = 'select2';
    const TAG_PICKER        = 'tag_picker';
    const FONT_PICKER       = 'font_picker';
    const DATE_PICKER       = 'date_picker';
    const LINK_PICKER       = 'link_picker';
    const COLOR_PICKER      = 'color_picker';
    const IMAGE_PICKER      = 'image_picker';
    const BENCHMARK_PICKER  = 'benchmark_picker';
    const META_KEY_PICKER   = 'meta_key_picker';
    const DROPDOWN          = 'dropdown';
    const DROPDOWN_CONTACTS = 'dropdown_contacts';
    const DROPDOWN_EMAILS   = 'dropdown_emails';
    const DROPDOWN_OWNERS   = 'dropdown_owners';
    const DROPDOWN_SMS      = 'dropdown_sms';

	/**
     * WPGH_HTML constructor.
     *
     * Set up the ajax calls.
     */
    public function __construct()
    {
        add_action( 'wp_ajax_gh_get_contacts',   [ $this, 'ajax_get_contacts' ] );
        add_action( 'wp_ajax_gh_get_emails',     [ $this, 'ajax_get_emails' ] );
        add_action( 'wp_ajax_gh_get_sms',        [ $this, 'ajax_get_sms' ] );
        add_action( 'wp_ajax_gh_get_tags',       [ $this, 'ajax_get_tags' ] );
        add_action( 'wp_ajax_gh_get_benchmarks', [ $this, 'ajax_get_benchmarks' ] );
        add_action( 'wp_ajax_gh_get_meta_keys',  [ $this, 'ajax_get_meta_keys' ] );
    }

    /**
     * @param array $args
     * @param array $cols
     * @param array $rows
     * @param bool $footer
     */
    public function list_table($args=[], $cols=[], $rows=[], $footer=true )
    {
        $args = wp_parse_args( $args, [
            'class' => ''
        ] );

        $args[ 'class' ] .= 'wp-list-table widefat fixed striped';

        ?>
        <table <?php echo array_to_atts( $args ); ?> >
        <thead>
        <tr>
            <?php foreach ( $cols as $col => $name ): ?>
            <th><?php echo $name; ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php if ( ! empty( $rows ) ): ?>

        <?php foreach ( $rows as $row => $cells ): ?>
            <tr>
                <?php foreach ( $cells as $cell => $content ): ?>
                    <td><?php echo $content; ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        <?php else:

        $col_span = count( $cols );
        echo $this->wrap( __( 'No items found...', 'groundhogg' ), 'td', [ 'colspan' => $col_span ] );

        endif; ?>
        </tbody>
            <?php if ( $footer ): ?>
        <tfoot>
        <?php foreach ( $cols as $col => $name ): ?>
            <th><?php echo $name; ?></th>
        <?php endforeach; ?>
        </tfoot>
        <?php endif; ?>
        </table>
        <?php
    }

    public function tabs( $tabs=[], $active_tab=false )
    {
        if ( empty( $tabs ) ){
            return;
        }

        if ( ! $active_tab ){
            $active_tab = get_request_var( 'tab' );

            // Get first Tab
            if ( ! $active_tab ){
                $tab_keys = array_keys( $tabs );
                $active_tab = array_shift( $tab_keys );
            }
        }

        ?>
        <h2 class="nav-tab-wrapper">
            <?php foreach ( $tabs as $id => $tab ):

                echo html()->e( 'a', [
                    'href' => esc_url( add_query_arg( [ 'tab' => $id ], $_SERVER[ 'REQUEST_URI' ] ) ),
                    'class' => 'nav-tab' . ( $active_tab == $id ? ' nav-tab-active' : '' ),
                    'id' => $id,
                ], $tab );

            endforeach; ?>
        </h2>
        <?php
    }

    /**
     * Start a form table cuz we use LOTS of those!!!
     *
     * @param array $args
     */
    public function start_form_table( $args=[] )
    {
        $args = wp_parse_args( $args, [
            'title' => '',
            'class' => ''
        ] );

        if ( ! empty( $args[ 'title' ] ) ){
            ?><h3><?php echo $args[ 'title' ]; ?></h3><?php
        }
        ?>
<table class="form-table <?php esc_attr_e( $args[ 'class' ] ) ?>">
    <tbody>
<?php
    }

    public function start_row( $args = [] )
    {
        $args = wp_parse_args( $args, [
            'title' => '',
            'class' => '',
            'id' => ''
        ] );

        printf( "<tr title='%s' class='%s' id='%s'>",
            esc_attr( $args[ 'title' ] ),
            esc_attr( $args[ 'class' ] ),
            esc_attr( $args[ 'id' ] ) );
    }

    public function end_row( $args = [] )
    {
        printf( "</tr>" );
    }

    public function th( $content, $args = [] )
    {
        if ( is_array( $content ) ){
            $content = implode( '', $content );
        }

        $args = wp_parse_args( $args, [
            'title' => '',
            'class' => '',
        ] );

        echo $this->wrap( $content, 'th', $args );
    }

    public function td( $content, $args = [] )
    {
        if ( is_array( $content ) ){
            $content = implode( '', $content );
        }

        $args = wp_parse_args( $args, [
            'title' => '',
            'class' => '',
        ] );

        echo $this->wrap( $content, 'td', $args );
    }

    /**
     * Return P description.
     *
     * @param $text
     * @return string
     */
    public function description( $text ){
        return sprintf( '<p class="description">%s</p>', $text );
    }

    /**
     * Add a form table row
     *
     * @param array $args
     * @param bool $tr_wrap whether the control should be wrapped in a TR tag
     */
    public function add_form_control( $args=[], $tr_wrap = true )
    {
        $args = wp_parse_args( $args, [
            'label' => '',
            'type' => self::INPUT,
            'field' => [],
            'description' => ''
        ] );

        if ( ! method_exists( $this, $args[ 'type' ] ) ){
            return;
        }

        if ( $tr_wrap ):

        ?>
        <tr class="form-row">
            <th><?php echo $args[ 'label' ]; ?></th><td><?php echo call_user_func( [ $this, $args[ 'type' ] ], $args[ 'field' ] );
        if ( ! empty( $args[ 'description' ] ) ){
            ?><p class="description"><?php echo $args[ 'description' ]; ?></p><?php
        } ?></td>
        </tr>
            <?php
        else:
            ?>
            <div class="form-row">
                <label><?php echo $args[ 'label' ]; ?><?php echo call_user_func( [ $this, $args[ 'type' ] ], $args[ 'field' ] );?></label>
                <?php if ( ! empty( $args[ 'description' ] ) ){
                    ?><p class="description"><?php echo $args[ 'description' ]; ?></p><?php
                } ?>
            </div>
            <?php
        endif;
    }

    /**
     * Wrap arbitraty HTML in another element
     *
     * @param string $content
     * @param string $e
     * @param array $atts
     * @return string
     */
    public function wrap( $content = '', $e = 'div', $atts = [] )
    {
        if ( is_array( $content ) ){
            $content = implode( '', $content );
        }

        return sprintf( '<%1$s %2$s>%3$s</%1$s>', esc_html( $e ), array_to_atts( $atts ), $content );
    }

	/**
     * Generate an html element.
     *
	 * @param string $e
	 * @param array $atts
	 * @param bool $self_closing
	 *
	 * @return string
	 */
    public function e( $e = 'div', $atts = [], $content='', $self_closing = true )
    {
        if ( ! empty( $content ) || ! $self_closing ){
            return $this->wrap( $content, $e, $atts );
        }

        return sprintf( '<%1$s %2$s/>', esc_html( $e ), array_to_atts( $atts ) );
    }


    public function end_form_table()
    {
        ?></tbody></table><?php
    }

    /**
     * @param array $args
     * @return string
     */
    public function editor( $args = [] )
    {
        $args = wp_parse_args( $args, [
            'id' => '',
            'content' => '',
            'settings' => []
        ] );

        ob_start();

        wp_editor( $args[ 'content' ], $args[ 'id' ], $args[ 'settings' ] );

       return ob_get_clean();
    }

	/**
	 * Output a simple input field
	 *
	 * @param $args
	 * @return string
	 */
	public function input( $args=[] )
	{
		$a = wp_parse_args( $args, array(
			'type'  => 'text',
			'name'  => '',
			'id'    => '',
			'class' => 'regular-text',
			'value' => '',
			'placeholder' => '',
		) );

		$specials = [
		    'required',
            'checked',
            'multiple'
        ];

		// Backwards compat.
		foreach ( $specials as $special ){
		    if ( ! isset_not_empty( $a, $special ) ){
		        unset( $a[ $special ] );
            }
        }

		if ( isset_not_empty( $a, 'attributes' ) && is_array( $a[ 'attributes' ] ) ){
		    $a = array_merge( $a, $a[ 'attributes' ] );
		    unset( $a[ 'attributes' ] );
        }

		$html = $this->e( 'input', $a );

		return apply_filters( 'groundhogg/html/input', $html, $a );
	}

	/**
	 * Wrapper function for the INPUT
	 *
	 * @param $args
	 * @return string
	 */
	public function number( $args=[] )
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
			'step'      => 1
		) );

		return apply_filters( 'groundhogg/html/number', $this->input( $a ), $a );
	}

	/**
	 * Output a button
	 *
	 * @param $args
	 *
	 * @return string
	 */
	public function button( $args=[] )
    {
        $a = wp_parse_args( $args, array(
            'type'      => 'button',
            'text'      => '',
            'name'      => '',
            'id'        => '',
            'class'     => 'button button-secondary',
            'value'     => '',
        ) );

        $text = $a[ 'text' ];
        unset( $a[ 'text' ] );

        return apply_filters( 'groundhogg/html/button', $this->wrap( $text, 'button', $a ), $a );
    }

	/**
	 * Output a checkbox
	 *
	 * @param $args
	 *
	 * @return string
	 */
	public function checkbox( $args=[] )
	{
		$a = shortcode_atts( array(
			'label'         => '',
			'type'          => 'checkbox',
			'name'          => '',
			'id'            => '',
			'class'         => '',
			'value'         => '1',
			'checked'       => false,
			'title'         => '',
		), $args );

		$html = $this->wrap( $this->input( $a ) . '&nbsp;' . $a[ 'label' ], 'label', [ 'class' => 'gh-checkbox-label' ] );

		return apply_filters( 'groundhogg/html/checkbox', $html, $a );
	}

	public function help_icon( $link='' )
    {
        return $this->modal_link( [
            'title'     => 'Help',
            'text'      => '',
            'footer_button_text' => __( 'Close' ),
            'id'        => '',
            'source'    => $link,
            'height'    => 800,
            'width'     => 1000,
            'footer'    => 'true',
            'preventSave'    => 'true',
            'class' => 'dashicons dashicons-editor-help'
        ] );
    }

	/**
	 * Generate a link that activates the Groundhogg modal
	 *
	 * @param array $args
	 *
	 * @return string
	 */
    public function modal_link( $args=[] )
    {
        $a = wp_parse_args( $args, array(
            'title'     => 'Modal',
            'text'      => __( 'Open Modal', 'groundhogg' ),
            'footer_button_text' => __( 'Save Changes' ),
            'id'        => '',
            'class'     => 'button button-secondary',
            'source'    => '',
            'height'    => 500,
            'width'     => 500,
            'footer'    => 'true',
            'preventSave'    => 'true',
        ) );

        enqueue_groundhogg_modal();

        $html = sprintf(
            "<a title='%s' id='%s' class='%s trigger-popup' href='#source=%s&footer=%s&width=%d&height=%d&footertext=%s&preventSave=%s' >%s</a>",
            esc_attr( $a[ 'title'  ] ),
            esc_attr( $a[ 'id'     ] ),
            esc_attr( $a[ 'class'  ] ),
            urlencode( $a[ 'source' ] ),
            esc_attr( $a[ 'footer' ] ),
            intval( $a[ 'width'    ] ),
            intval( $a[ 'height'   ] ),
            urlencode( $a[ 'footer_button_text' ] ),
            esc_attr( $a[ 'preventSave' ] ),
            $a[ 'text' ]
        );

        return apply_filters( 'groundhogg/html/modal_link', $html, $a );
    }

    /**
     * Wrapper function for the INPUT
     *
     * @param $args
     * @return string
     */
    public function range( $args=[] )
    {

        $a = wp_parse_args( $args, array(
            'type'  => 'range',
            'name'  => '',
            'id'    => '',
            'class' => 'slider',
            'value' => '',
            'attributes' => '',
            'placeholder' => '',
            'min'       => 0,
            'max'       => 99999,
            'step'      => 1
        ) );

	    return apply_filters( 'groundhogg/html/range', $this->input( $a ), $a );
    }

    /**
     * Output a simple textarea field
     *
     * @param $args
     * @return string
     */
    public function textarea( $args=[] )
    {
        $a = wp_parse_args( $args, array(
            'name'  => '',
            'id'    => '',
            'class' => '',
            'value' => '',
            'cols'  => '100',
            'rows'  => '7',
            'placeholder'   => '',
        ) );

        $value = $a[ 'value' ];
        unset( $a[ 'value' ] );

        $html = $this->wrap( esc_html( $value ), 'textarea', $a );

        return apply_filters( 'groundhogg/html/textarea', $html, $a );

    }

    /**
     * Output simple HTML for a dropdown field.
     *
     * @param $args
     * @return string
     */
    public function dropdown( $args=[] )
    {
        $a = wp_parse_args( $args, array(
            'name'              => '',
            'id'                => '',
            'class'             => '',
            'options'           => array(),
            'selected'          => '',
            'multiple'          => false,
            'option_none'       => 'Please Select One',
            'option_none_value' => '',
        ) );

        $a[ 'selected' ] = ensure_array( $a[ 'selected' ] );

        $optionHTML = '';

        if ( ! empty( $a[ 'option_none' ] ) ){
            $optionHTML .= sprintf( "<option value='%s'>%s</option>",
                esc_attr( $a[ 'option_none_value' ] ),
                sanitize_text_field( $a[ 'option_none' ] )
            );
        }

        if ( is_array( get_array_var( $a, 'options' ) ) ) {

            $options = $a[ 'options' ];

            foreach ( $options as $value => $name ){

                /* Include optgroup support */
                if ( is_array( $name ) ){

                    /* Redefine */
                    $inner_options = $name;
                    $label = $value;

                    $optionHTML .= sprintf( "<optgroup label='%s'>", $label );

                    foreach ( $inner_options as $inner_value => $inner_name ){

                        $selected = ( in_array( $inner_value, $a[ 'selected' ] ) ) ? 'selected' : '';

                        $optionHTML .= sprintf(
                            "<option value='%s' %s>%s</option>",
                            esc_attr( $inner_value ),
                            $selected,
                            esc_html( $inner_name )
                        );
                    }

                    $optionHTML .= "</optgroup>";

                } else {
                    $selected = ( in_array( $value, $a[ 'selected' ] ) ) ? 'selected' : '';

                    $optionHTML .= sprintf(
                        "<option value='%s' %s>%s</option>",
                        esc_attr( $value ),
                        $selected,
                        esc_html( $name )
                    );
                }

            }

        }

        if ( ! $a[ 'multiple' ] ){
            unset( $a[ 'multiple' ] );
        }

        unset( $a[ 'option_none' ] );
        unset( $a[ 'attributes' ] );
        unset( $a[ 'option_none_value' ] );
        unset( $a[ 'selected' ] );
        unset( $a[ 'options' ] );

        $html = $this->wrap( $optionHTML, 'select', $a );

	    return apply_filters( 'groundhogg/html/select', $html, $a );

    }

    /**
     * Provide a dropdown for possible contact owners.
     * Includes all ADMINs, MARKETERS, and SALES MANAGERs
     *
     * @param $args
     * @return string
     */
    public function dropdown_owners( $args=[] )
    {

        $a = wp_parse_args( $args, array(
            'name'              => 'owner_id',
            'id'                => 'owner_id',
            'class'             => 'gh-owners',
            'options'           => array(),
            'selected'          => '',
            'multiple'          => false,
            'option_none'       => 'Please Select an Owner',
            'option_none_value' => 0,
        ) );

        if ( empty( $a[ 'options' ] ) ){
            $owners = get_users( array( 'role__in' => array( 'administrator', 'marketer', 'sales_manager' ) ) );

            /**
             * @var $owner \WP_User
             */
            foreach ( $owners as $owner ){
                $a[ 'options' ][ $owner->ID ] = sprintf( '%s (%s)', $owner->display_name, $owner->user_email );
            }
        }

	    return apply_filters( 'groundhogg/html/dropdown_owners', $this->dropdown( $a ), $a );
    }

    /**
     * @param array $args
     *
     * @return string
     */
    public function round_robin( $args=[] )
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
             * @var $owner \WP_User
             */
            foreach ( $owners as $owner ){
                $a[ 'data' ][ $owner->ID ] = sprintf( '%s (%s)', $owner->display_name, $owner->user_email );
            }

        }

	    return apply_filters( 'groundhogg/html/round_robin', $this->select2( $a ), $a );
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
    public function select2( $args=[] )
    {
        $a = wp_parse_args( $args, array(
            'name'              => '',
            'id'                => '',
            'class'             => 'gh-select2',
            'data'              => [],
            'options'           => [],
            'selected'          => [],
            'multiple'          => false,
            'placeholder'       => 'Please Select One',
            'tags'              => false,
            'style'             => [ 'min-width' => '400px' ]
        ) );

        if ( isset_not_empty( $a, 'data' ) ){
            $a[ 'options' ] = $a[ 'data' ];
        }

        unset( $a[ 'data' ] );

        if ( isset_not_empty( $a, 'placeholder' ) ) {
            $a['data-placeholder'] = $a['placeholder'];
        }

        unset( $a[ 'placeholder' ] );

        if ( isset_not_empty( $a,'tags' ) ){
            $a[ 'data-tags' ] = $a[ 'tags' ];
        }

        unset( $a[ 'tags' ] );

        $html = $this->dropdown( $a );

        wp_enqueue_style( 'select2' );
        wp_enqueue_script( 'select2' );
        wp_enqueue_style( 'groundhogg-admin' );
        wp_enqueue_script( 'groundhogg-admin' );

        return apply_filters( 'groundhogg/html/select2', $html, $a );

    }

	/**
     * Return the HTML for a tag picker
     *
     * @param $args
     * @return string
     */
    public function tag_picker( $args=[] )
    {
        $a = wp_parse_args( $args, array(
            'name'              => 'tags[]',
            'id'                => 'tags',
            'class'             => 'gh-tag-picker',
            'data'              => array(),
            'selected'          => array(),
            'multiple'          => true,
            'placeholder'       => __( 'Please Select a Tag', 'groundhogg' ),
            'tags'              => true,
        ) );

        $a[ 'selected' ] = wp_parse_id_list( $a[ 'selected' ] );

        if ( is_array( $a[ 'selected' ] ) ){

            foreach ( $a[ 'selected' ] as $tag_id ){

                if ( Plugin::$instance->dbs->get_db('tags')->exists( $tag_id ) ){

                    $tag = Plugin::$instance->dbs->get_db('tags')->get( $tag_id );

                    $a[ 'data' ][ $tag_id ] = sprintf( "%s (%s)", $tag->tag_name, $tag->contact_count );

                }

            }
        }


        return apply_filters( 'groundhogg/html/tag_picker', $this->select2( $a ), $a );
    }


	/**
     * Output a simple Jquery UI date picker
     *
     * @param $args
     * @return string HTML
     */
    public function date_picker( $args=[] )
    {
        $a = wp_parse_args( $args, array(
            'name'  => '',
            'id'    => uniqid( 'date-' ),
            'class' => 'regular-text',
            'value' => '',
            'attributes' => '',
            'placeholder' => 'yyy-mm-dd',
            'min-date' => date( 'Y-m-d', strtotime( 'today' ) ),
            'max-date' => date( 'Y-m-d', strtotime( '+100 years' ) ),
            'format' => 'yy-mm-dd'
        ) );

        $html = sprintf(
            "<input type='text' id='%s' class='%s' name='%s' value='%s' placeholder='%s' autocomplete='off' %s><script>jQuery(function($){\$('#%s').datepicker({changeMonth: true,changeYear: true,minDate: '%s', maxDate: '%s',dateFormat:'%s'})});</script>",
            esc_attr( $a[ 'id'      ] ),
            esc_attr( $a[ 'class'   ] ),
            esc_attr( $a[ 'name'    ] ),
            esc_attr( $a[ 'value'   ] ),
            esc_attr( $a[ 'placeholder' ] ),
            $a[ 'attributes'  ],
            esc_attr( $a[ 'id'      ] ),
            esc_attr( $a[ 'min-date' ] ),
            esc_attr( $a[ 'max-date' ] ),
            esc_attr( $a[ 'format' ] )
        );

        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_style( 'jquery-ui' );

        return apply_filters( 'groundhogg/html/date_picker', $html, $a );
    }

	/**
     * Return the HTML of a dropdown for contacts
     *
     * @param $args
     * @return string
     */
    public function dropdown_contacts( $args=[] )
    {
        $a = wp_parse_args( $args, array(
            'name'              => 'contact_id',
            'id'                => 'contact_id',
            'class'             => 'gh-contact-picker',
            'data'              => array(),
            'selected'          => array(),
            'multiple'          => false,
            'placeholder'       => __( 'Please select a contact', 'groundhogg' ),
            'tags'              => false,
        ) );

        foreach ( $a[ 'selected' ] as $contact_id ){

            $contact = get_contactdata( $contact_id );

            if ( $contact->exists() ) {

                $a[ 'data' ][ $contact_id ] = sprintf( "%s %s (%s)", $contact->first_name, $contact->last_name, $contact->email );

            }

        }


        return apply_filters( 'groundhogg/html/dropdown_contacts', $this->select2( $a ), $a );
    }

	/**
     * Return the html for an email picker
     *
     * @param $args
     * @return string
     */
    public function dropdown_emails( $args=[] )
    {
        $a = wp_parse_args( $args, array(
            'name'              => 'email_id',
            'id'                => 'email_id',
            'class'             => 'gh-email-picker',
            'data'              => array(),
            'selected'          => array(),
            'multiple'          => false,
            'placeholder'       => __( 'Please select an email', 'groundhogg' ),
            'tags'              => false,
        ) );

        $a[ 'selected' ] = wp_parse_id_list( $a[ 'selected' ] );

        foreach ( $a[ 'selected' ] as $email_id ){
            if ( get_db( 'emails' )->exists( $email_id ) ){
                $email = get_db( 'emails' )->get( $email_id );
                $a[ 'data' ][ $email_id ] = $email->subject . ' (' . $email->status . ')';

            }
        }

	    return apply_filters( 'groundhogg/html/dropdown_emails', $this->select2( $a ), $a );
    }


    /**
	 * Return the html for an email picker
	 *
	 * @param $args
	 * @return string
	 */
	public function dropdown_sms( $args=[] )
	{
		$a = wp_parse_args( $args, array(
			'name'              => 'sms_id',
			'id'                => 'sms_id',
			'class'             => 'gh-sms-picker',
			'data'              => [],
			'selected'          => [],
			'multiple'          => false,
			'placeholder'       => __( 'Please select an SMS', 'groundhogg' ),
			'tags'              => false,
		) );

		foreach ( $a[ 'selected' ] as $sms_id ){
			if ( Plugin::$instance->dbs->get_db( 'sms' )->exists( $sms_id ) ){
				$sms = Plugin::$instance->dbs->get_db( 'sms' )->get( $sms_id );
				$a[ 'data' ][ $sms_id ] = $sms->title;
			}
		}

		return $this->select2( $a );
	}

    /**
	 * Returns a picker for benchmarks.
	 * Included in core so that we don't need to include it in every extension we write.
	 *
	 * @param $args
	 *
	 * @return string
	 */
	public function benchmark_picker( $args=[] )
	{

		$a = wp_parse_args( $args, array(
			'name'              => 'benchmarks[]',
			'id'                => 'benchmarks',
			'class'             => 'gh-benchmark-picker',
			'data'              => [],
			'selected'          => [],
			'multiple'          => true,
			'placeholder'       => __( 'Please select one or more benchmarks', 'groundhogg' ),
			'tags'              => false,
		) );

		foreach ( $a[ 'selected' ] as $benchmark_id ){

		    $step = Plugin::$instance->utils->get_step( $benchmark_id );

			if ( $step ){
                $funnel_name = $step->get_funnel_title();
                $a[ 'data' ][ $funnel_name ][ $step->ID ] = sprintf( "%d. %s (%s)", $step->get_order(), $step->get_title(), str_replace( '_', ' ', $step->get_type() ) );
			}

		}

		return $this->select2( $a );
	}

    /**
     * @param $args
     * @return string
     */
	public function step_picker( $args )
    {
        $steps = Plugin::$instance->dbs->get_db('steps' )->query( [], 'step_order' );
        $options = array();
        foreach ($steps as $step) {
            $step =Plugin::$instance->utils->get_step( $step->ID );
            if ( $step && $step->is_active()) {

                $funnel_name = $step->get_funnel()->get_title();
                $options[$funnel_name][$step->ID] = sprintf("%d. %s (%s)", $step->get_order(), $step->get_title(), str_replace('_', ' ', $step->get_type()));
            }
        }

        $a = wp_parse_args( $args, array(
            'name'              => 'steps[]',
            'id'                => 'steps',
            'class'             => 'gh-step-picker gh-select2',
            'selected'          => [],
            'options'           => $options,
            'multiple'          => true,
            'placeholder'       => __( 'Please select one or more steps.', 'groundhogg' ),
            'tags'              => false,
        ) );

        return $this->select2( $a );
    }

    /**
     * Get a meta key picker. useful for searching.
     *
     * @param array $args
     * @return string
     */
	public function meta_key_picker( $args=[] ){
        $a = wp_parse_args( $args, array(
            'name'              => 'key',
            'id'                => 'key',
            'class'             => 'gh-metakey-picker',
            'data'              => array(),
            'selected'          => array(),
            'multiple'          => false,
            'placeholder'       => __( 'Please select 1 or more meta keys', 'groundhogg' ),
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
    public function color_picker( $args=[] )
    {
        $a = wp_parse_args( $args, array(
            'name'      => '',
            'id'        => '',
            'value'     => '',
            'default'   => ''
        ) );

        $html = sprintf(
            "<input type=\"text\" id=\"%s\" name=\"%s\" class=\"wpgh-color\" value=\"%s\" data-default-color=\"%s\" />",
            esc_attr( $a[ 'id'      ] ),
            esc_attr( $a[ 'name'    ] ),
            esc_attr( $a[ 'value'   ] ),
            esc_attr( $a[ 'default' ] )
        );



        return apply_filters( 'groundhogg/html/color_picker', $html, $args );
    }

    /**
     * This is for use withing the email editor.
     *
     * @param $args
     * @return string
     */
    public function font_picker( $args=[] )
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

        unset( $a[ 'fonts' ] );

        return apply_filters( 'groundhogg/html/font_picker', $this->dropdown( $a ), $a );

    }

    /**
     * Image picker, maimly for use by the email editor
     *
     * @param $args
     * @return string
     */
    public function image_picker( $args=[] )
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
        wp_enqueue_style('groundhogg-admin' );
        wp_enqueue_script('groundhogg-admin-media-picker' );

        return apply_filters( 'groundhogg/html/image_picker', $html, $a );
    }

    /**
     * Autocomplete link picker
     *
     * @param $args
     * @return string
     */
    public function link_picker( $args=[] )
    {
        $a = wp_parse_args( $args, array(
            'type'  => 'text',
            'name'  => '',
            'id'    => '',
            'class' => 'regular-text',
            'value' => '',
            'attributes' => '',
            'placeholder' => __( 'Start typing...', 'groundhogg' ),
            'autocomplete' => false,
            'required' => false
        ) );

        if ( $a[ 'required' ] ){
            $a[ 'required' ] = 'required';
        }

        $a[ 'autocomplete' ] = $a[ 'autocomplete' ] ? 'on' : 'off';

        $html = sprintf(
            "<input type='%s' id='%s' class='%s gh-link-picker' name='%s' value='%s' placeholder='%s' autocomplete='%s' %s %s>",
            esc_attr( $a[ 'type'    ] ),
            esc_attr( $a[ 'id'      ] ),
            esc_attr( $a[ 'class'   ] ),
            esc_attr( $a[ 'name'    ] ),
            esc_attr( $a[ 'value'   ] ),
            esc_attr( $a[ 'placeholder' ] ),
            esc_attr( $a[ 'autocomplete' ] ),
            $a[ 'attributes'  ],
            $a[ 'required'  ]
        );

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-autocomplete' );
        wp_enqueue_style( 'groundhogg-admin' );
        wp_enqueue_script( 'groundhogg-admin' );

        return apply_filters( 'groundhogg/html/link_picker', $html, $args );
    }

    /**
	 * Output a progress bar.
	 *
	 * @param $args
	 *
	 * @return string
	 */
    public function progress_bar( $args=[] )
    {
	    $a = wp_parse_args( $args, array(
		    'id'        => '',
		    'class'     => '',
		    'hidden'    => false,
	    ) );

	    $hidden = ( $a[ 'hidden' ] ) ? 'hidden' : '';

	    $bar = sprintf( "<div id='%s-wrap' class=\"progress-bar-wrap %s %s\">
	            <div id='%s' class=\"progress-bar\">
	            <span id='%s-percentage' style='visibility: visible;float: none;padding-left: 30px;opacity: 1;' class=\"progress-percentage spinner\">0%%</span>
	            </div>
			</div>",
		    esc_attr( $a[ 'id' ] ),
		    esc_attr( $a[ 'class' ] ),
		    $hidden,
		    esc_attr( $a[ 'id' ] ),
		    esc_attr( $a[ 'id' ] )
	    );

        wp_enqueue_style( 'groundhogg-admin' );

        return apply_filters( 'groundhogg/html/progress_bar', $bar, $a );
    }

    /**
	 * Output a styled toggle switch.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function toggle( $args=[] )
    {
        $a = shortcode_atts( array(
            'name'          => '',
            'id'            => '',
            'class'         => '',
            'value'         => '1',
            'checked'       => false,
            'title'         => '',
            'attributes'    => '',
            'on'            => 'On',
            'off'           => 'Off',
        ), $args );

        $css = sprintf( "<style>#%s-switch .onoffswitch-inner:before {content: \"%s\";}
#%s-switch .onoffswitch-inner:after {content: \"%s\";}</style>",esc_attr( $a[ 'id' ] ), esc_attr( $a[ 'on' ] ), esc_attr( $a[ 'id' ] ), esc_attr( $a[ 'off' ] ) );

        wp_enqueue_style( 'groundhogg-admin' );

        $html = sprintf("%s<div id=\"%s-switch\" class=\"onoffswitch %s\" style=\"text-align: left\">
                        <input type=\"checkbox\" id=\"%s\" name=\"%s\" class=\"onoffswitch-checkbox %s\" value=\"%s\" %s>
                        <label class=\"onoffswitch-label\" for=\"%s\">
                            <span class=\"onoffswitch-inner\"></span>
                            <span class=\"onoffswitch-switch\"></span>
                        </label>
                    </div>",
            $css,
            esc_attr( $a[ 'id' ] ),
            esc_attr( $a[ 'class' ] ),
            esc_attr( $a[ 'id' ] ),
            esc_attr( $a[ 'name' ] ),
            esc_attr( $a[ 'class' ] ),
            esc_attr( $a[ 'value' ] ),
            $a[ 'checked' ] ? 'checked' : '',
            esc_attr( $a[ 'id' ] )
        );

	    return apply_filters( 'groundhogg/html/toggle', $html, $a );
    }

    /**
     * Send a json response in the format for a select2 picker
     *
     * @param array $data
     */
    public function send_picker_response( $data = [] )
    {
        $results = [ 'results' => $data, 'more' => false ];
        wp_send_json( $results );
    }

    /**
	 * Get json tag results for tag picker
	 */
	public function ajax_get_tags()
	{
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_tags' ) )
            wp_send_json_error();

		$value = isset( $_REQUEST[ 'q' ] ) ? sanitize_text_field( $_REQUEST[ 'q' ] ) : '';

        $tags = Plugin::$instance->dbs->get_db( 'tags' )->search( $value );

		$json = [];

		foreach ( $tags as $i => $tag ) {

			$json[] = array(
				'id' => $tag->tag_id,
				'text' => sprintf( "%s (%s)", $tag->tag_name, $tag->contact_count )
			);

		}

		$this->send_picker_response( $json );
	}

    /**
	 * Get json contact results for contact picker
	 */
	public function ajax_get_contacts()
	{
		if ( ! is_user_logged_in() || ! current_user_can( 'view_contacts' ) )
            wp_send_json_error();

		$value = isset( $_REQUEST[ 'q' ] )? sanitize_text_field( $_REQUEST[ 'q' ] ) : '';

		$contacts = Plugin::$instance->dbs->get_db( 'contacts' )->search( $value );

		$json = array();

		foreach ( $contacts as $i => $contact ) {

			$json[] = array(
				'id' => $contact->ID,
				'text' => sprintf( "%s %s (%s)", $contact->first_name, $contact->last_name, $contact->email )
			);

		}

        $this->send_picker_response( $json );
    }

    /**
	 * Get json email results for email picker
	 */
	public function ajax_get_emails()
	{
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_emails' ) )
            wp_send_json_error();

        $value = isset( $_REQUEST[ 'q' ] )? sanitize_text_field( $_REQUEST[ 'q' ] ) : '';
        $data = Plugin::$instance->dbs->get_db( 'emails' )->search( $value );

		$json = [];

		foreach ( $data as $i => $email ) {

			$json[] = array(
				'id' => $email->ID,
				'text' => $email->subject . ' (' . $email->status . ')'
			);

		}

        $this->send_picker_response( $json );
	}

    /**
     * Get json email results for email picker
     */
    public function ajax_get_sms()
    {
        if ( ! is_user_logged_in() || ! current_user_can( 'edit_sms' ) )
            wp_send_json_error();

        $value = isset( $_REQUEST[ 'q' ] )? sanitize_text_field( $_REQUEST[ 'q' ] ) : '';
        $data = Plugin::$instance->dbs->get_db( 'sms' )->search( $value );

        $json = array();

        foreach ( $data as $i => $sms ) {

            $json[] = array(
                'id' => $sms->ID,
                'text' => $sms->title
            );

        }

        $this->send_picker_response( $json );
    }

    /**
     * Returns a select 2 compatible json object with contact data meta keys
     */
    public function ajax_get_meta_keys()
    {
        if ( ! is_user_logged_in() || ! current_user_can( 'view_contacts' ) )
            wp_send_json_error();

        $json = [];

        $data = Plugin::$instance->dbs->get_db( 'contactmeta' )->get_keys();

        foreach ( $data as $i => $key ) {

            $json[] = array(
                'id' => $key,
                'text' => $key
            );

        }

        $this->send_picker_response( $json );
    }

    /**
     * Get json email results for email picker
     */
    public function ajax_get_benchmarks()
    {

        if ( ! is_user_logged_in() || ! current_user_can( 'edit_funnels' ) )
            wp_send_json_error();

        $value = isset( $_REQUEST[ 'q' ] )? sanitize_text_field( $_REQUEST[ 'q' ] ) : '';
        $data = Plugin::$instance->dbs->get_db( 'steps' )->search( $value );

        $json = array();

        foreach ( $data as $i => $step ) {

            $step = Plugin::$instance->utils->get_step( absint( $step->ID ) );

            if ( $step->is_active() ){

                $funnel_name = $step->get_funnel_title();

                if ( isset( $json[ $funnel_name ] ) ){
                    $json[ $funnel_name ][ 'children' ][] = [
                        'text' => sprintf( '%d. %s (%s)', $step->get_order(), $step->get_title(), str_replace( '_', ' ', $step->get_type() ) ),
                        'id'   => $step->ID
                    ];
                } else {
                    $json[ $funnel_name ] = array(
                        'text' => $funnel_name,
                        'children' => [
                            [
                                'text' => sprintf( '%d. %s (%s)', $step->get_order(), $step->get_title(), str_replace( '_', ' ', $step->get_type() ) ),
                                'id'   => $step->ID
                            ]
                        ]
                    );
                }

            }

        }

        $this->send_picker_response( $json );
    }

}