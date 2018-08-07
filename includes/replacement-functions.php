<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-07
 * Time: 3:52 PM
 */


/**
 * Substitute the replacement codes with actual contact information.
 *
 * @param $contact_id int    The Contact's ID
 * @param $content    string the content to replace
 *
 * @return string, the content with codes replaced with contact data
 */
function wpfn_do_replacements( $contact_id, $content )
{

    if ( ! $contact_id || ! is_int( $contact_id ) )
        return false;

    preg_match_all( '/{[\w\d]+}/', $content, $matches );
    $actual_matches = $matches[0];

    $contact = new WPFN_Contact( $contact_id );

    foreach ( $actual_matches as $pattern ) {

        # trim off the { and } from either end.
        $replacement = substr( $pattern, 1, -1);

        $new_replacement = call_user_func( $replacement, $contact_id );

        $content = preg_replace( '/' . $pattern . '/', $new_replacement, $content );
    }

    return $content;

}

function wpfn_get_replacement_codes()
{

}

function wpfn_add_replacment_code( $tag, $callback )
{

}