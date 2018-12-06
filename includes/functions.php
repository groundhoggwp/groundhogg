<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-04
 * Time: 5:10 PM
 */

define( 'WPGH_BROADCAST'    , 1 );
define( 'WPGH_UNCONFIRMED'  , 0 );
define( 'WPGH_CONFIRMED'    , 1 );
define( 'WPGH_UNSUBSCRIBED' , 2 );
define( 'WPGH_WEEKLY'       , 3 );
define( 'WPGH_MONTHLY'      , 4 );
define( 'WPGH_HARD_BOUNCE'  , 5 );
define( 'WPGH_SPAM'         , 6 );
define( 'WPGH_COMPLAINED'   , 7 );

/**
 * Return the FULL URI from wp_get_referer for string comparisons
 *
 * @return string
 */
function wpgh_get_referer()
{
    if ( ! isset( $_POST[ '_wp_http_referer' ]  ) )
        return wp_get_referer();

	return ( is_ssl() ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}" . $_REQUEST[ '_wp_http_referer' ];
}

/**
 * Get the text explanation for the optin status of a contact
 * 0 = unconfirmed, can send email
 * 1 = confirmed, can send email
 * 2 = opted out, can't send email
 *
 * @param $id_or_email int|string the contact in question
 *
 * @return bool|string
 */
function wpgh_get_optin_status_text( $id_or_email )
{
    $contact = new WPGH_Contact( $id_or_email );

    if ( ! $contact->email )
        return __( 'No Contact' );

    wpgh_get_option( 'gh_strict_gdpr', array( 'no' ) );

    if ( wpgh_is_gdpr() && wpgh_is_gdpr_strict() )
    {
        $consent = WPGH()->contact_meta->get_meta( $contact->ID, 'gdpr_consent', true );

        if ( $consent !== 'yes' )
            return __( 'This contact has not agreed to receive email marketing from you.', 'groundhogg' );
    }

    switch ( $contact->optin_status ){

        case WPGH_UNCONFIRMED:

            if ( wpgh_is_confirmation_strict() )
            {
                if ( ! wpgh_is_in_grace_period( $contact->ID ) )
                    return __( 'Unconfirmed. This contact will not receive emails, they are passed the email confirmation grace period.', 'groundhogg' );
            }

            return __( 'Unconfirmed. They will receive marketing.', 'groundhogg' );
            break;
        case WPGH_CONFIRMED:
            return __( 'Confirmed. They will receive marketing.', 'groundhogg' );
            break;
        case WPGH_UNSUBSCRIBED:
            return __( 'Unsubscribed. They will not receive marketing.', 'groundhogg' );
            break;
        case WPGH_WEEKLY:
            return __( 'This contact will only receive marketing weekly.', 'groundhogg' );
            break;
        case WPGH_MONTHLY:
            return __( 'This contact will only receive marketing monthly.', 'groundhogg' );
            break;
        case WPGH_HARD_BOUNCE:
            return __( 'This email address bounced, they will not receive marketing.', 'groundhogg' );
            break;
        case WPGH_SPAM:
            return __( 'This contact was marked as spam. They will not receive marketing.', 'groundhogg' );
            break;
        case WPGH_COMPLAINED:
            return __( 'This contact complained about your emails. They will not receive marketing.', 'groundhogg' );
            break;
        default:
            return __( 'Unconfirmed. They will receive marketing.', 'groundhogg' );
            break;
    }
}

/**
 * Convert the funnel into a json object so it can be duplicated fairly easily.
 *
 * @param $funnel_id int the ID of the funnel to convert.
 * @return false|string the json string of a converted funnel or false on failure.
 */
function wpgh_convert_funnel_to_json( $funnel_id )
{
    if ( ! $funnel_id || ! is_int( $funnel_id) )
        return false;

    $funnel = WPGH()->funnels->get_funnel( $funnel_id );

    if ( ! $funnel )
        return false;

    $export = array();

    $export['title'] = $funnel->title;

    $export[ 'steps' ] = array();

    $steps = WPGH()->steps->get_steps( array( 'funnel_id' => $funnel->ID ) );

    if ( ! $steps )
        return false;

    foreach ( $steps as $i => $step )
    {
        $step = new WPGH_Step( $step->ID );

        $export['steps'][$i] = array();
        $export['steps'][$i]['title'] = $step->title;
        $export['steps'][$i]['group'] = $step->group;
        $export['steps'][$i]['type']  = $step->type;
        $export['steps'][$i]['meta']  = WPGH()->step_meta->get_meta( $step->ID );
        $export['steps'][$i]['args']  = apply_filters( 'wpgh_export_step_' . $step->type, array(), $step );
        /* allow other plugins to modify */
        $export['steps'][$i] = apply_filters( 'wpgh_step_export_args', $export['steps'][$i], $step );
    }

    return json_encode( $export );
}

/**
 * Import a funnel
 *
 * @return bool|int
 */
function wpgh_import_funnel( $import )
{
    if ( is_string( $import ) ){
        $import = json_decode( $import, true );
    }

    if ( ! is_array( $import ) )
        return false;

    $title = $import[ 'title' ];

    $funnel_id = WPGH()->funnels->add( array( 'title' => $title, 'status' => 'inactive', 'author' => get_current_user_id() ) );

    $steps = $import[ 'steps' ];

    $valid_actions = WPGH()->elements->get_actions();
    $valid_benchmarks = WPGH()->elements->get_benchmarks();

    foreach ( $steps as $i => $step_args )
    {

        $step_title = $step_args['title'];
        $step_group = $step_args['group'];
        $step_type  = $step_args['type'];

        if ( ! isset( $valid_actions[$step_type] ) && ! isset( $valid_benchmarks[$step_type] ) )
            continue;

        $args = array(
            'funnel_id' => $funnel_id,
            'step_title'     => $step_title,
            'step_status'    => 'ready',
            'step_group'     => $step_group,
            'step_type'      => $step_type,
            'step_order'     => $i+1,
        );

        $step_id = WPGH()->steps->add( $args );

        $step_meta = $step_args[ 'meta' ];

//        var_dump( $step_meta );

        foreach ( $step_meta as $key => $value ) {
            if ( is_array( $value ) ){
                WPGH()->step_meta->update_meta( $step_id, $key, array_shift( $value ) );
            } else {
                WPGH()->step_meta->update_meta( $step_id, $key, $value );
            }
        }

        $import_args = $step_args[ 'args' ];

        $step = new WPGH_Step( $step_id );

        do_action( 'wpgh_import_step_' . $step_type, $import_args, $step );

    }

    return $funnel_id;
}


/**
 * Provides a quick way to instill a contact session and tie events to a particluar contact.
 *
 * @param $string|int the thing to encrypt/decrypt
 * @param string $action whether to encrypt or decrypt
 * @return bool|string false if failur, the result and success.
 */
function wpgh_encrypt_decrypt( $string, $action = 'e' ) {
    // you may change these values to your own
    $encrypt_method = "AES-256-CBC";

    if ( ! wpgh_get_option( 'gh_secret_key', false ) )
        update_option( 'gh_secret_key', wp_generate_password() );

    if ( ! wpgh_get_option( 'gh_secret_iv', false ) )
        update_option( 'gh_secret_iv', wp_generate_password() );

    if ( in_array( $encrypt_method, openssl_get_cipher_methods()) ){
        $secret_key = wpgh_get_option( 'gh_secret_key' );
        $secret_iv = wpgh_get_option( 'gh_secret_iv' );

        $output = false;
        $key = hash( 'sha256', $secret_key );
        $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

        if( $action == 'e' ) {
            $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
        }
        else if( $action == 'd' ){
            $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
        }
    } else {
        if( $action == 'e' ) {
            $output = base64_encode( $string );
        }
        else if( $action == 'd' ){
            $output = base64_decode( $string );
        }
    }

    return $output;
}

/**
 * Get the IP address of the current visitor
 *
 * @return string the IP of a vsitor.
 */
function wpgh_get_visitor_ip() {

    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        //check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        //to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return apply_filters( 'wpgh_get_ip', $ip );

}

/**
 * Check if GDPR is enabled throughout the plugin.
 *
 * @return bool, whether it's enable or not.
 */
function wpgh_is_gdpr()
{
    $is_gdpr =  wpgh_get_option( 'gh_enable_gdpr', array() );

    if ( ! is_array( $is_gdpr ) )
        return false;

    return in_array( 'on', $is_gdpr );
}

/**
 * check if the GDPR strict option is enabled
 *
 * @return bool
 */
function wpgh_is_gdpr_strict()
{

    $is_gdpr_strict =  wpgh_get_option( 'gh_strict_gdpr', array() );

    if ( ! is_array( $is_gdpr_strict ) )
        return false;

    return in_array( 'on', $is_gdpr_strict );
}

function wpgh_is_confirmation_strict()
{

    $is_confirmation_strict =  wpgh_get_option( 'gh_strict_confirmation', array() );

    if ( ! is_array( $is_confirmation_strict ) )
        return false;

    return in_array( 'on', $is_confirmation_strict );
}

/**
 * Return whether the given contact is within the strict confirmation grace period
 *
 * @param $contact_id
 * @return bool
 */
function wpgh_is_in_grace_period( $contact_id )
{

    $contact = new WPGH_Contact( $contact_id );

    $grace = intval( wpgh_get_option( 'gh_confirmation_grace_period', 14 ) ) * 24 * HOUR_IN_SECONDS;

    $base = WPGH()->contact_meta->get_meta( $contact_id, 'last_optin', true );

    if ( ! $base )
    {
        $base = strtotime( $contact->date_created );
    }

    $time_passed = time() - $base;

    return $time_passed < $grace;
}


/**
 * Extract the funnel ID from a link, only for use in ADMIN funnel editor.
 *
 * @param $link string link from the funnel editor page
 *
 * @return int|false the funnel ID, false otherwise
 */
function wpgh_extract_query_arg( $link, $arg = '' )
{

    $queryString = parse_url( $link, PHP_URL_QUERY );

    $queryArgs = explode( '&', $queryString );

    foreach ( $queryArgs as $args ){

        $subArgs = explode( '=' , $args );
        if ( $subArgs[0] == $arg ){
            return intval( $subArgs[1] );
        }

    }

    return false;
}

/**
 * Remove the editing toolbar from the email content so it doesn't show up in the client's email.
 *
 * @param $content string the email content
 *
 * @return string the new email content.
 */
function wpgh_remove_builder_toolbar( $content )
{
    return preg_replace( '/<wpgh-toolbar\b[^>]*>(.*?)<\/wpgh-toolbar>/', '', $content );
}

add_filter( 'wpgh_the_email_content', 'wpgh_remove_builder_toolbar' );
add_filter( 'wpgh_sanitize_email_content', 'wpgh_remove_builder_toolbar' );


/**
 * Remove the content editable attribute from the email's html
 *
 * @param $content string email HTML
 * @return string the filtered email content.
 */
function wpgh_remove_content_editable( $content )
{
    return preg_replace( "/contenteditable=\"true\"/", '', $content );
}

add_filter( 'wpgh_the_email_content', 'wpgh_remove_content_editable' );
add_filter( 'wpgh_sanitize_email_content', 'wpgh_remove_content_editable' );

/**
 * Strip out irrelevant whitespace form the html.
 *
 * @param $content string
 * @return string
 */
function wpgh_minify_html( $content )
{
    $search = array(
        '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
        '/[^\S ]+\</s',     // strip whitespaces before tags, except space
        '/(\s)+/s',         // shorten multiple whitespace sequences
        '/<!--(.|\s)*?-->/' // Remove HTML comments
    );

    $replace = array(
        '>',
        '<',
        '\\1',
        ''
    );

    $buffer = preg_replace($search, $replace, $content);

    return $buffer;
}

add_filter( 'wpgh_the_email_content', 'wpgh_minify_html' );

/**
 * Remove script tags from the email content
 *
 * @param $content string the email content
 * @return string, sanitized email content
 */
function wpgh_strip_script_tags( $content )
{
    return preg_replace( '/<script\b[^>]*>(.*?)<\/script>/', '', $content );
}

add_filter( 'wpgh_sanitize_email_content', 'wpgh_strip_script_tags' );

/**
 * Remove form tags from emails.
 *
 * @param $content string the email content
 * @return string, sanitized email content
 */
function wpgh_strip_form_tags( $content )
{
    return preg_replace( '/<form\b[^>]*>(.*?)<\/form>/', '', $content );
}

add_filter( 'wpgh_sanitize_email_content', 'wpgh_strip_form_tags' );

/**
 * Output the contents of an email if clicking the view in browser link.
 */
function wpgh_view_email_in_browser()
{
    if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/gh-email/' ) === false )
        return;

    $email = new WPGH_Email( intval( $_REQUEST[ 'email' ] ) );

    $contact = WPGH()->tracking->get_contact();

    if ( ! $contact || ! $contact->email )
    {
        wp_die( 'no contact' );
        return;
    }

    if ( ! $email->exists() )
    {
        wp_die( 'no email' );
        return;
    }

    $email->contact = $contact;

    wp_die( $email->get_content(), $email->get_subject_line() );
}

add_action( 'template_redirect', 'wpgh_view_email_in_browser' );

function wpgh_register_scripts()
{
    wp_register_style( 'jquery-ui', WPGH_ASSETS_FOLDER . 'lib/jquery-ui/jquery-ui.min.css' );
    wp_register_style( 'select2',   WPGH_ASSETS_FOLDER . 'lib/select2/css/select2.min.css' );
    wp_register_script( 'select2',  WPGH_ASSETS_FOLDER . 'lib/select2/js/select2.full.js'   , array( 'jquery' ) );
    wp_register_script( 'wpgh-admin-js',   WPGH_ASSETS_FOLDER . 'js/admin/admin.js', array( 'jquery' ), filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/admin.js' ) );
    wp_register_script( 'wpgh-queue',   WPGH_ASSETS_FOLDER . 'js/admin/queue.js', array( 'jquery' ), filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/queue.js' ) );
}

add_action( 'admin_enqueue_scripts', 'wpgh_register_scripts' );

function wpgh_add_bug_report_prompt( $text )
{
    return preg_replace( "/<\/span>/", sprintf( __( ' | Find a bug in Groundhogg? <a target="_blank" href="%s">Report It</a>!</span>' ), __( 'https://www.facebook.com/groups/274900800010203/' ) ), $text );
}
add_filter('admin_footer_text', 'wpgh_add_bug_report_prompt');

/**
 * Converts an array of tag IDs to a Select 2 friendly format.
 *
 * @param array $tags
 * @return array|false
 */
function wpgh_format_tags_for_select2( $tags=array() )
{

    if ( ! is_array( $tags ) )
        return false;

    $json = array();

    foreach ( $tags as $i => $tag ) {
        $tag = WPGH()->tags->get_tag( $tag );
        $json[] = array(
            'id' => $tag->tag_id,
            'text' => sprintf( "%s (%s)", $tag->tag_name, $tag->contact_count )
        );
    }

    return $json;

}

/**
 * Check if Recaptcha is enabled throughout the plugin.
 *
 * @return bool, whether it's enable or not.
 */
function wpgh_is_recaptcha_enabled(){

    $recaptcha = wpgh_get_option( 'gh_enable_recaptcha', array() );

    return is_array( $recaptcha ) && in_array( 'on', $recaptcha );
}

/**
 * Check if the email API is enabled throughout the plugin.
 *
 * @return bool, whether it's enable or not.
 */
function wpgh_is_email_api_enabled(){

    $recaptcha = wpgh_get_option( 'gh_send_with_gh_api', array() );

    return is_array( $recaptcha ) && in_array( 'on', $recaptcha );
}

/**
 * Generic function for checking checkboxes from the Groundhogg settings.
 *
 * @param string $key
 * @return bool
 */
function wpgh_is_option_enabled( $key = '' )
{
    $option = wpgh_get_option( $key, array() );
    return is_array( $option ) && in_array( 'on', $option );
}

/**
 * Swicth between the main site options if on a multisite network.
 *
 * @param $key
 * @param bool $default
 *
 * @return mixed
 */
function wpgh_get_option( $key, $default=false )
{

    if ( wpgh_is_global_multisite() ){
        return get_blog_option( get_network()->site_id, $key, $default );
    } else {
        return get_option( $key, $default );
    }

}

/**
 * update option wrapper
 *
 * @return mixed
 */
function wpgh_update_option( $key, $value ){
    if ( wpgh_is_global_multisite() ){
        return update_blog_option( get_network()->site_id, $key, $value );
    } else {
        return update_option( $key, $value );
    }
}

/**
 * get_transient wrapper
 *
 * @param $key
 * @return mixed
 */
function wpgh_get_transient( $key ){
    if ( wpgh_is_global_multisite() ){
        return get_site_transient( $key );
    } else {
        return get_transient( $key );
    }
}

/**
 * delete_transient wrapper
 *
 * @param $key
 * @return mixed
 */
function wpgh_delete_transient( $key ){
    if ( wpgh_is_global_multisite() ){
        return delete_site_transient( $key );
    } else {
        return delete_transient( $key );
    }
}

/**
 * Set transient wrapper
 *
 * @param $key
 * @param $value
 * @param $exp
 * @return bool
 */
function wpgh_set_transient( $key, $value, $exp ){
    if ( wpgh_is_global_multisite() ){
        return set_site_transient( $key, $value, $exp );
    } else {
        return set_transient( $key, $value, $exp );
    }
}

/**
 * Protect MAIN functionality by this multisite check.
 *
 * @return bool
 */
function wpgh_should_if_multisite()
{

    if ( ! is_multisite() ){
        return true;
    }

    if ( is_multisite() && ! get_site_option( 'gh_global_db_enabled' ) ){
        return true;
    }

    if ( is_multisite() && get_site_option( 'gh_global_db_enabled' ) && is_main_site() ){
        return true;
    }

    return false;

}

function wpgh_is_global_multisite()
{
    if ( ! is_multisite() ){
        return false;
    }

    if ( is_multisite() && ! get_site_option( 'gh_global_db_enabled' ) ){
        return false;
    }

    return true;
}

/**
 * Return the current user role.
 *
 * @return array|bool
 */
function wpgh_get_current_user_roles()
{

    if ( ! is_user_logged_in() )
        return false;

    $user = wp_get_current_user();

    $roles = (array) $user->roles;

//    if ( count( $roles ) === 1 ){
//
//        return $roles[0];
//
//    }

    return $roles;

}

/**
 * Simple function to get a contact
 *
 * @since 1.0.6
 *
 * @param $id_or_email string|int
 * @return WPGH_Contact
 */
function wpgh_get_contact( $id_or_email ){
    return new WPGH_Contact( $id_or_email );
}

/**
 * Recount the contacts per tag...
 */
function wpgh_recount_tag_contacts_count()
{
    /* Recount tag relationships */
    $tags = WPGH()->tags->get_tags();

    if ( ! empty( $tags ) ){
        foreach ( $tags as $tag ){
            $count = WPGH()->tag_relationships->count( $tag->tag_id, 'tag_id' );
            WPGH()->tags->update( $tag->tag_id, array( 'contact_count' => $count ) );
        }
    }
}


function wpgh_funnel_share_listen()
{
    if ( isset( $_GET[ 'funnel_share' ] ) ) {

        $key = urldecode( $_GET[ 'funnel_share' ] );
        $id = intval( wpgh_encrypt_decrypt( $key, 'd' ) );
        if ( WPGH()->funnels->exists( $id ) ){

            $funnel = WPGH()->funnels->get_funnel( $id );

            if ( ! $funnel )
                return;

            $export_string = wpgh_convert_funnel_to_json( $id );

            if ( ! $export_string )
                return;

            $filename = $funnel->title . ' - '. date("Y-m-d_H-i", time() );

            header("Content-type: text/plain");

            header( "Content-disposition: attachment; filename=".$filename.".funnel");

            $file = fopen('php://output', 'w');

            fputs( $file, $export_string );

            fclose($file);

            exit();
        }

    }
}

add_action( 'init', 'wpgh_funnel_share_listen' );

/**
 * Convert a unix timestamp to UTC-0 time
 *
 * @param $time
 * @return int
 */
function wpgh_convert_to_utc_0( $time )
{
    if ( is_string( $time ) ){
        $time = strtotime( $time );
    }

    return $time - ( wpgh_get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

}

/**
 * Convert a unix timestamp to local time
 *
 * @param $time
 * @return int
 */
function convert_to_local_time( $time )
{
    if ( is_string( $time ) ){
        $time = strtotime( $time );
    }

    return $time + ( wpgh_get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
}

/**
 * Round time to the nearest hour.
 *
 * @param $time int
 * @return int
 */
function wpgh_round_to_hour( $time ){

    $minutes = $time % HOUR_IN_SECONDS; # pulls the remainder of the hour.

    $time -= $minutes; # just start off rounded down.

    if ($minutes >= ( HOUR_IN_SECONDS / 2 ) ) $time += HOUR_IN_SECONDS; # add one hour if 30 mins or higher.

    return $time;
}

/**
 * Round time to the nearest hour.
 *
 * @param $time int
 * @return int
 */
function wpgh_round_to_day( $time ){

    $minutes = $time % DAY_IN_SECONDS; # pulls the remainder of the hour.

    $time -= $minutes; # just start off rounded down.

    if ($minutes >= ( DAY_IN_SECONDS / 2 ) ) $time += DAY_IN_SECONDS; # add one hour if 30 mins or higher.

    return $time;
}
