<?php
namespace Groundhogg;

/**
 * Notices
 *
 * Easy implementation for notices on admin pages in Groundhogg.
 * This class is used by all admin page classes, thus all notices will appear on any admin page.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Notices
{
    const TRANSIENT = 'groundhogg_notices';

    public function __construct()
    {
        add_action( 'admin_notices', [ $this, 'pre_notices' ] );
        add_action( 'admin_notices', [ $this, 'notices' ] );
    }

    public function pre_notices()
    {
        $this->add(  'features-notice', sprintf( "IMPORTANT! Several features were removed in from Groundhogg in version 2.1. Please go here to <a class='button-primary' href='%s'>re-install features!</a> <a class='button' href='%s'>Dismiss</a>", admin_page_url( 'gh_tools', [ 'tab' => 'remote_install' ] ), action_url( 'dismiss_notice' ) ), 'warning', 'administrator', true );
    }

    /**
	 * @return bool|string
	 */
    protected function get_transient_name()
    {
        if ( is_user_logged_in() ){
            return self::TRANSIENT . '_user_' . get_current_user_id();
        }

        if ( Plugin::$instance->tracking->get_current_contact() ){
	        return self::TRANSIENT . '_contact_' . Plugin::$instance->tracking->get_current_contact_id();
        }

        return false;
    }

	/**
	 * @return bool
	 */
    protected function can_add_notices()
    {
        return (bool) $this->get_transient_name();
    }

	/**
	 * @return mixed
	 */
    protected function get_stored_notices()
    {
        return get_transient( $this->get_transient_name() );
    }

	/**
	 * @param array $notices
	 *
	 * @return bool
	 */
    protected function store_notices( $notices=[] )
    {
	    return set_transient( $this->get_transient_name() , $notices, MINUTE_IN_SECONDS );
    }

    /**
     * Add a notice
     *
     * @param $code string|\WP_Error|array ID of the notice
     * @param $message string message
     * @param string $type
     * @param string|bool $cap
     * @param bool $site_wide whether the notice should be displayed site_wide
     *
     * @return true|false
     */
    public function add( $code='', $message='', $type='success', $cap=false, $site_wide=false )
    {
        if ( ! $this->can_add_notices() ){
            return false;
        }

        $notices = $this->get_stored_notices();

        if ( ! $notices || ! is_array( $notices ) ) {
            $notices = array();
        }

        $data = [];

        // Is WP Error
        if ( is_wp_error( $code ) ){
            $error = $code;
            $code = $error->get_error_code();
            $message = $error->get_error_message();

            if ( ! empty( $error->get_error_data() ) ){
                $data = [
                    'code' => $error->get_error_code(),
                    'message' => $error->get_error_message(),
                    'data' => $error->get_error_data(),
                ];
            }

            $type = 'error';
        }
        // Passed as array
        elseif ( is_array( $code ) ){

            $args = wp_parse_args( $code, [
                'code' => '',
                'message' => '',
                'type' => 'success',
                'data' => false,
                'cap' => false,
                'site_wide' => false
            ] );

            extract( $args );
        }

        $notices[$code][ 'code' ]    = $code;
        $notices[$code][ 'message' ] = $message;
        $notices[$code][ 'type' ]    = $type;
        $notices[$code][ 'data' ]    = $data;
        $notices[$code][ 'cap' ]     = $cap;
        $notices[$code][ 'site_wide' ] = $site_wide;

        $this->store_notices( $notices );

        return true;
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public function remove( $code='' )
    {
	    if ( ! $this->can_add_notices() ){
	        return false;
        }

        $notices = $this->get_stored_notices();
        unset( $notices[ $code ] );
        $this->store_notices( $notices );

        return true;
    }

    /**
     * Get the notices
     */
    public function notices()
    {

        $notices = $this->get_stored_notices();

        if ( ! $notices ){
            $notices = [];
        }

        if ( ! wp_doing_ajax() ){
            ?><div id="groundhogg-notices"><?php
        }

        foreach ( $notices as $code => $notice ){

            // If doing admin_notices do not show sitewide notices.
            if ( doing_action( 'admin_notices' ) && ! get_array_var( $notice, 'site_wide' ) ){
                continue;
            }

            if ( isset_not_empty( $notice, 'cap' ) && ! current_user_can( $notice[ 'cap' ] ) ){
                continue;
            }

            ?>
            <div id="<?php esc_attr_e( $notice['code'] ); ?>" class="notice notice-<?php esc_attr_e( $notice[ 'type' ] ); ?> is-dismissible"><p><strong><?php echo wp_kses_post( $notice[ 'message' ] ); ?></strong></p>
                <?php if ( $notice[ 'type' ] === 'error' && ! empty( $notice[ 'data' ] ) ): ?>
                    <p><textarea class="code" style="width: 100%;" readonly><?php echo wp_json_encode( $notice[ 'data' ], JSON_PRETTY_PRINT ); ?></textarea></p>
                <?php endif; ?>
            </div>
            <?php

            unset( $notices[ $code ] );
        }

        if ( ! wp_doing_ajax() ) {
            ?></div><?php
        }

        if ( ! empty( $notices ) ){
            set_transient( $this->get_transient_name(), $notices, MINUTE_IN_SECONDS );
        } else {
            delete_transient( $this->get_transient_name() );
        }
    }

    /**
     * Print the notices.
     *
     * @param bool $echo
     * @return true|string
     */
    public function print_notices( $echo=true )
    {
        if ( $echo ){
            $this->notices();
            return true;
        }

        ob_start();

        $this->notices();

        $notices = ob_get_clean();

        return $notices;

    }

}
