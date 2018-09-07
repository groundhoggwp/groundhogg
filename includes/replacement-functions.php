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
function wpgh_contact_replacement_shortcode( $atts )
{
    $a = shortcode_atts( array(
        'field' => 'first'
    ), $atts );

    $contact = wpgh_get_the_contact();

    if ( ! $contact )
        return __( 'Friend', 'groundhogg' );

    if ( substr( $a['field'], 0, 1) === '_' ) {
        $new_replacement = $contact->get_meta( substr( $a['field'], 1) );
    } else {

        if ( strpos( $a['field'], '.' ) > 0 ){

            $parts = explode( '.', $a['field'] );

            $function = $parts[0];
            $arg = $parts[1];
            $new_replacement = apply_filters( 'wpgh_replacement_' . $function, $arg, $contact );

        } else {
            $new_replacement = apply_filters( 'wpgh_replacement_' . $a['field'], $contact );
        }
    }

    return $new_replacement;
}

add_shortcode( 'gh_contact', 'wpgh_contact_replacement_shortcode' );

/**
 * Mere contact replacements into page content with this shortcode.
 *
 * @param $atts array should be empty
 * @param string $content the content to perfotm the merge fields
 * @return string the updated content,.
 */
function wpgh_merge_replacements_shortcode( $atts, $content = '' )
{
    $contact = wpgh_get_the_contact();

    if ( ! $contact )
        return '';

    return wpgh_do_replacements( $contact->get_id(), $contact );
}

/**
 * Substitute the replacement codes with actual contact information.
 *
 * @param $contact_id int    The Contact's ID
 * @param $content    string the content to replace
 *
 * @return string, the content with codes replaced with contact data
 */
function wpgh_do_replacements( $contact_id, $content )
{

    if ( ! $contact_id || ! is_int( $contact_id ) )
        return false;

    do_action( 'wpgh_load_replacements' );

    preg_match_all( '/{[^{}]+}/', $content, $matches );
    $actual_matches = $matches[0];

    $contact = new WPGH_Contact( $contact_id );

    foreach ( $actual_matches as $pattern ) {

        # trim off the { and } from either end.
        $replacement = substr( $pattern, 1, -1);

        if ( substr($replacement, 0, 1) === '_' ) {
            $new_replacement = $contact->get_meta( substr( $replacement, 1 ) );
        } else {

            if ( strpos( $replacement, '.' ) > 0 ){

                $parts = explode( '.', $replacement );

                $function = $parts[0];

                if ( ! isset( $parts[1] ) )
                {
                    $arg = false;
                } else {
                    $arg = $parts[1];
                }

                $new_replacement = apply_filters( 'wpgh_replacement_' . $function, $arg, $contact );

            } else {
                $new_replacement = apply_filters( 'wpgh_replacement_' . $replacement, $contact );
            }
        }

        $content = preg_replace( sprintf( "/%s/", $pattern ), $new_replacement, $content );
    }

    return $content;

}

/**
 * Return he contact meta
 *
 * @param $contact WPGH_Contact
 * @param $arg string the meta key
 * @return mixed|string
 */
function wpgh_replacement_meta( $contact, $arg )
{
    if ( ! $arg )
        return '';

    return print_r( wpgh_get_contact_meta( $contact->get_id(), $arg, true ) , true );
}

add_filter( 'wpgh_replacement_meta', 'wpgh_replacement_meta' );

/**
 * Return back the first name ot the contact.
 *
 * @param $contact WPGH_Contact the contact
 * @return string the first name
 */
function wpgh_replacement_first_name( $contact )
{
    return $contact->get_first();
}

add_filter( 'wpgh_replacement_first_name', 'wpgh_replacement_first_name' );
add_filter( 'wpgh_replacement_first', 'wpgh_replacement_first_name' );

/**
 * Return back the last name ot the contact.
 *
 * @param $contact WPGH_Contact the contact
 * @return string the last name
 */
function wpgh_replacement_last_name( $contact )
{
    return $contact->get_last();
}

add_filter( 'wpgh_replacement_last_name', 'wpgh_replacement_last_name' );
add_filter( 'wpgh_replacement_last', 'wpgh_replacement_last_name' );

/**
 * Return back the email of the contact.
 *
 * @param $contact WPGH_Contact the contact
 * @return string the email
 */
function wpgh_replacement_email( $contact )
{
    return $contact->get_email();
}

add_filter( 'wpgh_replacement_email', 'wpgh_replacement_email' );

/**
 * Return back the email address of the contact owner.
 *
 * @param $contact WPGH_Contact the contact
 * @return string the owner's email
 */
function wpgh_replacement_owner_email( $contact )
{
    $owner = $contact->get_owner();

    if ( ! $owner )
        return get_bloginfo( 'admin_email' );

    $user = get_userdata( $owner );

    return $user->user_email;
}

add_filter( 'wpgh_replacement_owner_email', 'wpgh_replacement_owner_email' );

/**
 * Return back the first name of the contact owner.
 *
 * @param $contact WPGH_Contact the contact
 * @return string the owner's name
 */
function wpgh_replacement_owner_first_name( $contact )
{
    $owner = $contact->get_owner();

    if ( ! $owner )
        return get_bloginfo( 'admin_email' );

    $user = get_userdata( $owner );

    return $user->first_name;
}

add_filter( 'wpgh_replacement_owner_first_name', 'wpgh_replacement_owner_first_name' );

/**
 * Return back the first name of the contact owner.
 *
 * @param $contact WPGH_Contact the contact
 * @return string the owner's name
 */
function wpgh_replacement_owner_last_name( $contact )
{
    $owner = $contact->get_owner();

    if ( ! $owner )
        return get_bloginfo( 'admin_email' );

    $user = get_userdata( $owner );

    return $user->last_name;
}

add_filter( 'wpgh_replacement_owner_last_name', 'wpgh_replacement_owner_last_name' );

/**
 * Return a confirmation link for the contact
 * This just gets the Optin Page link for now.
 *
 * @param $contact WPGH_Contact the contact
 * @return string the optin link
 */
function wpgh_replacement_confirmation_link( $contact )
{
    $link_text = get_option( 'gh_confirmation_text', __( 'Confirm your email', 'groundhogg' ) );
    $link_url = site_url( 'gh-confirmation/via/email/' );

    return "<a href=\"$link_url\" target=\"_blank\">$link_text</a>";
}

add_filter( 'wpgh_replacement_confirmation_link', 'wpgh_replacement_confirmation_link' );

function wpgh_replacement_date( $time_string, $contact )
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

add_filter( 'wpgh_replacement_date', 'wpgh_replacement_date', 10, 2 );

function wpgh_replacement_business_name( $contact )
{
    return get_option( 'gh_business_name' );
}

add_filter( 'wpgh_replacement_business_name', 'wpgh_replacement_business_name' );

function wpgh_replacement_business_phone( $contact )
{
    return get_option( 'gh_phone' );
}

add_filter( 'wpgh_replacement_business_phone', 'wpgh_replacement_business_phone' );

function wpgh_replacement_business_address( $contact )
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

add_filter( 'wpgh_replacement_business_address', 'wpgh_replacement_business_address' );