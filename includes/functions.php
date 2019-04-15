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
 * Convert the funnel into a json object so it can be duplicated fairly easily.
 *
 * @todo add to funnel class
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
        $step = wpgh_get_funnel_step( $step->ID );

        $export['steps'][$i] = array();
        $export['steps'][$i]['title'] = $step->title;
        $export['steps'][$i]['group'] = $step->group;
        $export['steps'][$i]['type']  = $step->type;

        $meta = WPGH()->step_meta->get_meta( $step->ID );

        foreach ( $meta as $j => $item ){

            if ( is_array( $item ) ){
                $meta[ $j ] = array( maybe_unserialize( array_shift( $item ) ) );
            } else {
                $meta[ $j ] = maybe_unserialize( $item ) ;
            }

        }

        $export['steps'][$i]['meta']  = $meta;
        $export['steps'][$i]['args']  = apply_filters( 'wpgh_export_step_' . $step->type, array(), $step );
        $export['steps'][$i]['args']  = apply_filters( "groundhogg/elements/{$step->type}/export" , array(), $step );
        /* allow other plugins to modify */
        $export['steps'][$i] = apply_filters( 'wpgh_step_export_args', $export['steps'][$i], $step );
        $export['steps'][$i] = apply_filters( 'groundhogg/elements/step/export', $export['steps'][$i], $step );
    }

    return json_encode( $export );
}

/**
 * Import a funnel
 *
 * @todo add to funnel class
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

        $step = wpgh_get_funnel_step( $step_id );

        do_action( 'wpgh_import_step_' . $step_type, $import_args, $step );
        do_action( "groundhogg/elements/{$step->type}/import", $import_args, $step );

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
        update_option( 'gh_secret_key', bin2hex( openssl_random_pseudo_bytes( 32 ) ) );

    if ( ! wpgh_get_option( 'gh_secret_iv', false ) )
        update_option( 'gh_secret_iv', bin2hex( openssl_random_pseudo_bytes( 16 ) ) );

    if ( in_array( $encrypt_method, openssl_get_cipher_methods()) ){

        $secret_key = wpgh_get_option( 'gh_secret_key' );
        $secret_iv = wpgh_get_option( 'gh_secret_iv' );

        //backwards compat
        if ( ctype_xdigit( $secret_key ) ){
            $secret_key = hex2bin( $secret_key );
            $secret_iv = hex2bin( $secret_iv );
        }

        $output = false;
        $key = substr( hash( 'sha256', $secret_key ), 0, 32 );
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

/**
 * Add a link to the FB group in the admin footer.
 *
 * @param $text
 * @return string|string[]|null
 */
function wpgh_add_bug_report_prompt( $text )
{
    if ( apply_filters( 'groundhogg/footer/show_text', true ) ){
        return preg_replace( "/<\/span>/", sprintf( __( ' | Find a bug in Groundhogg? <a target="_blank" href="%s">Report It</a>!</span>' ), __( 'https://www.facebook.com/groups/274900800010203/' ) ), $text );
    }

    return $text;
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
 * whather we have a token or not.
 *
 * @return bool
 */
function wpgh_has_email_token()
{
    return ( bool ) wpgh_get_option( 'gh_email_token', false );
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

    if ( ! is_array( $option ) && $option ){
        return true;
    }

    //backwards compat

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
 * delete option wrapper
 *
 * @return mixed
 */
function wpgh_delete_option( $key ){
	if ( wpgh_is_global_multisite() ){
		return delete_blog_option( get_network()->site_id, $key );
	} else {
		return delete_option( $key );
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

/**
 * Check if the site is gloabl multisite enabled
 *
 * @return bool
 */
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
 * Array access for existing contact objects...
 *
 * @type WPGH_Contact[]
 */
global $wpgh_funnel_steps_cache;
$wpgh_funnel_steps_cache = [];

/**
 * Simple function to get a contact
 *
 * @since 1.3 return false if contact does not exist
 *
 * @param $id int
 * @return WPGH_Step|false
 */
function wpgh_get_funnel_step( $id, $get_from_cache=true ){

    global $wpgh_funnel_steps_cache;

    $cache_key = md5( $id );

    if ( $get_from_cache && is_array( $wpgh_funnel_steps_cache ) ){
        if (  key_exists( $cache_key, $wpgh_funnel_steps_cache ) ){
            return $wpgh_funnel_steps_cache[ $cache_key ];
        }
    }

    $step = new WPGH_Step( $id );

    if ( $step->exists() ){

        if ( $get_from_cache && is_array( $wpgh_funnel_steps_cache )  ){
            $wpgh_contacts_cache[ $cache_key ] = $step;
        }

        return $step;
    }

    return false;
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

/**
 * Listen for the funnel share link and then perform the download.
 */
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
 * Get a timezone offest.
 *
 * @param string $timeZone
 * @return int
 */
function wpgh_get_timezone_offset( $timeZone = '' )
{
    if ( ! $timeZone ){
        return 0;
    }

    try{
        $timeZone = new DateTimeZone( $timeZone );
    } catch (Exception $e) {
        return 0;
    }

    try{
        $dateTime = new DateTime( 'now', $timeZone );
    } catch ( Exception $e ){
        return 0;
    }

    return $timeZone->getOffset( $dateTime );
}

/**
 * Convert a unix timestamp to local time
 *
 * @param $time
 * @return int
 */
function wpgh_convert_to_local_time($time )
{
    if ( is_string( $time ) ){
        $time = strtotime( $time );
    }

    return $time + ( wpgh_get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
}

/**
 * Backwards compat for dependent extensions.
 */
if ( ! function_exists( 'convert_to_local_time' ) ){
    function convert_to_local_time( $time ){
        return wpgh_convert_to_local_time( $time );
    }
}

/**
 * Converts the given time into the timeZone
 *
 * @param $time int UTC-0 Timestamp
 * @param string $timeZone the timezone to change to
 * @return int UTC-0 TImestamp that reflects the given timezone
 */
function wpgh_convert_to_foreign_time( $time, $timeZone = '' )
{

    if ( ! $timeZone ){
        return $time;
    }

    $time += wpgh_get_timezone_offset( $timeZone );

    return $time;
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
 * Round time to the nearest day.
 *
 * @param $time int
 * @return int
 */
function wpgh_round_to_day( $time ){

    $hours = $time % DAY_IN_SECONDS; # pulls the remainder of the hour.

    $time -= $hours; # just start off rounded down.

    if ($hours >= ( DAY_IN_SECONDS / 2 ) ) $time += DAY_IN_SECONDS; # add one day if 12 hours or higher.

    return $time;
}

/**
 * Create a contact quickly from a user account.
 *
 * @param $user WP_User|int
 * @param $sync_meta bool whether to copy the meta data over.
 * @return WPGH_Contact|false|WP_Error the new contact, false on failure, or WP_Error on error
 */
function wpgh_create_contact_from_user( $user, $sync_meta = false )
{

    if ( is_int( $user ) ) {
        $user = get_userdata( $user );
        if ( ! $user ){
            return false;
        }
    }

    if ( ! $user instanceof WP_User ){
        return false;
    }

    /* Get by email instead of by ID because */
    $contact = wpgh_get_contact( $user->user_email );

    /**
     * Do not continue if the contact already exists. Just return it...
     */
    if ( $contact && $contact->exists() ){
        $contact->update( array( 'user_id' => $user->ID ) );
        return $contact;
    }

    /**
     * Setup the initial args..
     */
    $args = array(
        'first_name'    => $user->first_name,
        'last_name'     => $user->last_name,
        'email'         => $user->user_email,
        'user_id'       => $user->ID,
        'optin_status'  => WPGH_UNCONFIRMED
    );

    if ( empty( $args[ 'first_name' ] ) ){
        $args[ 'first_name' ] = $user->display_name;
    }

    $id = WPGH()->contacts->add( $args );


    if ( ! $id ){
        return new WP_Error( 'BAD_ARGS', __( 'Could not create contact.', 'groundhogg' ) );
    }

    $contact = wpgh_get_contact( $id );

    // Additional stuff.
    $contact->update_meta( 'user_login', $user->user_login );
    $contact->change_marketing_preference( $contact->optin_status );
    $contact->add_tag( wpgh_get_roles_pretty_names( $user->roles ) );

    return $contact;
}

/**
 * Convert an array of roles to n array of display roles
 *
 * @param $roles array an array of user roles...
 * @return array an array of pretty role names.
 */
function wpgh_get_roles_pretty_names( $roles )
{
    $pretty_roles = array();

    foreach ( $roles as $role ){
        $pretty_roles[] = wpgh_get_role_pretty_name( $role );
    }

    return $pretty_roles;
}

/**
 * Get the pretty name of a role
 *
 * @param $role string
 * @return string
 */
function wpgh_get_role_pretty_name( $role )
{
    global $wp_roles;
    return translate_user_role( $wp_roles->roles[ $role ]['name'] );
}

/**
 * Convert a role to a tag name
 *
 * @param $role string the user role
 * @return int the ID of the tag
 */
function wpgh_convert_role_to_tag( $role )
{
    $tags = WPGH()->tags->validate( wpgh_get_role_pretty_name( $role ) );
    return array_shift( $tags );
}

/**
 * When a role is added also add the tag
 *
 * @param $user_id int
 * @param $role string
 */
function wpgh_apply_tags_to_contact_from_new_roles( $user_id, $role )
{
    $contact = wpgh_get_contact( $user_id, true );

    if ( ! $contact || ! $contact->exists() ){
        return;
    }

    $role = wpgh_get_role_pretty_name( $role );
    $contact->add_tag( $role );
}

add_action( 'add_user_role', 'wpgh_apply_tags_to_contact_from_new_roles', 10, 2 );

/**
 * When a role is remove also remove the tag
 *
 * @param $user_id int
 * @param $role string
 */
function wpgh_remove_tags_to_contact_from_remove_roles( $user_id, $role )
{
    $contact = wpgh_get_contact( $user_id, true );
    $role = wpgh_get_role_pretty_name( $role );
    $contact->remove_tag( $role );
}

add_action( 'remove_user_role', 'wpgh_remove_tags_to_contact_from_remove_roles', 10, 2 );

/**
 * When a role is set also set the tag
 *
 * @param $user_id int
 * @param $role string
 * @param $old_roles string[]
 */
function wpgh_apply_tags_to_contact_from_changed_roles( $user_id, $role, $old_roles )
{
    $contact = wpgh_get_contact( $user_id, true );

    if ( ! $contact || ! $contact->exists() ){
        return;
    }

    /**
     * Convert list of roles to a list of tags and remove them...
     */
    $roles = wpgh_get_roles_pretty_names( $old_roles );
    $contact->remove_tag( $roles );

    /**
     * Add the new role as a tag
     */
    $role = wpgh_get_role_pretty_name( $role );
    $contact->add_tag( $role );
}

add_action( 'set_user_role', 'wpgh_apply_tags_to_contact_from_changed_roles', 10, 3 );

/**
 * Provides a global hook not requireing the benchmark anymore.
 *
 * @param $userId int the Id of the user
 */
function wpgh_convert_user_to_contact_when_user_registered( $userId )
{
    $user = get_userdata( $userId );
    $contact = wpgh_create_contact_from_user( $user );

    if ( ! is_admin() ){

        /* register front end which is technically an optin */
        $contact->update_meta( 'last_optin', time() );

    }

    /**
     * Provide hook for the Account Created benchmark and other functionality
     *
     * @param $user WP_User
     * @param $contact WPGH_Contact
     */
    do_action( 'wpgh_user_created', $user, $contact );
}

add_action( 'user_register', 'wpgh_convert_user_to_contact_when_user_registered' );

/**
 * Get quarter $start & end dates...
 *
 * @see https://stackoverflow.com/questions/21185924/get-startdate-and-enddate-for-current-quarter-php
 *
 * @param string $quarter
 * @param null $year
 * @param null $format
 * @return int[]
 * @throws Exception
 */
function wpgh_get_dates_of_quarter($quarter = 'current', $year = null, $format = null)
{
    if ( !is_int($year) ) {
        $year = (new DateTime)->format('Y');
    }
    $current_quarter = ceil((new DateTime)->format('n') / 3);
    switch (  strtolower($quarter) ) {
        case 'this':
        case 'current':
            $quarter = ceil((new DateTime)->format('n') / 3);
            break;

        case 'previous':
            $year = (new DateTime)->format('Y');
            if ($current_quarter == 1) {
                $quarter = 4;
                $year--;
            } else {
                $quarter =  $current_quarter - 1;
            }
            break;

        case 'first':
            $quarter = 1;
            break;

        case 'last':
            $quarter = 4;
            break;

        default:
            $quarter = (!is_int($quarter) || $quarter < 1 || $quarter > 4) ? $current_quarter : $quarter;
            break;
    }
    if ( $quarter === 'this' ) {
        $quarter = ceil((new DateTime)->format('n') / 3);
    }
    $start = new DateTime($year.'-'.(3*$quarter-2).'-1 00:00:00');
    $end = new DateTime($year.'-'.(3*$quarter).'-'.($quarter == 1 || $quarter == 4 ? 31 : 30) .' 23:59:59');

    return array(
        'start' => $start->getTimestamp(),
        'end'   => $end->getTimestamp(),
    );
}

/**
 * Used for blocks...
 *
 * @return array
 */
function wpgh_get_form_list() {

    $forms = WPGH()->steps->get_steps( array(
        'step_type' => 'form_fill'
    ) );
    $form_options = array();
    $default = 0;
    foreach ( $forms as $form ){
        if ( ! $default ){$default = $form->ID;}
        $step = wpgh_get_funnel_step( $form->ID );
        if ( $step->is_active() ){$form_options[ $form->ID ] = $form->step_title;}
    }
    return $form_options;
}

/**
 * Whether or not we should show the stats collection prompt
 *
 * @return bool
 */
function wpgh_should_show_stats_collection()
{
    $show = false;

    if ( ! wpgh_is_option_enabled( 'gh_opted_in_stats_collection' ) && current_user_can( 'manage_options' ) ){
        $show = true;
    }

    return apply_filters( 'groundhogg/stats_collection/show', $show );
}

/**
 * If the JSON is your typical error response
 *
 * @param $json
 * @return bool
 */
function wpgh_is_json_error( $json ){
    return isset( $json->code ) && isset( $json->message );
}

/**
 * Convert JSON to a WP_Error
 *
 * @param $json
 * @return bool|WP_Error
 */
function wpgh_get_json_error( $json ){

    if ( wpgh_is_json_error( $json ) ){
        return new WP_Error( $json->code, $json->message, $json->data );
    }

    return false;
}

/**
 * Schedule a 1 off email notification
 *
 * @param $email_id int the ID of the email to send
 * @param $contact_id_or_email int|string the ID of the contact to send to
 * @param int $time time time to send at, defaults to time()
 *
 * @return bool whether the scheduling was successful.
 */
function wpgh_send_email_notification( $email_id, $contact_id_or_email, $time=0 )
{
    $contact = wpgh_get_contact( $contact_id_or_email );

    if ( ! WPGH()->emails->exists( $email_id ) || ! $contact ){
        return false;
    }

    if ( ! $time ){
        $time = time();
    }

    $event = [
        'time'          => $time,
        'funnel_id'     => 0,
        'step_id'       => $email_id,
        'contact_id'    => $contact->ID,
        'event_type'    => GROUNDHOGG_EMAIL_NOTIFICATION_EVENT,
        'status'        => 'waiting',
    ];

    if ( WPGH()->events->add( $event ) ){
        return true;
    }

    return false;
}

/**
 * Schedule a 1 off sms notification
 *
 * @param $sms_id int the ID of the sms to send
 * @param $contact_id_or_email int|string the ID of the contact to send to
 * @param int $time time time to send at, defaults to time()
 *
 * @return bool whether the scheduling was successful.
 */
function wpgh_send_sms_notification( $sms_id, $contact_id_or_email, $time=0 )
{
    $contact = wpgh_get_contact( $contact_id_or_email );

    if ( ! WPGH()->sms->exists( $sms_id ) || ! $contact ){
        return false;
    }

    if ( ! $time ){
        $time = time();
    }

    $event = [
        'time'          => $time,
        'funnel_id'     => 0,
        'step_id'       => $sms_id,
        'contact_id'    => $contact->ID,
        'event_type'    => GROUNDHOGG_SMS_NOTIFICATION_EVENT,
        'status'        => 'waiting',
    ];

    if ( WPGH()->events->add( $event ) ){
        return true;
    }

    return false;
}

//add_filter( 'groundhogg/templates/emails', 'wpgh_add_my_custom_email_templates' );

/**
 * Include custom email templates
 *
 * @param $email_templates
 * @return mixed
 */
function wpgh_add_my_custom_email_templates( $email_templates ){

    $emails = WPGH()->emails->get_emails( [ 'is_template' => 1 ] );

    foreach ( $emails as $email ){

        $template = [
            'title'          => $email->subject,
            'description'    => $email->pre_header,
            'content'        => $email->content,
        ];

        array_unshift( $email_templates, $template );

    }

    return $email_templates;

}

/**
 * Return if a value in an array isset and is not empty
 *
 * @param $array
 * @param $key
 *
 * @return bool
 */
function gisset_not_empty( $array, $key='' )
{
    return isset( $array[ $key ] ) && ! empty( $array[ $key] );
}

/**
 * Parse the headers and return things like from/to etc...
 *
 * @param $headers string|string[]
 * @return array|false
 */
function wpgh_parse_headers( $headers )
{
    $headers = is_array( $headers ) ? implode( PHP_EOL, $headers ) : $headers;
    if ( ! is_string( $headers ) ){
        return false;
    }

    $parsed = imap_rfc822_parse_headers( $headers );

    if ( ! $parsed ){
        return false;
    }

    $map = [];

    if ( $parsed->sender && ! is_array( $parsed->sender ) ){
        $map[ 'sender' ] = sprintf( '%s@%s', $parsed->sender->mailbox, $parsed->sender->host );
        $map[ 'from' ] = $parsed->sender->personal;
    } else if ( is_array( $parsed->sender ) ){
        $map[ 'sender' ] = sprintf( '%s@%s', $parsed->sender[0]->mailbox, $parsed->sender[0]->host );
        $map[ 'from' ] = $parsed->sender[0]->personal;
    }

    return $map;

}

add_filter("retrieve_password_message", "wpgh_fix_html_pw_reset_link", 10, 4);

/**
 * GHSS doesn't link the <pwlink> format so we have to fix it by removing the gl & lt
 *
 * @param $message
 * @param $key
 * @param $user_login
 * @param $user_data
 * @return string
 */
function wpgh_fix_html_pw_reset_link($message, $key, $user_login, $user_data )    {
    $message = preg_replace( '/<(https?:\/\/.*)>/', '$1', $message );
    return $message;
}

/**
 * Overwrite the regular WP_Mail with an identical function but use our modified PHPMailer class instead
 * which sends the email to the Groundhogg Sending Service.
 *
 * @since 1.2.10
 **
 * @param string|array $to          Array or comma-separated list of email addresses to send message.
 * @param string       $subject     Email subject
 * @param string       $message     Message contents
 * @param string|array $headers     Optional. Additional headers.
 * @param string|array $attachments Optional. Files to attach.
 * @return bool Whether the email contents were sent successfully.
 */
function gh_ss_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
    // Compact the input, apply the filters, and extract them back out

    /**
     * Filters the wp_mail() arguments.
     *
     * @since 2.2.0
     *
     * @param array $args A compacted array of wp_mail() arguments, including the "to" email,
     *                    subject, message, headers, and attachments values.
     */
    $atts = apply_filters( 'wp_mail', compact( 'to', 'subject', 'message', 'headers', 'attachments' ) );

    if ( isset( $atts['to'] ) ) {
        $to = $atts['to'];
    }

    if ( ! is_array( $to ) ) {
        $to = explode( ',', $to );
    }

    if ( isset( $atts['subject'] ) ) {
        $subject = $atts['subject'];
    }

    if ( isset( $atts['message'] ) ) {
        $message = $atts['message'];
    }

    if ( isset( $atts['headers'] ) ) {
        $headers = $atts['headers'];
    }

    if ( isset( $atts['attachments'] ) ) {
        $attachments = $atts['attachments'];
    }

    if ( ! is_array( $attachments ) ) {
        $attachments = explode( "\n", str_replace( "\r\n", "\n", $attachments ) );
    }

    global $phpmailer;

    /* Use the GH SS Mailer class instead */
    if ( ! ( $phpmailer instanceof GH_SS_Mailer ) ) {
        require_once dirname( __FILE__ ) . '/class-gh-ss-mailer.php';
        $phpmailer = new GH_SS_Mailer( true );
    }

    // Headers
    $cc = $bcc = $reply_to = array();

    if ( empty( $headers ) ) {
        $headers = array();
    } else {
        if ( ! is_array( $headers ) ) {
            // Explode the headers out, so this function can take both
            // string headers and an array of headers.
            $tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
        } else {
            $tempheaders = $headers;
        }
        $headers = array();

        // If it's actually got contents
        if ( ! empty( $tempheaders ) ) {
            // Iterate through the raw headers
            foreach ( (array) $tempheaders as $header ) {
                if ( strpos( $header, ':' ) === false ) {
                    if ( false !== stripos( $header, 'boundary=' ) ) {
                        $parts    = preg_split( '/boundary=/i', trim( $header ) );
                        $boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
                    }
                    continue;
                }
                // Explode them out
                list( $name, $content ) = explode( ':', trim( $header ), 2 );

                // Cleanup crew
                $name    = trim( $name );
                $content = trim( $content );

                switch ( strtolower( $name ) ) {
                    // Mainly for legacy -- process a From: header if it's there
                    case 'from':
                        $bracket_pos = strpos( $content, '<' );
                        if ( $bracket_pos !== false ) {
                            // Text before the bracketed email is the "From" name.
                            if ( $bracket_pos > 0 ) {
                                $from_name = substr( $content, 0, $bracket_pos - 1 );
                                $from_name = str_replace( '"', '', $from_name );
                                $from_name = trim( $from_name );
                            }

                            $from_email = substr( $content, $bracket_pos + 1 );
                            $from_email = str_replace( '>', '', $from_email );
                            $from_email = trim( $from_email );

                            // Avoid setting an empty $from_email.
                        } elseif ( '' !== trim( $content ) ) {
                            $from_email = trim( $content );
                        }
                        break;
                    case 'mime-version':
                        // Ensure mime-version does not survive do avoid duplicate header.
                        break;
                    case 'content-type':
                        if ( strpos( $content, ';' ) !== false ) {
                            list( $type, $charset_content ) = explode( ';', $content );
                            $content_type                   = trim( $type );
                            if ( false !== stripos( $charset_content, 'charset=' ) ) {
                                $charset = trim( str_replace( array( 'charset=', '"' ), '', $charset_content ) );
                            } elseif ( false !== stripos( $charset_content, 'boundary=' ) ) {
                                $boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset_content ) );
                                $charset  = '';
                            }

                            // Avoid setting an empty $content_type.
                        } elseif ( '' !== trim( $content ) ) {
                            $content_type = trim( $content );
                        }
                        break;
                    case 'cc':
                        $cc = array_merge( (array) $cc, explode( ',', $content ) );
                        break;
                    case 'bcc':
                        $bcc = array_merge( (array) $bcc, explode( ',', $content ) );
                        break;
                    case 'reply-to':
                        $reply_to = array_merge( (array) $reply_to, explode( ',', $content ) );
                        break;
                    default:
                        // Add it to our grand headers array
                        $headers[ trim( $name ) ] = trim( $content );
                        break;
                }
            }
        }
    }

    // Empty out the values that may be set
    $phpmailer->clearAllRecipients();
    $phpmailer->clearAttachments();
    $phpmailer->clearCustomHeaders();
    $phpmailer->clearReplyTos();

    // From email and name
    // If we don't have a name from the input headers
    if ( ! isset( $from_name ) ) {
        $from_name = 'WordPress';
    }

    /* If we don't have an email from the input headers default to wordpress@$sitename
     * Some hosts will block outgoing mail from this address if it doesn't exist but
     * there's no easy alternative. Defaulting to admin_email might appear to be another
     * option but some hosts may refuse to relay mail from an unknown domain. See
     * https://core.trac.wordpress.org/ticket/5007.
     */

    if ( ! isset( $from_email ) ) {
        // Get the site domain and get rid of www.
        $sitename = strtolower( $_SERVER['SERVER_NAME'] );
        if ( substr( $sitename, 0, 4 ) == 'www.' ) {
            $sitename = substr( $sitename, 4 );
        }

        $from_email = 'wordpress@' . $sitename;
    }

    /**
     * Filters the email address to send from.
     *
     * @since 2.2.0
     *
     * @param string $from_email Email address to send from.
     */
    $from_email = apply_filters( 'wp_mail_from', $from_email );

    /**
     * Filters the name to associate with the "from" email address.
     *
     * @since 2.3.0
     *
     * @param string $from_name Name associated with the "from" email address.
     */
    $from_name = apply_filters( 'wp_mail_from_name', $from_name );

    try {
        $phpmailer->setFrom( $from_email, $from_name, false );
    } catch ( phpmailerException $e ) {
        $mail_error_data                             = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
        $mail_error_data['phpmailer_exception_code'] = $e->getCode();

        /** This filter is documented in wp-includes/pluggable.php */
        do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $e->getMessage(), $mail_error_data ) );

        return false;
    }

    // Set destination addresses, using appropriate methods for handling addresses
    $address_headers = compact( 'to', 'cc', 'bcc', 'reply_to' );

    foreach ( $address_headers as $address_header => $addresses ) {
        if ( empty( $addresses ) ) {
            continue;
        }

        foreach ( (array) $addresses as $address ) {
            try {
                // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
                $recipient_name = '';

                if ( preg_match( '/(.*)<(.+)>/', $address, $matches ) ) {
                    if ( count( $matches ) == 3 ) {
                        $recipient_name = $matches[1];
                        $address        = $matches[2];
                    }
                }

                switch ( $address_header ) {
                    case 'to':
                        $phpmailer->addAddress( $address, $recipient_name );
                        break;
                    case 'cc':
                        $phpmailer->addCc( $address, $recipient_name );
                        break;
                    case 'bcc':
                        $phpmailer->addBcc( $address, $recipient_name );
                        break;
                    case 'reply_to':
                        $phpmailer->addReplyTo( $address, $recipient_name );
                        break;
                }
            } catch ( phpmailerException $e ) {
                continue;
            }
        }
    }

    // Set Content-Type and charset
    // If we don't have a content-type from the input headers
    // Auto set HTML because AWS doesn't like plain text.
    if ( ! isset( $content_type ) ) {
        $content_type = 'text/html';
    }

    /**
     * Filters the wp_mail() content type.
     *
     * @since 2.3.0
     *
     * @param string $content_type Default wp_mail() content type.
     */
    $content_type = apply_filters( 'wp_mail_content_type', $content_type );

    $phpmailer->ContentType = $content_type;

    // Set the content-type and charset
    // Set whether it's plaintext, depending on $content_type
    // GHSS can only send HTML emails apparently. So convert all emails to HTML
    if ( 'text/html' == $content_type ) {
        $phpmailer->isHTML( true );
        $message = apply_filters( 'the_content', $message );
    }

    // Set mail's subject and body
    $phpmailer->Subject = $subject;
    $phpmailer->Body    = $message;

    // If we don't have a charset from the input headers
    if ( ! isset( $charset ) ) {
        $charset = get_bloginfo( 'charset' );
    }


    /**
     * Filters the default wp_mail() charset.
     *
     * @since 2.3.0
     *
     * @param string $charset Default email charset.
     */
    $phpmailer->CharSet = apply_filters( 'wp_mail_charset', $charset );

    // Set custom headers
    if ( ! empty( $headers ) ) {
        foreach ( (array) $headers as $name => $content ) {
            $phpmailer->addCustomHeader( sprintf( '%1$s: %2$s', $name, $content ) );
        }

        if ( false !== stripos( $content_type, 'multipart' ) && ! empty( $boundary ) ) {
            $phpmailer->addCustomHeader( sprintf( "Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary ) );
        }
    }

    if ( ! empty( $attachments ) ) {
        foreach ( $attachments as $attachment ) {
            try {
                $phpmailer->addAttachment( $attachment );
            } catch ( phpmailerException $e ) {
                continue;
            }
        }
    }

    /**
     * Fires after PHPMailer is initialized.
     *
     * @since 2.2.0
     *
     * @param PHPMailer $phpmailer The PHPMailer instance (passed by reference).
     */
    do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );

    // Hard set X-Mailer cuz we taking credit for this.
    $phpmailer->XMailer = sprintf( 'Groundhogg %s (https://www.groundhogg.io)', WPGH()->version );

    // Send!
    try {

        if ( empty( $phpmailer->AltBody ) ){
            $phpmailer->AltBody = wp_strip_all_tags( $message );
        }

        return $phpmailer->send();

    } catch ( phpmailerException $e ) {

        $mail_error_data                             = compact( 'to', 'subject', 'message', 'headers', 'attachments' );
        $mail_error_data['phpmailer_exception_code'] = $e->getCode();
        $mail_error_data['mime_message'] = $phpmailer->getSentMIMEMessage();

        if ( WPGH()->service_manager->has_errors() ){
            $mail_error_data[ 'orig_error_data' ] = WPGH()->service_manager->get_last_error()->get_error_data();
            $mail_error_data[ 'orig_error_message' ] = WPGH()->service_manager->get_last_error()->get_error_message();
            $mail_error_data[ 'orig_error_code' ] = WPGH()->service_manager->get_last_error()->get_error_code();
        }

        /**
         * Fires after a phpmailerException is caught.
         *
         * @since 4.4.0
         *
         * @param WP_Error $error A WP_Error object with the phpmailerException message, and an array
         *                        containing the mail recipient, subject, message, headers, and attachments.
         */
        do_action( 'wp_mail_failed', new WP_Error( 'wp_mail_failed', $e->getMessage(), $mail_error_data ) );

        return false;
    }
}

/**
 * handle a wp_mail_failed event.
 *
 * @param $error WP_Error
 */
function wpgh_parse_complaint_and_bounce_emails( $error )
{
    $data = (array) $error->get_error_data();

    if ( ! gisset_not_empty( $data, 'orig_error_data' ) ){
        return;
    }

    $code = $data[ 'orig_error_code' ];
    $data = $data[ 'orig_error_data' ];

    if ( $code === 'invalid_recipients' ){

        /* handle bounces */
        $bounces = gisset_not_empty( $data, 'bounces' )? $data[ 'bounces' ] : [];

        if ( ! empty( $bounces ) ){
            foreach ( $bounces as $email ){
                if ( $contact = wpgh_get_contact( $email ) ){
                    $contact->change_marketing_preference( WPGH_HARD_BOUNCE );
                }
            }

        }

        $complaints = gisset_not_empty( $data, 'complaints' )? $data[ 'complaints' ] : [];

        if ( ! empty( $complaints ) ){
            foreach ( $complaints as $email ){
                if ( $contact = wpgh_get_contact( $email ) ){
                    $contact->change_marketing_preference( WPGH_COMPLAINED );
                }
            }
        }
    }
}

add_action( 'wp_mail_failed', 'wpgh_parse_complaint_and_bounce_emails' );

/**
 * Override the default from email
 *
 * @param $original_email_address
 * @return mixed
 */
function wpgh_sender_email( $original_email_address ) {

    // Get the site domain and get rid of www.
    $sitename = strtolower( $_SERVER['SERVER_NAME'] );
    if ( substr( $sitename, 0, 4 ) == 'www.' ) {
        $sitename = substr( $sitename, 4 );
    }

    $from_email = 'wordpress@' . $sitename;

    if ( $original_email_address === $from_email ){
        $new_email_address = wpgh_get_option( 'gh_override_from_email', $original_email_address );

        if ( ! empty( $new_email_address ) ){
            $original_email_address = $new_email_address;
        }
    }

    return $original_email_address;
}

/**
 * Override the default from name
 *
 * @param $original_email_from
 * @return mixed
 */
function wpgh_sender_name( $original_email_from ) {

    if( $original_email_from === 'WordPress' ){
        $new_email_from = wpgh_get_option( 'gh_override_from_name', $original_email_from );

        if ( ! empty( $new_email_from ) ){
            $original_email_from = $new_email_from;
        }
    }

    return $original_email_from;
}

// Hooking up our functions to WordPress filters
add_filter( 'wp_mail_from', 'wpgh_sender_email' );
add_filter( 'wp_mail_from_name', 'wpgh_sender_name' );

/**
 * AWS Doesn't like special chars in the from name so we'll strip them out here.
 *
 * @param $name
 * @return string
 */
function wpgh_sanitize_from_name( $name )
{
    return sanitize_text_field( preg_replace( '/&#?[a-z0-9]+;/', '', $name ) );
}

/* Pluggable functions */
if ( ! function_exists( 'wp_mail' ) && wpgh_is_option_enabled( 'gh_send_all_email_through_ghss' ) ):

    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
        return gh_ss_mail( $to, $subject, $message, $headers, $attachments);
    }

endif;

/**
 * This function is for use by any form or eccom extensions which is essentially a copy of the PROCESS method in the submission handler.
 *
 * @param $contact WPGH_Contact
 */
function wpgh_after_form_submit_handler( &$contact )
{

    if ( $contact->update_meta( 'ip_address', wpgh_get_visitor_ip() ) ){
        $contact->extrapolate_location();
    }

    if ( ! $contact->get_meta( 'lead_source' ) ){
        $contact->update_meta( 'lead_source', WPGH()->tracking->lead_source );
    }

    if ( ! $contact->get_meta( 'source_page' ) ){
        $contact->update_meta( 'source_page', wpgh_get_referer()  );
    }

    if ( is_user_logged_in() && ! $contact->user ){
        $contact->update( array( 'user_id' => get_current_user_id() ) );
    }

    if ( $contact->optin_status === WPGH_UNSUBSCRIBED ) {
        $contact->change_marketing_preference( WPGH_UNCONFIRMED );
    }

    $contact->update_meta( 'last_optin', time() );
}

/**
 * Whether the given email address has the same hostname as the current site.
 *
 * @param $email
 * @return bool
 */
function wpgh_email_is_same_domain( $email )
{
    $email_domain = substr( $email, strrpos($email, '@') + 1 );
    $site_domain = site_url();
    return strpos( $site_domain, $email_domain ) !== false;
}

/**
 * Whether SMS is using the GHSS.
 *
 * @return bool
 */
function wpgh_using_ghss_for_sms()
{
    return (bool) apply_filters( 'groundhogg/sms/send_with_ghss', true );
}

/**
 * Whether the ghss is active.
 *
 * @return bool
 */
function wpgh_ghss_is_active()
{
    return (bool) wpgh_get_option( 'gh_email_token', false );
}

/**
 * Notify the admin when credits run low.
 *
 * @param $credits
 */
function wpgh_ghss_notify_low_credit( $credits ){

    if ( $credits > 1000 ){
        return;
    }

    $message = false;
    $subject = false;

    switch ( $credits ) {
        case 1000:
        case 500:
        case 300:
        case 100:
        case 0:
            $subject = sprintf( "Low on Email/SMS credits!" );
            $message = sprintf( "You are running low on credits! Only %s credits remaining. Top up on credits &rarr; https://www.groundhogg.io/downloads/credits/", $credits );
            break;
    }

    if ( $message && $subject ){
        wp_mail( get_bloginfo( 'admin_email' ), $subject, $message );
    }

}

add_action( 'groundhogg/ghss/credits_used', 'wpgh_ghss_notify_low_credit' );
add_action( 'groundhogg/ghss/sms_credits_used', 'wpgh_ghss_notify_low_credit' );

if ( wpgh_is_option_enabled( 'gh_send_notifications_on_event_failure' ) ) {

    /**
     * Send event failure notification.
     *
     * @param $event WPGH_Event
     */
    function wpgh_send_event_failure_notification($event)
    {
        $subject = sprintf("Event (%s) failed for %s", $event->get_step_title(), $event->contact->email);
        $message = sprintf("This is to let you know that an event \"%s\" in funnel \"%s\" has failed for \"%s (%s)\"", $event->get_step_title(), $event->get_funnel_title(), $event->contact->full_name, $event->contact->email);
        $message .= sprintf("\nFailure Reason: %s", $event->get_failure_reason());
        $message .= sprintf("\nManage Failed Events: %s", admin_url('admin.php?page=gh_events&view=status&status=failed'));
        $to = wpgh_get_option('gh_event_failure_notification_email', get_option('admin_email'));
        wp_mail($to, $subject, apply_filters('the_content', $message));
    }

    add_action('groundhogg/event/failed', 'wpgh_send_event_failure_notification');
}

if ( ! function_exists( 'wpgh_split_name' ) ):

/**
 * Split a name into first and last.
 *
 * @param $name
 *
 * @return array
 */
function wpgh_split_name($name) {
	$name = trim($name);
	$last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
	$first_name = trim( preg_replace('#'.$last_name.'#', '', $name ) );
	return array($first_name, $last_name);
}

endif;

/**
 * Get a list of items from a file path, if file does not exist of there are no items return an empty array.
 *
 * @param string $file_path
 * @return array
 */
function wpgh_get_items_from_csv( $file_path='' )
{

    if ( ! file_exists( $file_path ) ){
        return [];
    }

    $header = NULL;
    $data = array();
    if (($handle = fopen($file_path, 'r')) !== FALSE)
    {
        while (($row = fgetcsv($handle, 0, ',')) !== FALSE)
        {
            if(!$header)
                $header = $row;
            else
                $data[] = array_combine($header, $row);
        }
        fclose($handle);
    }

    return $data;

}

/**
 * Get the base uploads path.
 *
 * @return string
 */
function wpgh_get_base_uploads_dir()
{
    $base = 'groundhogg';

    $upload_dir = wp_get_upload_dir();
    $base = $upload_dir[ 'basedir' ] . DIRECTORY_SEPARATOR . $base;

    if ( is_multisite() && ! wpgh_is_global_multisite() ){
        $base .= '/' . get_current_blog_id();
    }

    return wp_normalize_path( apply_filters( 'groundhogg/uploads_path', $base ) );
}

/**
 * Get the base uploads path.
 *
 * @return string
 */
function wpgh_get_base_uploads_url()
{
    $base = 'groundhogg';

    $upload_dir = wp_get_upload_dir();
    $base = $upload_dir[ 'baseurl' ] . '/' . $base;

    if ( is_multisite() && ! wpgh_is_global_multisite() ){
        $base .= '/' . get_current_blog_id();
    }

    return apply_filters( 'groundhogg/uploads_path', $base );
}

/**
 * Generic function for mapping to uploads folder.
 *
 * @param string $subdir
 * @param string $file_path
 * @param bool $create_folders
 * @return string
 */
function wpgh_get_uploads_dir( $subdir='uploads', $file_path='', $create_folders=false )
{
    $path = untrailingslashit( wp_normalize_path( sprintf( "%s/%s/%s", wpgh_get_base_uploads_dir(), $subdir, $file_path ) ) );

    if ( $create_folders ){
        wp_mkdir_p( dirname( $path ) );
    }

    return $path;
}

/**
 * Generic function for mapping to uploads folder.
 *
 * @param string $subdir
 * @param string $file_path
 * @return string
 */
function wpgh_get_uploads_url( $subdir='uploads', $file_path='' )
{
    $path = untrailingslashit( sprintf( "%s/%s/%s", wpgh_get_base_uploads_url(), $subdir, $file_path ) );
    return $path;
}

/**
 * @return string Get the CSV import URL.
 */
function wpgh_get_csv_imports_dir( $file_path='', $create_folders=false ){
    return wpgh_get_uploads_dir( 'imports', $file_path, $create_folders );
}

/**
 * @return string Get the CSV import URL.
 */
function wpgh_get_csv_imports_url( $file_path='' ){
    return wpgh_get_uploads_url( 'imports', $file_path );
}

/**
 * @return string Get the CSV import URL.
 */
function wpgh_get_contact_uploads_dir( $file_path='', $create_folders=false ){
    return wpgh_get_uploads_dir( 'uploads', $file_path, $create_folders );
}

/**
 * @return string Get the CSV import URL.
 */
function wpgh_get_contact_uploads_url( $file_path='' ){
    return wpgh_get_uploads_url( 'uploads', $file_path );
}

/**
 * @return string Get the CSV export URL.
 */
function wpgh_get_csv_exports_dir( $file_path='', $create_folders=false ){
    return wpgh_get_uploads_dir( 'exports', $file_path, $create_folders );
}

/**
 * @return string Get the CSV export URL.
 */
function wpgh_get_csv_exports_url( $file_path='' ){
    return wpgh_get_uploads_url( 'exports', $file_path );
}


/**
 * Get a list of mappable fields as well as extra fields
 *
 * @param array $extra
 * @return array
 */
function wpgh_get_mappable_fields( $extra=[] )
{

    $defaults = [
        'full_name'                 => __( 'Full Name' ),
        'first_name'                => __( 'First Name' ),
        'last_name'                 => __( 'Last Name' ),
        'email'                     => __( 'Email Address' ),
        'optin_status'              => __( 'Optin Status' ),
        'user_id'                   => __( 'User Id' ),
        'owner_id'                  => __( 'Owner Id' ),
        'primary_phone'             => __( 'Phone Number' ),
        'primary_phone_extension'   => __( 'Phone Number Extension' ),
        'street_address_1'          => __( 'Street Address 1' ),
        'street_address_2'          => __( 'Street Address 2' ),
        'city'                      => __( 'City' ),
        'postal_zip'                => __( 'Postal/Zip' ),
        'region'                    => __( 'Province/State/Region' ),
        'country'                   => __( 'Country' ),
        'company_name'              => __( 'Company Name' ),
        'company_address'           => __( 'Full Company Address' ),
        'job_title'                 => __( 'Job Title' ),
        'time_zone'                 => __( 'Time Zone' ),
        'ip_address'                => __( 'IP Address' ),
        'lead_source'               => __( 'Lead Source' ),
        'source_page'               => __( 'Source Page' ),
        'utm_campaign'              => __( 'UTM Campaign' ),
        'utm_content'               => __( 'UTM Content' ),
        'utm_medium'                => __( 'UTM Medium' ),
        'utm_term'                  => __( 'UTM Term' ),
        'utm_source'                => __( 'UTM Source' ),
        'notes'                     => __( 'Add To Notes' ),
        'tags'                      => __( 'Apply Value as Tag' ),
        'meta'                      => __( 'Add as Custom Meta' ),
    ];

    $fields = array_merge( $defaults, $extra );

    return apply_filters( 'groundhogg/mappable_fields', $fields );

}

/**
 * Generate a contact from given associative array and a field map.
 *
 * @param $fields
 * @param $map
 *
 * @return WPGH_Contact|false
 */
function wpgh_generate_contact_with_map( $fields, $map )
{
    $meta = [];
    $tags = [];
    $notes = [];
    $args = [];

    foreach ( $fields as $column => $value ){

        // ignore if we are not mapping it.
        if ( ! key_exists( $column, $map ) ){
            continue;
        }

        $value = wp_unslash( $value );

        $field = $map[ $column ];

        switch ( $field ){
            case 'full_name':
                $parts = wpgh_split_name( $value );
                $args[ 'first_name' ] = sanitize_text_field( $parts[0] );
                $args[ 'last_name' ] = sanitize_text_field( $parts[1] );
                break;
            case 'first_name':
            case 'last_name':
                $args[ $field ] = sanitize_text_field( $value );
                break;
            case 'email':
                $args[ $field ] = sanitize_email( $value );
                break;
            case 'optin_status':
            case 'user_id':
            case 'owner_id':
                $args[ $field ] = absint( $value );
                break;
            case 'primary_phone':
            case 'primary_phone_extension':
            case 'street_address_1' :
            case 'street_address_2':
            case 'city':
            case 'postal_zip':
            case 'region':
            case 'company_name':
            case 'company_address':
            case 'job_title':
            case 'lead_source':
            case 'source_page':
            case 'utm_campaign':
            case 'utm_medium':
            case 'utm_content':
            case 'utm_term':
            case 'utm_source':
                $meta[ $field ] = sanitize_text_field( $value );
                break;
            case 'country':
                if ( strlen( $value ) !== 2 ){
                    $countries = wpgh_get_countries_list();
                    $code = array_search( $value, $countries );
                    if ( $code ){
                        $value = $code;
                    }
                }
                $meta[ $field ] = $value;
                break;
            case 'tags':
                $maybe_tags = explode( ',', $value );
                $tags = array_merge( $tags, $maybe_tags );
                break;
            case 'meta':
                $meta[ get_key_from_column_label( $column ) ] = sanitize_text_field( $value );
                break;
            case 'notes':
                $notes[] = sanitize_textarea_field( $value );
                break;
            case 'time_zone':
                $zones = wpgh_get_time_zones();
                $code = array_search( $value, $zones );
                if ( $code ){
                    $meta[ $field ] = $code;
                }
                break;
            case 'ip_address':
                $ip = filter_var( $value, FILTER_VALIDATE_IP );
                if ( $ip ){
                    $meta[ $field ] = $ip;
                }

                break;
        }

    }

    $id = WPGH()->contacts->add( $args );

    if ( ! $id ){
        return false;
    }

    $contact = wpgh_get_contact( $id );

    if ( ! $contact ){
        return false;
    }

    // Add Tags
    if ( ! empty( $tags ) ){
        $contact->apply_tag( $tags );
    }

    // Add notes
    if ( ! empty( $notes ) ){
        foreach ( $notes as $note ){
            $contact->add_note( $note );
        }
    }

    // update meta data
    if ( ! empty( $meta ) ){
        foreach ( $meta as $key => $value ){
            $contact->update_meta( $key, $value );
        }
    }

    // Run the actions for optin status.
    $contact->change_marketing_preference( $contact->optin_status );
    $contact->update_meta( 'last_optin', time() );

    return $contact;
}

if ( ! function_exists( 'get_key_from_column_label' ) ):

/**
 * Key a key from a column label
 *
 * @param $column
 * @return string
 */
function get_key_from_column_label( $column )
{
    return sanitize_key( str_replace( ' ', '_', $column ) );
}

endif;

if ( ! function_exists( 'multi_implode' ) ):
    function multi_implode( $glue, $array ) {
        $ret = '';

        foreach ($array as $item) {
            if (is_array($item)) {
                $ret .= multi_implode( $glue, $item ) . $glue;
            } else {
                $ret .= $item . $glue;
            }
        }

        $ret = substr($ret, 0, 0-strlen($glue));

        return $ret;
    }
endif;

if ( ! function_exists( 'obfuscate_email' ) ):
/**
 * Obfuscate an email address
 *
 * @param $email
 * @return string|string[]|null
 */
function obfuscate_email( $email )
{
    return preg_replace("/(?!^).(?=[^@]+@)/", "*", $email );
}

endif;