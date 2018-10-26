<?php
/**
 * Replacements
 *
 * The inspiration for this class came from EDD_Email_Tags by easy digital downloads.
 * But ours is better because it allows for dynamic arguments passed with the replacements code.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Replacements
{

    /**
     * Array of replacement codes and their callback functions
     *
     * @var array
     */
    var $replacements = array();

    /**
     * The contact ID
     *
     * @var int
     */
    var $contact_id;


    public function __construct()
    {

        $this->setup_defaults();

    }

    /**
     * Setup the default replacement codes
     */
    private function setup_defaults()
    {

        $replacements = array(
            array(
                'code'        => 'first',
                'callback'    => 'wpgh_replacement_first_name',
                'description' => __( 'The contact\'s first name.', 'groundhogg' ),
            ),
            array(
                'code'        => 'first_name',
                'callback'    => 'wpgh_replacement_first_name',
                'description' => __( 'The contact\'s first name.', 'groundhogg' ),
            ),
            array(
                'code'        => 'last',
                'callback'    => 'wpgh_replacement_last_name',
                'description' => __( 'The contact\'s last name.', 'groundhogg' ),
            ),
            array(
                'code'        => 'last_name',
                'callback'    => 'wpgh_replacement_last_name',
                'description' => __( 'The contact\'s last name.', 'groundhogg' ),
            ),
            array(
                'code'        => 'email',
                'callback'    => 'wpgh_replacement_email',
                'description' => __( 'The contact\'s email address.', 'groundhogg' ),
            ),
            array(
                'code'        => 'phone',
                'callback'    => 'wpgh_replacement_phone',
                'description' => __( 'The contact\'s phone number.', 'groundhogg' ),
            ),
            array(
                'code'        => 'phone_ext',
                'callback'    => 'wpgh_replacement_phone_ext',
                'description' => __( 'The contact\'s phone number extension.', 'groundhogg' ),
            ),
            array(
                'code'        => 'address',
                'callback'    => 'wpgh_replacement_address',
                'description' => __( 'The contact\'s full address.', 'groundhogg' ),
            ),
            array(
                'code'        => 'meta',
                'callback'    => 'wpgh_replacement_meta',
                'description' => __( 'Any meta data related to the contact. Usage: {meta.attribute}', 'groundhogg' ),
            ),
            array(
                'code'        => 'business_name',
                'callback'    => 'wpgh_replacement_business_name',
                'description' => __( 'The business name as defined in the settings.', 'groundhogg' ),
            ),
            array(
                'code'        => 'business_phone',
                'callback'    => 'wpgh_replacement_business_phone',
                'description' => __( 'The business phone number as defined in the settings.', 'groundhogg' ),
            ),
            array(
                'code'        => 'business_address',
                'callback'    => 'wpgh_replacement_business_address',
                'description' => __( 'The business address as defined in the settings.', 'groundhogg' ),
            ),
            array(
                'code'        => 'owner_first_name',
                'callback'    => 'wpgh_replacement_owner_name',
                'description' => __( 'The contact owner\'s name.', 'groundhogg' ),
            ),
            array(
                'code'        => 'owner_last_name',
                'callback'    => 'wpgh_replacement_owner_name',
                'description' => __( 'The contact owner\'s name.', 'groundhogg' ),
            ),
            array(
                'code'        => 'owner_email',
                'callback'    => 'wpgh_replacement_owner_email',
                'description' => __( 'The contact owner\'s email address.', 'groundhogg' ),
            ),
            array(
                'code'        => 'owner_phone',
                'callback'    => 'wpgh_replacement_owner_phone',
                'description' => __( 'The contact owner\'s phone number.', 'groundhogg' ),
            ),
            array(
                'code'        => 'confirmation_link',
                'callback'    => 'wpgh_replacement_confirmation_link',
                'description' => __( 'A link to confirm the email address of a contact.', 'groundhogg' ),
            ),
            array(
                'code'        => 'superlink',
                'callback'    => 'wpgh_replacement_superlink',
                'description' => __( 'A superlink code. Usage: {superlink.id}', 'groundhogg' ),
            ),
            array(
                'code'        => 'date',
                'callback'    => 'wpgh_replacement_date',
                'description' => __( 'Insert a dynamic date. Usage {date.format|time}. Example: {date.Y-m-d|+2 days}', 'groundhogg' ),
            ),
        );

        $replacements = apply_filters( 'wpgh_replacement_defaults', $replacements );

        foreach ( $replacements as $replacement )
        {
            $this->add( $replacement['code'], $replacement[ 'callback' ], $replacement[ 'description' ] );
        }

    }

    /**
     * Add a replacement code
     *
     * @param $code string the code
     * @param $callback string|array the callback function
     * @param string $description string description of the code
     *
     * @return bool
     */
    function add( $code, $callback, $description='' )
    {
        if ( ! $code || ! $callback )
            return false;

        if ( is_callable( $callback ) )
        {
            $this->replacements[ $code ] = array(
                'code' => $code,
                'callback' => $callback,
                'description' => $description
            );

            return true;
        }

        return false;

    }

    /**
     * Remove a replacement code
     *
     * @since 1.9
     *
     * @param string $code to remove
     */
    public function remove( $code )
    {
        unset( $this->replacements[$code] );
    }

    /**
     * See if the replacement code exists already
     *
     * @param $code
     *
     * @return bool
     */
    function has_replacement( $code )
    {
        return array_key_exists( $code, $this->replacements );
    }

    /**
     * Returns a list of all replacement codes
     *
     * @since 1.9
     *
     * @return array
     */
    public function get_replacements()
    {
        return $this->replacements;
    }

    /**
     * Process the codes based on the given contact ID
     *
     * @param $contact_id int ID of the contact
     * @param $content
     *
     * @return string
     */
    public function process( $content, $contact_id=null )
    {

        if ( empty( $contact_id ) )
            $contact_id = WPGH()->tracking->get_contact()->ID;

        if ( ! $contact_id || ! is_int( $contact_id ) )
            return $content;

        // Check if there is at least one tag added
        if ( empty( $this->replacements ) || ! is_array( $this->replacements ) ) {
            return $content;
        }

        if ( ! wpgh_should_if_multisite() ){
            //switch to main blog for this process.
            switch_to_blog( get_network()->site_id );
        }

        $this->contact_id = $contact_id;
        $new_content = preg_replace_callback( "/{([^{}]+)}/s", array( $this, 'do_replacement' ), $content );
        $this->contact_id = null;

        if ( ! wpgh_should_if_multisite() ){
            //switch to main blog for this process.
            restore_current_blog();
        }

        return $new_content;

    }

    /**
     * Process the given replacement code
     *
     * @param $m
     *
     * @return mixed
     */
    private function do_replacement( $m )
    {
        // Get tag
        $code = $m[1];

        /* make sure that if it's a dynamic code to remove anything after the period */
        if ( strpos( $code, '.' ) > 0 ) {
            $parts = explode( '.', $code );
            $code = $parts[0];
        }

        // Return tag if tag not set
        if ( ! $this->has_replacement( $code ) && substr( $code, 0, 1 ) !== '_' ) {
            return $m[0];
        }

        /* reset code */
        $code = $m[1];

        if ( substr( $code, 0, 1) === '_' ) {

            $text = WPGH()->contact_meta->get_meta( $this->contact_id, substr( $code, 1 ) );

        } else if ( strpos( $code, '.' ) > 0 ) {

            $parts = explode( '.', $code );
            $code = $parts[0];

            if ( ! isset( $parts[1] ) ) {
                $arg = false;
            } else {
                $arg = $parts[1];
            }

            $text = call_user_func( $this->replacements[ $code ]['callback'], $arg, $this->contact_id, $code );

        } else {

            $text = call_user_func( $this->replacements[ $code ]['callback'], $this->contact_id, $code );

        }

        return apply_filters( 'wpgh_filter_replacement_' . $code, $text );

    }


}

/**
 * Return the contact meta
 *
 * @param $contact_id int
 * @param $arg string the meta key
 * @return mixed|string
 */
function wpgh_replacement_meta( $arg, $contact_id )
{
    if ( empty( $arg ) )
        return '';

    return print_r( WPGH()->contact_meta->get_meta( $contact_id, $arg, true ) , true );
}

/**
 * Return back the first name ot the contact.
 *
 * @param $contact_id int the contact_id
 * @return string the first name
 */
function wpgh_replacement_first_name( $contact_id )
{
    return WPGH()->contacts->get_column_by( 'first_name', 'ID', $contact_id );
}

/**
 * Return back the last name ot the contact.
 *
 * @param $contact_id int the contact_id
 * @return string the last name
 */
function wpgh_replacement_last_name( $contact_id )
{
    return WPGH()->contacts->get_column_by( 'last_name', 'ID', $contact_id );
}

/**
 * Return back the email of the contact.
 *
 * @param $contact_id int the contact ID
 * @return string the email
 */
function wpgh_replacement_email( $contact_id )
{
    return WPGH()->contacts->get_column_by( 'email', 'ID', $contact_id );
}

/**
 * Return back the phone # ot the contact.
 *
 * @param $contact_id int the contact_id
 * @return string the first name
 */
function wpgh_replacement_phone( $contact_id )
{
    return WPGH()->contact_meta->get_meta( $contact_id, 'primary_phone', true );
}

/**
 * Return back the phone # ext the contact.
 *
 * @param $contact_id int the contact_id
 * @return string the first name
 */
function wpgh_replacement_phone_ext( $contact_id )
{
    return WPGH()->contact_meta->get_meta( $contact_id, 'primary_phone_extension', true );
}

/**
 * Return back the address of the contact.
 *
 * @param $contact_id int the contact_id
 * @return string the first name
 */
function wpgh_replacement_address( $contact_id )
{

    $contact = new WPGH_Contact( $contact_id );

    $address = array();

    if ( $contact->get_meta( 'gh_street_address_1' ) )
        $address[] = $contact->get_meta( 'gh_street_address_1' );
    if ( $contact->get_meta( 'gh_street_address_2' ) )
        $address[] = ' ' . $contact->get_meta( 'gh_street_address_2' );
    if ( $contact->get_meta( 'city' ) )
        $address[] = $contact->get_meta( 'city' );
    if ( $contact->get_meta( 'region' ) )
        $address[] = $contact->get_meta( 'region' );

    if ( $contact->get_meta( 'country' ) ){
        $countries  = wpgh_get_countries_list();
        $address[] = $countries[ $contact->get_meta( 'country' ) ];
    }

    if ( $contact->get_meta( 'zip_postal' ) )
        $address[] = strtoupper( $contact->get_meta( 'zip_postal' ) );

    $address = implode( ', ', $address );

    return $address;

}

/**
 * Return the contact's owner
 *
 * @param $contact_id int the contact ID
 *
 * @return false|string|WP_User
 */
function wpgh_get_contact_owner( $contact_id )
{
    $owner = (int) WPGH()->contacts->get_column_by( 'owner_id', 'ID', $contact_id );

    if ( ! $owner )
        return get_bloginfo( 'admin_email' );

    return get_userdata( $owner );
}

/**
 * Return back the email address of the contact owner.
 *
 * @param $contact_id int the contact ID
 * @return string the owner's email
 */
function wpgh_replacement_owner_email( $contact_id )
{
    $user = wpgh_get_contact_owner( $contact_id );

    if ( ! $user )
        return get_bloginfo( 'admin_email' );

    return $user->user_email;
}

/**
 * Return back the first name of the contact owner.
 *
 * @param $contact_id int the contact
 * @return string the owner's name
 */
function wpgh_replacement_owner_first_name( $contact_id )
{
    $user = wpgh_get_contact_owner( $contact_id );

    if ( ! $user )
        return get_bloginfo( 'admin_email' );

    return $user->first_name;
}

/**
 * Return back the first name of the contact owner.
 *
 * @param $contact_id int the contact
 * @return string the owner's name
 */
function wpgh_replacement_owner_last_name( $contact_id )
{
    $user = wpgh_get_contact_owner( $contact_id );

    if ( ! $user )
        return get_bloginfo( 'admin_email' );

    return $user->last_name;
}

/**
 * Return a confirmation link for the contact
 * This just gets the Optin Page link for now.
 *
 * @return string the optin link
 */
function wpgh_replacement_confirmation_link()
{
    $link_text = get_option( 'gh_confirmation_text', __( 'Confirm your email.', 'groundhogg' ) );
    $link_url = site_url( 'gh-confirmation/via/email/' );

    return sprintf( "<a href=\"%s\" target=\"_blank\">%s</a>", $link_url, $link_text );
}

/**
 * Do the link replacement...
 *
 * @param $linkId int the ID of the link
 *
 * @return string the superlink url
 */
function wpgh_replacement_superlink( $linkId )
{
    $linkId = absint( intval( $linkId ) );
    return site_url( 'superlinks/link/' . $linkId );
}

/**
 * Return a formatted date in local time.
 *
 * @param $time_string
 *
 * @return string
 */
function wpgh_replacement_date( $time_string )
{

    $parts =preg_split( "/(\||;)/", $time_string );

    if ( count( $parts ) === 1 ){
        $format = 'l jS \of F Y';
        $when = $parts[0];
    } else {
        $format = $parts[0];
        $when = $parts[1];
    }

    /* convert to local time */
    $time = strtotime( $when ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

    return date_i18n( $format, $time );

}

/**
 * Return the business name
 *
 * @return string
 */
function wpgh_replacement_business_name()
{
    return get_option( 'gh_business_name' );
}

/**
 * Return eh business phone #
 *
 * @return string
 */
function wpgh_replacement_business_phone()
{
    return get_option( 'gh_phone' );
}

/**
 * Return the business address
 *
 * @return array|string
 */
function wpgh_replacement_business_address()
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