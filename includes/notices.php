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

    /**
     * Add a notice
     *
     * @param $code string|\WP_Error ID of the notice
     * @param $message string message
     * @param string $type
     * @param string|bool $cap
     */
    public function add( $code='', $message='', $type='success', $cap=false )
    {
        $notices = get_transient( self::TRANSIENT );

        if ( ! $notices || ! is_array( $notices ) )
        {
            $notices = array();
        }

        $data = [];
        if ( is_wp_error( $code ) ){
            $data = $code->get_error_data();
            $error = $code;
            $code = $error->get_error_code();
            $message = $error->get_error_message();
            $type = 'error';
        }

        $notices[$code][ 'code' ]    = $code;
        $notices[$code][ 'message' ] = $message;
        $notices[$code][ 'type' ]    = $type;
        $notices[$code][ 'data' ]    = $data;
        $notices[$code][ 'cap' ]     = $cap;

        set_transient( self::TRANSIENT, $notices, 60 );
    }

    /**
     * @param string $code
     */
    public function remove( $code='' )
    {
        $notices = get_transient( self::TRANSIENT );
        unset( $notices[ $code ] );
        set_transient( self::TRANSIENT, $notices, 60 );
    }

    /**
     * Get the notices
     */
    public function notices()
    {

        $notices = get_transient( self::TRANSIENT );

        if ( ! $notices ){
            $notices = [];
        }

        if ( ! wp_doing_ajax() ){
            ?><div id="groundhogg-notices"><?php
        }

        foreach ( $notices as $notice ){

            if ( isset_not_empty( $notice, 'cap' ) && ! current_user_can( $notice[ 'cap' ] ) ){
                continue;
            }

            ?>
            <div id="<?php esc_attr_e( $notice['code'] ); ?>" class="notice notice-<?php esc_attr_e( $notice[ 'type' ] ); ?> is-dismissible"><p><strong><?php echo wp_kses_post( $notice[ 'message' ] ); ?></strong></p>
                <?php if ( $notice[ 'type' ] === 'error' && ! empty( $notice[ 'data' ] ) ): ?>
                    <p><textarea class="code" style="width: 100%;" readonly><?php echo wp_json_encode( $notice[ 'data' ] ); ?></textarea></p>
                <?php endif; ?>
            </div>
            <?php
        }

        if ( ! wp_doing_ajax() ) {
            ?></div><?php
        }

        delete_transient( 'groundhogg_notices' );
    }

    /**
     * Alias for notices()
     */
    public function print_notices()
    {
        $this->notices();
    }

}
