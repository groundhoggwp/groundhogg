<?php
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

class WPGH_Notices
{
    const TRANSIENT = 'wpgh_notices';

    /**
     * Add a notice
     *
     * @param $code string|WP_Error ID of the notice
     * @param $message string message
     * @param string $type
     */
    public function add( $code='', $message='', $type='success' )
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

        set_transient( self::TRANSIENT, $notices, 60 );
    }

    /**
     * Get the notices
     */
    public function notices()
    {
        $notices = get_transient( self::TRANSIENT );

        if ( ! $notices )
            return;

        foreach ( $notices as $notice ){
            ?>
            <div id="<?php esc_attr_e( $notice['code'] ); ?>" class="notice notice-<?php esc_attr_e( $notice[ 'type' ] ); ?> is-dismissible"><p><strong><?php echo $notice[ 'message' ]; ?></strong></p>
            <?php if ( $notice[ 'type' ] === 'error' && ! empty( $notice[ 'data' ] ) ): ?>
                <p><textarea class="code" style="width: 100%;" readonly ><?php echo json_encode( $notice[ 'data' ] ); ?></textarea></p>
            <?php endif; ?>
            </div>
            <?php
        }

	    delete_transient( 'wpgh_notices' );
    }

}
