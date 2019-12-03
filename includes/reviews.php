<?php

namespace Groundhogg;


class Reviews
{

    public function __construct()
    {

        add_action( 'admin_notices', [ $this, 'show_review_request' ] );
        add_action( 'wp_ajax_groundhogg_dismiss_review', [ $this, 'dismiss_review' ] );

    }

    public function show_review_request()
    {
        if ( ! current_user_can( 'administrator' ) ){
            return;
        }

        if ( get_transient( 'groundhogg_review_request_dismissed' ) ){
            return;
        }

        $message = sprintf(
            esc_html__( 'Is Groundhogg working for you? Show your appreciation by leaving a 5 %s review on %s! %s | %s | %s', 'groundhogg' ),
            '<span class="dashicons dashicons-star-filled"></span>',
            '<a target="_blank" href="https://wordpress.org/support/plugin/groundhogg/reviews/#new-post">WordPress.org</a>',
            html()->e( 'a', [ 'class' => 'notice-dismiss-link', 'style' => [ 'color' => '#a00' ], 'href' => '#' ],  __( "I don't want to", 'groundhogg' ) ),
            html()->e( 'a', [ 'class' => 'notice-dismiss-link', 'style' => [ 'color' => '' ], 'href' => '#' ], __( "I already did", 'groundhogg' ) ),
            html()->e( 'a', [ 'class' => '', 'style' => [ 'color' => 'green' ], 'href' => 'https://wordpress.org/support/plugin/groundhogg/reviews/#new-post', 'target' => '_blank' ], __( "I'll leave a review!", 'groundhogg' ) )
        );

        $html_message = sprintf( '<div class="review-request notice notice-info is-dismissible">%s</div>', wpautop( $message ) );

        echo wp_kses_post( $html_message );

        ?>
        <script>
            (function($){
                $(function () {

                    $( '.notice-dismiss-link' ).click( function (e) {
                        e.preventDefault();

                        $(this).closest( '.notice' ).fadeOut( 100, function () {
                            $(this).remove();
                        } );

                        adminAjaxRequest( { action: 'groundhogg_dismiss_review' }, function ( response ) {
                            console.log( response )
                        } );
                    } )
                });
            })(jQuery)
        </script>
        <?php

    }

    public function dismiss_review()
    {

        if ( ! current_user_can( 'administrator' ) ){
            return;
        }

        set_transient( 'groundhogg_review_request_dismissed', WEEK_IN_SECONDS );

        wp_send_json_success();
    }

}