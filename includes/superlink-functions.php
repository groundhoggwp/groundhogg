<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-21
 * Time: 2:00 PM
 */


/**
 * Do the link replacement...
 *
 * @param $linkId int the ID of the link
 * @param $contact WPFN_Contact the contact
 *
 * @return string the superlink url
 */
function wpfn_superlink_replacement_callback( $linkId, $contact )
{
    $linkId = absint( intval( $linkId ) );
    return site_url( 'superlinks/link/' . $linkId );
}

add_filter( 'wpfn_replacement_superlink', 'wpfn_superlink_replacement_callback', 10, 2 );

/**
 * Filter out http://http:// as a result of wpLink enforcing http:// even when using {replacements} which is really annoying.
 *
 * @param $content
 * @return string, the email content
 */
function wpfn_filter_out_double_http( $content )
{
    $schema = is_ssl()? 'https://' : 'http://';

    $content = str_replace( 'http://https://', $schema, $content );
    $content = str_replace( 'http://http://', $schema, $content );

    return $content;
}

add_filter( 'wpfn_the_email_content', 'wpfn_filter_out_double_http' );
add_filter( 'wpfn_sanitize_email_content', 'wpfn_filter_out_double_http' );

function wpfn_filter_out_http_superlink_prefix( $content )
{
    return preg_replace( '/http:\/\/({superlink\.\d})/', '${1}', $content );
}

add_filter( 'wpfn_the_email_content', 'wpfn_filter_out_http_superlink_prefix' );
add_filter( 'wpfn_sanitize_email_content', 'wpfn_filter_out_http_superlink_prefix' );
