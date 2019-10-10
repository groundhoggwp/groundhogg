<?php
namespace Groundhogg;


/**
 * SendWP Connect.
 *
 * @package  Groundhogg
 *
 * @since 3.36.1
 * @version 3.36.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_SendWP class..
 *
 * @since 3.36.1
 */
class SendWp {
    
    const PARTNER_ID = 2265;

    /**
     * Constructor.
     *
     * @since 3.36.1
     *
     * @return void
     */
    public function __construct() {

        /**
         * Disable the SendWP Connector class and settings
         *
         * @since 3.36.1
         *
         * @param bool $disabled Whether or not this class is disabled.
         */
        if ( apply_filters( 'gorundhogg/disable_sendwp', false ) ) {
            return;
        }

        add_action( 'wp_ajax_groundhogg_sendwp_remote_install', array( $this, 'ajax_callback_remote_install' ) );
    }


    /**
     * Ajax callback for installing SendWP Plugin.
     *
     * @since 3.36.1
     *
     * @hook wp_ajax_groundhogg_sendwp_remote_install
     *
     * @return void
     */
    public function ajax_callback_remote_install() {

        $ret = $this->do_remote_install();
        ob_clean();
        wp_send_json( $ret, ! empty( $ret['status'] ) ? $ret['status'] : 200 );

    }

    /**
     * Remote installation method.
     *
     * @since 3.36.1
     *
     * @return array
     */
    public function do_remote_install() {

        if ( ! current_user_can( 'install_plugins' ) ) {
            return array(
                'code'    => 'sendwp_install_unauthorized',
                'message' => __( 'You do not have permission to perform this action.', 'groundhogg' ),
                'status'  => 403,
            );
        }

        $install = $this->install();

        if ( is_wp_error( $install ) ) {
            return array(
                'code'    => $install->get_error_code(),
                'message' => $install->get_error_message(),
                'status'  => 400,
            );
        }

        if ( ! function_exists( 'sendwp_get_client_redirect' ) ){
            return array(
                'code'    => 'function_not_defined',
                'message' => 'Missing functions.',
                'status'  => 400,
            );
        }

        $redirect = guided_setup_finished() ? sendwp_get_client_redirect() : admin_page_url( 'gh_guided_setup', [ 'step' => '6' ] );

        return array(
            'partner_id'      => self::PARTNER_ID,
            'register_url'    => sendwp_get_server_url() . '_/signup',
            'client_name'     => sendwp_get_client_name(),
            'client_secret'   => sendwp_get_client_secret(),
            'client_redirect' => $redirect,
        );

    }

    /**
     * Install / Activate SendWP plugin.
     *
     * @since 3.36.1
     *
     * @return \WP_Error|true
     */
    private function install() {

        $is_sendwp_installed = false;
        foreach ( get_plugins() as $path => $details ) {
            if ( false === strpos( $path, '/sendwp.php' ) ) {
                continue;
            }
            $is_sendwp_installed = true;
            $activate            = activate_plugin( $path );
            if ( is_wp_error( $activate ) ) {
                return $activate;
            }
            break;
        }

        $install = null;
        if ( ! $is_sendwp_installed ) {

            include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            include_once ABSPATH . 'wp-admin/includes/file.php';
            include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

            // Use the WordPress Plugins API to get the plugin download link.
            $api = plugins_api(
                'plugin_information',
                array(
                    'slug' => 'sendwp',
                )
            );
            if ( is_wp_error( $api ) ) {
                return $api;
            }

            // Use the AJAX upgrader skin to quietly install the plugin.
            $upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
            $install  = $upgrader->install( $api->download_link );
            if ( is_wp_error( $install ) ) {
                return $install;
            }

            $activate = activate_plugin( $upgrader->plugin_info() );
            if ( is_wp_error( $activate ) ) {
                return $activate;
            }
        }

        // Final check to see if SendWP is available.
        if ( ! function_exists( 'sendwp_get_server_url' ) ) {
            return new \WP_Error( 'sendwp_not_found', __( 'SendWP Plugin not found. Please try again.', 'groundhogg' ), $install );
        }

        return true;

    }

    /**
     * Get the "Connect" Setting field html.
     */
    public function output_connect_button() {

        if ( function_exists( 'sendwp_client_connected' ) && sendwp_client_connected() ) {

            $ret = array(
                __( 'Your site is connected to SendWP.', 'groundhogg' ),
            );

            if ( function_exists( 'sendwp_forwarding_enabled' ) && sendwp_forwarding_enabled() ) {
                $ret[] = sprintf(
                // Translators: %1$s = Opening anchor tag; %2$s = Closing anchor tag.
                    __( '%1$sManage your account%2$s.', 'groundhogg' ),
                    '<a href="https://sendwp.com/account/" target="_blank" rel="noopener noreferrer">',
                    '</a>'
                );
            } else {
                $ret[] = sprintf(
                // Translators: %1$s = Opening anchor tag; %2$s = Closing anchor tag.
                    '<em>' . __( 'Email sending is currently disabled. %1$sVisit the SendWP Settings%2$s to enable sending..', 'groundhogg' ) . '</em>',
                    '<a href="' . admin_url( '/tools.php?page=sendwp' ) . '">',
                    '</a>'
                );
            }

            echo '<p>' . implode( ' ', $ret ) . '</p>';

            return;

        }

        echo sprintf( '<button type="button" class="button button-primary" id="groundhogg-sendwp-connect">%s %s</button>',  dashicon( 'email-alt' ), __( 'Connect SendWP' ) );
    }

    /**
     * Output some quick and dirty inline CSS.
     *
     * @since 3.36.1
     *
     * @return void
     */
    public function output_css() {
        ?>
        <style type="text/css">
            #groundhogg-sendwp-connect {
                font-size: 16px;
                height: auto;
                margin: 0 0 6px;
                padding: 8px 14px;
            }
            #groundhogg-sendwp-connect .fa {
                margin-right: 4px;
            }
            #groundhogg-sendwp-connect .dashicons{
                /*vertical-align: middle;*/
                font-size: 26px;
                margin-right: 10px;
            }
        </style>
        <?php
    }

    /**
     * Settings UI output.
     */
    public function settings_connect_ui(){

        $this->output_css();
        $this->output_connect_button();
        $this->output_js();

        ?><p class="description"><?php _e( 'Never worry about sending email again! <a href="https://sendwp.com/" target="_blank">SendWP</a> takes care of everything for you for just <b>$9/month!</b>', 'groundhogg' ); ?></p><?php
    }

    /**
     * Output some quick and dirty inline JS.
     *
     * @since 3.36.1
     *
     * @return void
     */
    public function output_js() {
        ?>
        <script>
            var btn = document.getElementById( 'groundhogg-sendwp-connect' );
            btn.addEventListener( 'click', function( e ) {
                e.preventDefault();
                groundhogg_sendwp_remote_install();
            } );

            /**
             * Perform AJAX request to install SendWP plugin.
             *
             * @since 3.36.1
             *
             * @return void
             */
            function groundhogg_sendwp_remote_install() {
                var data = {
                    'action': 'groundhogg_sendwp_remote_install',
                };

                jQuery.post( ajaxurl, data, function( res ) {
                    groundhogg_sendwp_register_client( res.register_url, res.client_name, res.client_secret, res.client_redirect, res.partner_id );
                } ).fail( function( jqxhr ) {
                    if ( jqxhr.responseJSON && jqxhr.responseJSON.message ) {
                        alert( 'Error: ' + jqxhr.responseJSON.message );
                        console.log( jqxhr );
                    }
                } );
            }

            /**
             * Register client with SendWP.
             *
             * @since 3.36.1
             *
             * @param {string} register_url Registration URL.
             * @param {string} client_name Client name.
             * @param {string} client_secret Client secret.
             * @param {string} client_redirect Client redirect URL.
             * @param {int} partner_id SendWP partner ID.
             * @return {void}
             */
            function groundhogg_sendwp_register_client( register_url, client_name, client_secret, client_redirect, partner_id ) {

                var form = document.createElement( 'form' );
                form.setAttribute( 'method', 'POST' );
                form.setAttribute( 'action', register_url );

                function groundhogg_sendwp_append_form_input( name, value ) {
                    var input = document.createElement( 'input' );
                    input.setAttribute( 'type', 'hidden' );
                    input.setAttribute( 'name', name );
                    input.setAttribute( 'value', value );
                    form.appendChild( input );
                }

                groundhogg_sendwp_append_form_input( 'client_name', client_name );
                groundhogg_sendwp_append_form_input( 'client_secret', client_secret );
                groundhogg_sendwp_append_form_input( 'client_redirect', client_redirect );
                groundhogg_sendwp_append_form_input( 'partner_id', partner_id );

                document.body.appendChild( form );
                form.submit();

            }
        </script>
        <?php

    }

    /**
     * @var SendWp;
     */
    public static $instance;

    /**
     * Instance.
     *
     * Ensures only one instance of the plugin class is loaded or can be loaded.
     *
     * @since 1.0.0
     * @access public
     * @static
     *
     * @return SendWp An instance of the class.
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Clone.
     *
     * Disable class cloning and throw an error on object clone.
     *
     * The whole idea of the singleton design pattern is that there is a single
     * object. Therefore, we don't want the object to be cloned.
     *
     * @access public
     * @since 1.0.0
     */
    public function __clone() {
        // Cloning instances of the class is forbidden.
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'groundhogg' ), '2.0.0' );
    }

    /**
     * Wakeup.
     *
     * Disable unserializing of the class.
     *
     * @access public
     * @since 1.0.0
     */
    public function __wakeup() {
        // Unserializing instances of the class is forbidden.
        _doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'groundhogg' ), '2.0.0' );
    }

}