<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-08
 * Time: 10:39 AM
 */

if ( ! function_exists( 'wp_mail' ) && get_option( 'gh_send_all_email_through_ghss', false ) ):

    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
        try{
            return \Groundhogg\gh_ss_mail( $to, $subject, $message, $headers, $attachments);
        } catch (Exception $exception ){
            return false;
        }
    }

endif;