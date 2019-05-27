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

class Dialogger
{
    const TRANSIENT = 'groundhogg_dialogs';

    public function __construct()
    {
        add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );
        add_action( 'admin_footer', [ $this, 'print_dialogs' ] );

        $this->add( 'test', 'You can search our documentation and helpful articles in the event you have questions.', [
                'title' => 'Documentation',
            ],
            [
                'my' => 'left',
                'at' => 'right',
                'of' => '.postbox.support'
            ]
        );
    }

    public function scripts()
    {
        if ( $this->has_dialogs() ){
            // Ensure these are enqueued!
            wp_enqueue_script( 'jquery-ui-dialog', [ 'jquery', 'jquery-ui' ] );
            wp_enqueue_style( 'wp-jquery-ui-dialog' );
        }
    }

    public function has_dialogs()
    {
        $dialogs = get_transient( self::TRANSIENT );
        return ! empty( $dialogs );
    }


    /**
     * Add a notice
     *
     * @param $code string|\WP_Error ID of the notice
     * @param $message string message
     * @param array $atts
     */
    public function add( $code='', $message='', $atts=[], $position=[] )
    {
        $dialogs = get_transient( self::TRANSIENT );

        if ( ! $dialogs || ! is_array( $dialogs ) ) {
            $dialogs = array();
        }

        $atts = wp_parse_args( $atts, [
            'title' => '',
            'dialogClass' => 'wp-dialog',
            'autoOpen' => false,
            'draggable' => false,
            'width' => '300px',
            'modal' => false,
            'resizable' => false,
            'closeOnEscape' => true,
        ] );

        $position = wp_parse_args( $position, [
            'my' => 'center',
            'at' => 'center',
            'of' => 'body'
        ] );

        $dialogs[$code][ 'code' ]    = $code;
        $dialogs[$code][ 'message' ] = $message;
        $dialogs[$code][ 'atts' ]    = $atts;
        $dialogs[$code][ 'position' ] = $position;

        set_transient( self::TRANSIENT, $dialogs, 60 );
    }

    /**
     * @param string $code
     */
    public function remove( $code='' )
    {
        $dialogs = get_transient( self::TRANSIENT );
        unset( $dialogs[ $code ] );
        set_transient( self::TRANSIENT, $dialogs, 60 );
    }

    /**
     * Alias for notices()
     */
    public function print_dialogs()
    {
        $dialogs = get_transient( self::TRANSIENT );

        if ( empty( $dialogs ) ){
            return;
        }

        if ( ! $dialogs ){
            $dialogs = [];
        }

        foreach ( $dialogs as $i => $dialog ){

            ?>
            <div id="<?php esc_attr_e( $dialog['code'] ); ?>" class="dialog hidden"><p><strong><?php echo wp_kses_post( $dialog[ 'message' ] ); ?></strong></p></div>
            <script>
                jQuery(function ($) {
                    var $dialog = $( '#<?php esc_attr_e( $dialog['code'] ); ?>' ).dialog( <?php echo wp_json_encode( $dialog[ 'atts' ] );?> );
                    var $e = $( '<?php esc_attr_e( $dialog['position'][ 'of' ] ); ?>' );
                    $dialog.dialog( 'option', 'position', { my: '<?php echo $dialog[ 'position' ][ 'my' ]; ?>', at: '<?php echo $dialog[ 'position' ][ 'at' ]; ?>', of: $e } );

                    <?php if (  $i == 0 ): ?>
                    $dialog.dialog( 'open' );
                    <?php endif; ?>
                });
            </script>
            <?php

        }

        delete_transient( self::TRANSIENT );
    }

}
