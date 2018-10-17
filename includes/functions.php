<?php
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

    get_option( 'gh_strict_gdpr', array( 'no' ) );

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

            return __( 'Unconfirmed. They will receive emails.', 'groundhogg' );
            break;
        case WPGH_CONFIRMED:
            return __( 'Confirmed. They will receive emails.', 'groundhogg' );
            break;
        case WPGH_UNSUBSCRIBED:
            return __( 'Unsubscribed. They will not receive emails.', 'groundhogg' );
            break;
        case WPGH_WEEKLY:
            return __( 'This contact will only receive emails weekly.', 'groundhogg' );
            break;
        case WPGH_MONTHLY:
            return __( 'This contact will only receive emails monthly.', 'groundhogg' );
            break;
        case WPGH_HARD_BOUNCE:
            return __( 'This email bounced, further emails will not be sent.', 'groundhogg' );
            break;
        case WPGH_SPAM:
            return __( 'This contact was marked as spam. Emails will not be sent.', 'groundhogg' );
            break;
        default:
            return __( 'Unconfirmed. They will receive emails.', 'groundhogg' );
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
 * Provides a quick way to instill a contact session and tie events to a particluar contact.
 *
 * @param $string|int the thing to encrypt/decrypt
 * @param string $action whether to encrypt or decrypt
 * @return bool|string false if failur, the result and success.
 */
function wpgh_encrypt_decrypt( $string, $action = 'e' ) {
    // you may change these values to your own
    $encrypt_method = "AES-256-CBC";

    if ( ! get_option( 'gh_secret_key', false ) )
        update_option( 'gh_secret_key', wp_generate_password() );

    if ( ! get_option( 'gh_secret_iv', false ) )
        update_option( 'gh_secret_iv', wp_generate_password() );

    if ( in_array( $encrypt_method, openssl_get_cipher_methods()) ){
        $secret_key = get_option( 'gh_secret_key' );
        $secret_iv = get_option( 'gh_secret_iv' );

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
    $is_gdpr =  get_option( 'gh_enable_gdpr', array() );

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
    $is_gdpr_strict =  get_option( 'gh_strict_gdpr', array() );

    if ( ! is_array( $is_gdpr_strict ) )
        return false;

    return in_array( 'on', $is_gdpr_strict );
}

function wpgh_is_confirmation_strict()
{
    $is_confirmation_strict =  get_option( 'gh_strict_confirmation', array() );

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

    $grace = intval( get_option( 'gh_confirmation_grace_period', 14 ) ) * 24 * HOUR_IN_SECONDS;

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
 * Return a random quote from the movie groundhog day staring bill murray.
 * Also the movie of which branding is based upon.
 *
 * @return mixed
 */
function wpgh_get_random_groundhogday_quote()
{
    $quotes = array();

    $quotes[] = "I'm not going to live by their rules anymore.";
    $quotes[] = "When Chekhov saw the long winter, he saw a winter bleak and dark and bereft of hope. Yet we know that winter is just another step in the cycle of life. But standing here among the people of Punxsutawney and basking in the warmth of their hearths and hearts, I couldn't imagine a better fate than a long and lustrous winter.";
    $quotes[] = "Hi, three cheeseburgers, two large fries, two milkshakes, and one large coke.";;
    $quotes[] = "It's the same thing every day, Clean up your room, stand up straight, pick up your feet, take it like a man, be nice to your sister, don't mix beer and wine ever, Oh yeah, don't drive on the railroad tracks.";
    $quotes[] = "I'm a god, I'm not the God. I don't think.";
    $quotes[] = "Don't drive angry! Don't drive angry!";
    $quotes[] = "I'm betting he's going to swerve first.";
    $quotes[] = "You want a prediction about the weather? You're asking the wrong Phil. I'm going to give you a prediction about this winter? It's going to be cold, it's going to be dark and it's going to last you for the rest of your lives!";
    $quotes[] = "We mustn't keep our audience waiting.";
    $quotes[] = "Okay campers, rise and shine, and don't forget your booties cause its cold out there...its cold out there every day.";
    $quotes[] = "I peg you as a glass half empty kinda guy.";
    $quotes[] = "Why would anybody steal a groundhog? I can probably think of a couple of reasons... pervert.";
    $quotes[] = "Well, what if there is no tomorrow? There wasn't one today.";
    $quotes[] = "Did he actually refer to himself as \"the talent\"?";
    $quotes[] = "Did you sleep well Mr. Connors?";

    $quotes = apply_filters( 'add_movie_quotes', $quotes );

    $quote = rand( 0, count( $quotes ) - 1 );

    return $quotes[ $quote ];
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
    wp_register_script( 'wpgh-admin-js',   WPGH_ASSETS_FOLDER . 'js/admin/admin.js' );
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
    $recaptcha = get_option( 'gh_enable_recaptcha', array() );
    return is_array( $recaptcha ) && in_array( 'on', $recaptcha );
}