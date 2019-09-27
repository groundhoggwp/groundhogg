<?php
namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HTTP Post
 *
 * This allows the user send an http post with contact information to any specified URL.
 * The URL must be HTTPS
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
class HTTP_Post extends Action
{

    /**
     * @return string
     */
    public function get_help_article()
    {
        return 'https://docs.groundhogg.io/docs/builder/actions/http-post/';
    }

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Webhook', 'step_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'http_post';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Send an HTTP Post to your favorite external software.', 'step_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/http-post.png';
    }
    
    /**
     * Display the settings
     *
     * @param $step Step
     */
    public function settings( $step )
    {
        $post_keys      = $step->get_meta( 'post_keys' );
        $post_values    = $step->get_meta( 'post_values' );
        $post_url       = esc_url_raw( $step->get_meta( 'post_url' ) );

        if ( ! is_array( $post_keys ) || ! is_array( $post_values ) ){
            $post_keys = array( '' ); //empty to show first option.
            $post_values = array( '' ); //empty to show first option.
        }

        $html = Plugin::$instance->utils->html;
        
        ?>

        <table class="form-table">
            <tbody>
            <tr>
                <td>
                    <strong><?php _e( 'Url:', 'groundhogg' ); ?></strong>
                </td>
                <td colspan="2">
                    <?php $args = array(
                        'type'  => 'url',
                        'name'  => $this->setting_name_prefix( 'post_url' ),
                        'id'    => $this->setting_id_prefix( 'post_url' ),
                        'value' => $post_url
                    );

                    echo $html->input( $args );

                    ?><span class="row-actions"><?php

                    echo $html->button( [
                        'type'      => 'button',
                        'text'      => __( 'Send Test' ),
                        'name'      => 'send_test',
                        'id'        => '',
                        'class'     => 'test-webhook button button-secondary',
                        'value'     => 'send',
                    ] );
                    ?>
                    </span>
                    <p>
                        <?php

                        echo $html->checkbox( [
                            'label'         => __( 'Send as JSON' ),
                            'type'          => 'checkbox',
                            'name'          => $this->setting_name_prefix( 'send_as_json' ),
                            'id'            => $this->setting_id_prefix( 'send_as_json' ),
                            'class'         => '',
                            'value'         => '1',
                            'checked'       => $this->get_setting( 'send_as_json' ),
                            'title'         => '',
                        ] );

                        ?>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>
        <?php

        $rows = [];

        foreach ( $post_keys as $i => $post_key ):

            $rows[] = [
                $html->input( [
                    'name'  => $this->setting_name_prefix( 'post_keys' ) . '[]',
                    'class' => 'input',
                    'value' => sanitize_key( $post_key )
                ] ),
                $html->input( [
                    'name'  => $this->setting_name_prefix( 'post_values' ) . '[]',
                    'class' => 'input',
                    'value' => esc_html( $post_values[$i] )
                ] ),
                "<span class=\"row-actions\">
                        <span class=\"add\"><a style=\"text-decoration: none\" href=\"javascript:void(0)\" class=\"addmeta\"><span class=\"dashicons dashicons-plus\"></span></a></span> |
                        <span class=\"delete\"><a style=\"text-decoration: none\" href=\"javascript:void(0)\" class=\"deletemeta\"><span class=\"dashicons dashicons-trash\"></span></a></span>
                    </span>"
            ];


        endforeach;

        $html->list_table( [ 'id' => 'meta-table-' . $step->get_id()  ], [ __( 'Key' ), __( 'Value' ), __( 'Actions' ) ], $rows, false );


        ?>
        <script>
            jQuery(function($){
                var table = $( "#meta-table-<?php echo $step->ID; ?>" );
                table.click(function ( e ){
                    var el = $(e.target);
                    if ( el.closest( '.addmeta' ).length ) {
                        el.closest('tr').last().clone().appendTo( el.closest('tr').parent() );
                        el.closest('tr').parent().children().last().find( ':input' ).val( '' );
                    } else if ( el.closest( '.deletemeta' ).length ) {
                        el.closest( 'tr' ).remove();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Save the settings
     *
     * @param $step Step
     */
    public function save( $step )
    {
        $this->save_setting( 'post_url', esc_url_raw( $this->get_posted_data( 'post_url' ) ) );
        $this->save_setting( 'send_as_json', absint( $this->get_posted_data( 'send_as_json' ) ) );

        $post_keys = $this->get_posted_data( 'post_keys', [] );

        if ( $post_keys ){
            $post_values = $this->get_posted_data( 'post_values', [] );

            if ( ! is_array( $post_keys ) )
                return;

            $post_keys = array_map( 'sanitize_key', $post_keys );
            $post_values = array_map( 'sanitize_text_field', wp_unslash( $post_values ) );

            $this->save_setting( 'post_keys', $post_keys );
            $this->save_setting( 'post_values', $post_values );
        }

    }
    /**
     * Process the http post step...
     *
     * @param $contact Contact
     * @param $event Event
     *
     * @return bool|object|array
     */
    public function run( $contact, $event )
    {

        $post_keys   = $this->get_setting( 'post_keys' );
        $post_values = $this->get_setting( 'post_values' );

        if ( ! is_array( $post_keys ) || ! is_array( $post_values ) || empty( $post_keys ) || empty( $post_values ) ){
            return false;
        }

        $data = array();

        foreach ( $post_keys as $i => $key )
        {
            if ( ! empty( $key ) ){
                $data[ sanitize_key( $key ) ] = Plugin::$instance->replacements->process( sanitize_text_field( $post_values[ $i ] ), $contact->get_id() );
            }
        }

        if ( empty( $data ) ){
            $data = $contact->get_as_array();
        }

        $post_url = $this->get_setting('post_url' );
        $post_url = Plugin::$instance->replacements->process( esc_url_raw( $post_url ), $contact->get_id() );


        $headers = [];

        if ( $this->get_setting( 'send_as_json' ) ){
            $headers[ 'Content-Type' ] = sprintf( 'application/json; charset=%s', get_bloginfo( 'charset' ) );
            $data = wp_json_encode( $data );
        }

        $args = apply_filters( 'groundhogg/steps/http_post/run/request_data', [
            'method'        => 'POST',
            'headers'       => $headers,
            'body'          => $data,
            'data_format'   => 'body',
            'sslverify'     => true
        ] );

        $response = wp_remote_post( $post_url, $args );

        if ( is_wp_error( $response ) ) {
            $contact->add_note( $response->get_error_message() );
        }

        return $response;

    }

    public function admin_scripts()
    {
        wp_enqueue_script( 'groundhogg-funnel-webhook' );
        wp_localize_script( 'groundhogg-funnel-webhook', 'WebhookStep', [
            'test' => 'Hello World',
        ] );
    }

    protected function add_additional_actions()
    {
        add_action( 'wp_ajax_groundhogg_test_webhook', [ $this, 'ajax_test' ] );
    }

    public function ajax_test()
    {
        if ( ! current_user_can( 'edit_funnels' ) ){
            wp_send_json_error();
        }

        $step_id = absint( get_request_var( 'step_id' ) );

        $step = new Step( $step_id );

        if ( ! $step->exists() ){
            wp_send_json_error( new \WP_Error( 'error', 'The provided step does not exist.' ) );
        }

        $this->set_current_step( $step );

        $contact = get_contactdata( wp_get_current_user()->user_email );

        if ( ! $contact ){
            wp_send_json_error();
        }

        $response = $this->run( $contact, new Event() );

        if ( ! $response ){
            wp_send_json_error( __( 'Something went wrong.' ) );
        }

        if ( is_wp_error( $response ) ){
            wp_send_json_error( $response );
        }

        $body = wp_remote_retrieve_body( $response );

        if ( json_decode( $body ) ){
            $body = json_decode( $body );
        }

        wp_send_json_success( $body );

        return;
    }
}