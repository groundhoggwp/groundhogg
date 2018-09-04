<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-07
 * Time: 3:52 PM
 */

/**
 * Process the contact shortcode
 */
function wpfn_contact_replacement_shortcode( $atts )
{
    $a = shortcode_atts( array(
        'field' => 'first'
    ), $atts );

    $contact = wpfn_get_the_contact();

    if ( ! $contact )
        return __( 'Friend', 'groundhogg' );

    if ( substr( $a['field'], 0, 1) === '_' ) {
        $new_replacement = $contact->get_meta( substr( $a['field'], 1) );
    } else {

        if ( strpos( $a['field'], '.' ) > 0 ){

            $parts = explode( '.', $a['field'] );

            $function = $parts[0];
            $arg = $parts[1];
            $new_replacement = apply_filters( 'wpfn_replacement_' . $function, $arg, $contact );

        } else {
            $new_replacement = apply_filters( 'wpfn_replacement_' . $a['field'], $contact );
        }
    }

    return $new_replacement;
}

add_shortcode( 'gh_contact', 'wpfn_contact_replacement_shortcode' );

/**
 * Mere contact replacements into page content with this shortcode.
 *
 * @param $atts array should be empty
 * @param string $content the content to perfotm the merge fields
 * @return string the updated content,.
 */
function wpfn_merge_replacements_shortcode( $atts, $content = '' )
{
    $contact = wpfn_get_the_contact();
    return wpfn_do_replacements( $contact->get_id(), $contact );
}

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

    do_action( 'wpfn_load_replacements' );

    preg_match_all( '/{[^{}]+}/', $content, $matches );
    $actual_matches = $matches[0];

    $contact = new WPFN_Contact( $contact_id );

    foreach ( $actual_matches as $pattern ) {

        # trim off the { and } from either end.
        $replacement = substr( $pattern, 1, -1);

        if ( substr($replacement, 0, 1) === '_' ) {
            $new_replacement = $contact->get_meta( substr( $replacement, 1 ) );
        } else {

            if ( strpos( $replacement, '.' ) > 0 ){

                $parts = explode( '.', $replacement );

                $function = $parts[0];
                $arg = $parts[1];
                $new_replacement = apply_filters( 'wpfn_replacement_' . $function, $arg, $contact );

            } else {
                $new_replacement = apply_filters( 'wpfn_replacement_' . $replacement, $contact );
            }
        }

        $content = preg_replace( sprintf( "/%s/", $pattern ), $new_replacement, $content );
    }

    return $content;

}

/**
 * Return back the first name ot the contact.
 *
 * @param $contact WPFN_Contact the contact
 * @return string the first name
 */
function wpfn_replacement_first_name( $contact )
{
    return $contact->get_first();
}

add_filter( 'wpfn_replacement_first_name', 'wpfn_replacement_first_name' );
add_filter( 'wpfn_replacement_first', 'wpfn_replacement_first_name' );

/**
 * Return back the last name ot the contact.
 *
 * @param $contact WPFN_Contact the contact
 * @return string the last name
 */
function wpfn_replacement_last_name( $contact )
{
    return $contact->get_last();
}

add_filter( 'wpfn_replacement_last_name', 'wpfn_replacement_last_name' );
add_filter( 'wpfn_replacement_last', 'wpfn_replacement_last_name' );

/**
 * Return back the email of the contact.
 *
 * @param $contact WPFN_Contact the contact
 * @return string the email
 */
function wpfn_replacement_email( $contact )
{
    return $contact->get_email();
}

add_filter( 'wpfn_replacement_email', 'wpfn_replacement_email' );

/**
 * Return a confirmation link for the contact
 * This just gets the Optin Page link for now.
 *
 * @param $contact WPFN_Contact the contact
 * @return string the optin link
 */
function wpfn_replacement_confirmation_link( $contact )
{
    $link_text = get_option( 'gh_confirmation_text', __( 'Confirm your email', 'groundhogg' ) );
    $link_url = site_url( 'gh-confirmation/via/email/' );

    return "<a href=\"$link_url\" target=\"_blank\">$link_text</a>";
}

add_filter( 'wpfn_replacement_confirmation_link', 'wpfn_replacement_confirmation_link' );

function wpfn_replacement_date( $time_string, $contact )
{

    $parts = explode( ';', $time_string );

    if ( count( $parts ) === 1 ){
        $format = 'l jS \of F Y';
        $when = $parts[0];
    } else {
        $format = $parts[0];
        $when = $parts[1];
    }

    return date( $format, strtotime( $when ) );

}

add_filter( 'wpfn_replacement_date', 'wpfn_replacement_date', 10, 2 );

function wpfn_replacement_business_name( $contact )
{
    return get_option( 'gh_business_name' );
}

add_filter( 'wpfn_replacement_business_name', 'wpfn_replacement_business_name' );

function wpfn_replacement_business_phone( $contact )
{
    return get_option( 'gh_phone' );
}

add_filter( 'wpfn_replacement_business_phone', 'wpfn_replacement_business_phone' );

function wpfn_replacement_business_address( $contact )
{
    $address = array();

    if ( get_option( 'gh_street_address_1' ) )
        $address[] = get_option( 'gh_street_address_1' ) . ' ' . get_option( 'gh_street_address_2' );
    if ( get_option( 'gh_city' ) )
        $address[] = get_option( 'gh_city' );
    if ( get_option( 'gh_region' ) )
        $address[] = get_option( 'gh_region' );
    if ( get_option( 'gh_country' ) )
        $address[] = get_option( 'gh_country' );
    if ( get_option( 'gh_zip_or_postal' ) )
        $address[] = strtoupper( get_option( 'gh_zip_or_postal' ) );

    $address = implode( ', ', $address );

    return $address;
}

add_filter( 'wpfn_replacement_business_address', 'wpfn_replacement_business_address' );