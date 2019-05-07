<?php

namespace Groundhogg;

use WP_Error;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Return if a value in an array isset and is not empty
 *
 * @param $array
 * @param $key
 *
 * @return bool
 */
function isset_not_empty($array, $key='' )
{
    return isset( $array[ $key ] ) && ! empty( $array[ $key ] );
}

/**
 * Get a variable from the $_REQUEST global
 *
 * @param string $key
 * @param bool $default
 * @param bool $post_only
 * @return mixed
 */
function get_request_var( $key='', $default=false, $post_only=false )
{
    $global = $post_only ? $_POST : $_REQUEST;
    return get_array_var( $global, $key, $default );
}

/**
 * Get a variable from an array or default if it doesn't exist.
 *
 * @param $array
 * @param string $key
 * @param bool $default
 * @return bool
 */
function get_array_var( $array, $key='', $default=false )
{
    if ( isset_not_empty( $array, $key ) ){
        return $array[ $key ];
    }

    return $default;
}

/**
 * convert a key to words.
 *
 * @param $key
 * @return string
 */
function key_to_words( $key )
{
    return ucwords( preg_replace( '/[-_]/', ' ', $key ) );
}

/**
 * Convert words to a key
 *
 * @param $words
 * @return string
 */
function words_to_key( $words )
{
    return sanitize_key( str_replace( ' ', '_', $words ) );
}

/**
 * Dequeue WooCommerce style for compatibility
 */
function dequeue_wc_css_compat()
{
    global $wp_styles;
    $maybe_dequeue = $wp_styles->queue;
    foreach ( $maybe_dequeue as $style ){
        if ( strpos( $style, 'woocommerce' ) !== false ){
            wp_dequeue_style( $style );
        }
    }
}

/**
 * Dequeue Theme styles for compatibility
 */
function dequeue_theme_css_compat()
{
    $theme_name = basename( get_stylesheet_directory() );

    // Dequeue Theme Support.
    wp_dequeue_style( $theme_name. '-style' );
    wp_dequeue_style( $theme_name );
    wp_dequeue_style( 'style' );

    // Extra compat.
    global $wp_styles;
    $maybe_dequeue = $wp_styles->queue;
    foreach ( $maybe_dequeue as $style ){
        if ( strpos( $style, $theme_name ) !== false ){
            wp_dequeue_style( $style );
        }
    }
}

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
        do_action( "groundhogg/steps/{$step->type}/import", $import_args, $step );

    }

    return $funnel_id;
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
function remove_builder_toolbar( $content )
{
    return preg_replace( '/<wpgh-toolbar\b[^>]*>(.*?)<\/wpgh-toolbar>/', '', $content );
}

add_filter( 'groundhogg/email/the_content', '\Groundhogg\remove_content_editable' );
//add_filter( 'wpgh_sanitize_email_content', 'wpgh_remove_builder_toolbar' );


/**
 * Remove the content editable attribute from the email's html
 *
 * @param $content string email HTML
 * @return string the filtered email content.
 */
function remove_content_editable( $content )
{
    return preg_replace( "/contenteditable=\"true\"/", '', $content );
}

add_filter( 'groundhogg/email/the_content', '\Groundhogg\remove_content_editable' );
//add_filter( 'wpgh_sanitize_email_content', 'wpgh_remove_content_editable' );

/**
 * Remove script tags from the email content
 *
 * @param $content string the email content
 * @return string, sanitized email content
 */
function strip_script_tags( $content )
{
    return preg_replace( '/<script\b[^>]*>(.*?)<\/script>/', '', $content );
}

//add_filter( 'wpgh_sanitize_email_content', 'strip_script_tags' );

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

//add_filter( 'wpgh_sanitize_email_content', 'wpgh_strip_form_tags' );

/**
 * Add a link to the FB group in the admin footer.
 *
 * @param $text
 * @return string|string[]|null
 */
function add_bug_report_prompt( $text )
{
    if ( apply_filters( 'groundhogg/footer/show_text', true ) ){
        return preg_replace( "/<\/span>/", sprintf( __( ' | Find a bug in Groundhogg? <a target="_blank" href="%s">Report It</a>!</span>' ), __( 'https://www.facebook.com/groups/274900800010203/' ) ), $text );
    }

    return $text;
}

add_filter('admin_footer_text', '\Groundhogg\add_bug_report_prompt');

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

//add_action( 'init', 'wpgh_funnel_share_listen' );

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
 * If the JSON is your typical error response
 *
 * @param $json
 * @return bool
 */
function is_json_error( $json ){
    return isset( $json->code ) && isset( $json->message );
}

/**
 * Convert JSON to a WP_Error
 *
 * @param $json
 * @return bool|WP_Error
 */
function get_json_error( $json ){

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

    if ( ! isset_not_empty( $data, 'orig_error_data' ) ){
        return;
    }

    $code = $data[ 'orig_error_code' ];
    $data = $data[ 'orig_error_data' ];

    if ( $code === 'invalid_recipients' ){

        /* handle bounces */
        $bounces = isset_not_empty( $data, 'bounces' )? $data[ 'bounces' ] : [];

        if ( ! empty( $bounces ) ){
            foreach ( $bounces as $email ){
                if ( $contact = wpgh_get_contact( $email ) ){
                    $contact->change_marketing_preference( WPGH_HARD_BOUNCE );
                }
            }

        }

        $complaints = isset_not_empty( $data, 'complaints' )? $data[ 'complaints' ] : [];

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
//add_filter( 'wp_mail_from', 'wpgh_sender_email' );
//add_filter( 'wp_mail_from_name', 'wpgh_sender_name' );

/**
 * AWS Doesn't like special chars in the from name so we'll strip them out here.
 *
 * @param $name
 * @return string
 */
function sanitize_from_name( $name )
{
    return sanitize_text_field( preg_replace( '/[^A-z0-9 ]/', '', $name ) );
}

///* Pluggable functions */
//if ( ! function_exists( 'wp_mail' ) && wpgh_is_option_enabled( 'gh_send_all_email_through_ghss' ) ):
//
//    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
//        return gh_ss_mail( $to, $subject, $message, $headers, $attachments);
//    }
//
//endif;

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

//if ( wpgh_is_option_enabled( 'gh_send_notifications_on_event_failure' ) ) {
//
//    /**
//     * Send event failure notification.
//     *
//     * @param $event WPGH_Event
//     */
//    function wpgh_send_event_failure_notification($event)
//    {
//        $subject = sprintf("Event (%s) failed for %s", $event->get_step_title(), $event->contact->email);
//        $message = sprintf("This is to let you know that an event \"%s\" in funnel \"%s\" has failed for \"%s (%s)\"", $event->get_step_title(), $event->get_funnel_title(), $event->contact->full_name, $event->contact->email);
//        $message .= sprintf("\nFailure Reason: %s", $event->get_failure_reason());
//        $message .= sprintf("\nManage Failed Events: %s", admin_url('admin.php?page=gh_events&view=status&status=failed'));
//        $to = wpgh_get_option('gh_event_failure_notification_email', get_option('admin_email'));
//        wp_mail($to, $subject, apply_filters('the_content', $message));
//    }
//
//    add_action('groundhogg/event/failed', 'wpgh_send_event_failure_notification');
//}

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

    if (!file_exists($file_path)) {
        return [];
    }

    $header = NULL;
    $data = array();
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        while (($row = fgetcsv($handle, 0, ',')) !== FALSE) {
            if (!$header)
                $header = $row;
            else
                $data[] = array_combine($header, $row);
        }
        fclose($handle);
    }

    return $data;

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