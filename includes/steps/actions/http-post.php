<?php
namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
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
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'HTTP Post', 'action_name', 'groundhogg' );
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
        return _x( 'Send an HTTP Post to your favorite external software.', 'element_description', 'groundhogg' );
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

        <table class="form-table" id="meta-table-<?php echo $step->get_id() ; ?>">
            <tbody>
            <tr>
                <td>
                    <strong><?php _e( 'Post Url:', 'groundhogg' ); ?></strong>
                </td>
                <td colspan="2">
                    <?php $args = array(
                        'type'  => 'url',
                        'name'  => $this->setting_name_prefix( 'post_url' ),
                        'id'    => $this->setting_id_prefix( 'post_url' ),
                        'value' => $post_url
                    );

                    echo $html->input( $args ); ?>
                </td>
            </tr>
            <?php foreach ( $post_keys as $i => $post_key): ?>
                <tr>
                    <td>
                        <label><strong><?php _e( 'Key: ' ); ?></strong>

                            <?php $args = array(
                                'name'  => $this->setting_name_prefix( 'post_keys' ) . '[]',
                                'class' => 'input',
                                'value' => sanitize_key( $post_key )
                            );

                            echo $html->input( $args ); ?>

                        </label>
                    </td>
                    <td>
                        <label><strong><?php _e( 'Value: ' ); ?></strong> <?php $args = array(
                                'name'  => $step->prefix( 'post_values' ) . '[]',
                                'class' => 'input',
                                'value' => esc_html( $post_values[$i] )
                            );

                            echo $html->input( $args ); ?></label>
                    </td>
                    <td>
                    <span class="row-actions">
                        <span class="add"><a style="text-decoration: none" href="javascript:void(0)" class="addmeta"><span class="dashicons dashicons-plus"></span></a></span> |
                        <span class="delete"><a style="text-decoration: none" href="javascript:void(0)" class="deletemeta"><span class="dashicons dashicons-trash"></span></a></span>
                    </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p>
            <?php Plugin::$instance->replacements->show_replacements_button(); ?>
        </p>
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
     * @return bool|object
     */
    public function run( $contact, $event )
    {

        $post_keys   = $this->get_setting( 'post_keys' );
        $post_values = $this->get_setting( 'post_values' );

        if ( ! is_array( $post_keys ) || ! is_array( $post_values ) || empty( $post_keys ) || empty( $post_values ) ){
            return false;
        }

        $post_array = array();

        foreach ( $post_keys as $i => $key )
        {
            if ( ! empty( $key ) ){
                $post_array[ sanitize_key( $key ) ] = Plugin::$instance->replacements->process( sanitize_text_field( $post_values[ $i ] ), $contact->get_id() );
            }
        }

        $post_url = $this->get_setting('post_url' );
        $post_url = Plugin::$instance->replacements->process( esc_url_raw( $post_url ), $contact->get_id() );

        $response = wp_remote_post( $post_url, array(
            'body' => $post_array
        ) );

        if ( is_wp_error( $response ) ) {
            $contact->add_note( $response->get_error_message() );
        }

        return $response;

    }
}