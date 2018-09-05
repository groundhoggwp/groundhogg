<?php
/**
 * Emailing Functions
 *
 * Anything to do with saving, manipulating, and running email functions in the event queue
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Return the html tags allowed in emails
 *
 * @return array the allowed HTML in emails
 */
function wpgh_emails_allowed_html()
{
	//todo define custom HTML array.

	$allowed_tags = wp_kses_allowed_html();
	return $allowed_tags;
}

/**
 * Send an email to a contact.
 * Replaces all links in email content with new links that direct to site containing information about the
 * funnel, email, and step the email was sent from if applicable.
 * uses the "ref" parameter to send the contact to the intent destination.
 *
 * @param $contact_id int the ID of the contact the email is being sent to.
 * @param $email_id int the ID of the emai to send
 * @param $step_id int the ID of the step the email is being sent from
 * @param $funnel_id int the ID of the funnel the step is in (maybe required)
 *
 * @return bool true on success, false on failure
 */
function wpgh_send_email( $contact_id, $email_id, $funnel_id=null, $step_id=null )
{

    if ( ! $contact_id || ! $email_id  )
        return false;

    $email_id = absint( intval( $email_id ) );

    $contact = new WPGH_Contact( $contact_id );

    /* if the email is a dud, give up. */
    if ( ! is_email( $contact->get_email() ) )
        return false;

    /* don't send email depending on their optin status */
    if ( ! wpgh_can_send_email( $contact->get_id() ) )
        return false;

    $email = wpgh_get_email_by_id( $email_id );

    /* don't send if the email is marked as unready. */
    if ( $email->email_status !== 'ready' && ! isset( $_POST['send_test'] ) )
        return false;

    /* @var $link_args array array of $_GET args that will be used to run analytics actions on the site */
    $link_args = array();

    if ( $funnel_id && is_int( $funnel_id ) )
        $link_args['funnel'] = absint( $funnel_id );

    if ( $step_id && is_int( $step_id ) )
        $link_args['step'] = absint( $step_id );

    $link_args['contact'] = wpgh_encrypt_decrypt( $contact->get_id(), 'e' );
    $link_args['email'] = $email_id;

    /* @var $ref_link string link containing all relevant tracking info, prepared to be appended with a url encoded link that the contact was originally intended to be sent to. */

    $ref_link = add_query_arg( $link_args, site_url( 'gh-tracking/email/click/' ) ) . '&ref=';

    /* merge in email content into default template */
    $tracking_link = add_query_arg( $link_args, site_url( 'gh-tracking/email/open/' ) );

    $title = get_option( 'gh_business_name' );

    $subject_line = wpgh_do_replacements( $contact->get_id(), $email->subject );

    $pre_header = wpgh_do_replacements( $contact->get_id(), $email->pre_header );

    $content = apply_filters( 'wpgh_the_email_content', wpgh_do_replacements( $contact->get_id(), $email->content ) );

    $email_footer_text = wpgh_get_email_footer_text();

    $unsubscribe_link = get_permalink( get_option( 'gh_email_preferences_page' ) );

    $alignment = wpgh_get_email_meta( $email_id, 'alignment', true );

    if ( $alignment === 'left' ){
        $margins = "margin-left:0;margin-right:auto;";
    } else {
        $margins = "margin-left:auto;margin-right:auto;";
    }

    ob_start();

    include dirname( __FILE__ ) . '/templates/email.php';

    $email_content = ob_get_contents();

    ob_end_clean();

    /* Filter the links to include data about the email, campaign, and funnel steps... */
    $email_content = preg_replace_callback( '/(href=")([^"]*)(")/i', 'wpgh_urlencode_email_links' , $email_content );
    $email_content = preg_replace( '/(href=")([^"]*)(")/i', '${1}' . $ref_link . '${2}${3}' , $email_content );

    $from_user = get_userdata( $email->from_user );

    /* use groundhogg API mail service */
    if ( get_option( 'gh_mail_server', false ) === 'groundhogg' ){

        $body = array(
            'from_name' => $from_user->display_name,
            'from_email' => $from_user->user_email,
            'to'   => $contact->get_email(),
            'subject_line' => $subject_line,
            'content' => $email_content
        );

        $will_send = GH_Account::$instance->post( 'email', $body );

        if ( is_wp_error( $will_send ) )
        {
            return false;
        }
    } else {
        /* Use default mail-server */
        $headers = array();
        $headers[ 'from' ] = 'From: ' . $from_user->display_name . ' <' . $from_user->user_email . '>';
        $headers[ 'reply_to' ] = 'Reply-To: ' . $from_user->user_email;
        $headers[ 'content_type' ] = 'Content-Type: text/html; charset=UTF-8';

        $headers = apply_filters( 'wpgh_email_headers', $headers );

        add_filter( 'wp_mail_content_type', 'wpgh_send_html_email' );

        $sent = wp_mail( $contact->get_email() , $subject_line, $email_content, $headers );

        if ( is_wp_error( $sent ) || ! $sent ){
            return false;
        }
    }

    wpgh_update_contact_meta( $contact->get_id(), 'last_sent', time() );

    return true;
}


/**
 * Get the can spam compliant email footer.
 *
 * @return string the email footer
 */
function wpgh_get_email_footer_text()
{
    $footer = "";

    if ( get_option( 'gh_business_name' ) )
        $footer .= "&copy; " . get_option( 'gh_business_name' ) . "<br/>" ;

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

    $footer .= implode( ', ', $address ) . "<br/>";

    $sub = array();
    if ( get_option( 'gh_phone', 0 ) )
        $sub[] = "<a href='tel:" . esc_attr( get_option( 'gh_phone' ) ) . "'>" . esc_attr( get_option( 'gh_phone' ) ) . "</a>";
    if ( get_option( 'gh_privacy_policy' ) )
        $sub[] = "<a href=\"" . esc_attr( get_permalink( get_option( 'gh_privacy_policy' ) ) ) . "\">" . __( 'Privacy Policy', 'groundhogg' ) . "</a>";
    if ( get_option( 'gh_terms', 0 ) )
        $sub[] = "<a href=\"" . esc_attr( get_permalink( get_option( 'gh_terms' ) ) ) . "\">" . __( 'Terms', 'groundhogg' ) . "</a>";

    $footer .= implode( ' | ', $sub ) ;

    $footer = apply_filters( 'wpgh_email_footer', $footer );

    return $footer;
}

/**
 * PRE URL encode email links for the ref passage
 *
 * @param $matches array
 * @return string
 */
function wpgh_urlencode_email_links( $matches )
{
    return $matches[1] . urlencode( $matches[2] ) . $matches[3];
}

/**
 * Convert to HTML email
 *
 * @return string the content type for the email
 */
function wpgh_send_html_email()
{
    return 'text/html';
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
    return str_replace( 'contenteditable="true" ', '', $content );
}

add_filter( 'wpgh_the_email_content', 'wpgh_remove_content_editable' );

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
 * Queue the email in the event queue. Does Basically it runs immediately but is queued for the sake of semantics.
 *
 * @param $step_id int The Id of the step
 * @param $contact_id int the Contact's ID
 */
function wpgh_enqueue_send_email_action( $step_id, $contact_id )
{
    $funnel_id = wpgh_get_step_funnel( $step_id );
    wpgh_enqueue_event( time() + 10, $funnel_id,  $step_id, $contact_id );
}

add_action( 'wpgh_enqueue_next_funnel_action_send_email', 'wpgh_enqueue_send_email_action', 10, 2 );

/**
 * Process the email action step sending and then queue up the next action in the funnel.
 *
 * @param $step_id int the email step's id
 * @param $contact_id int The contact's ID
 *
 * @return bool, whether the email was sent successfully
 */
function wpgh_do_send_email_action( $step_id, $contact_id )
{
    $email_id = wpgh_get_step_meta( $step_id, 'email_id', true );
    return wpgh_send_email( intval( $contact_id ), intval( $email_id ), wpgh_get_step_funnel( $step_id ), $step_id );
}

add_action( 'wpgh_do_action_send_email', 'wpgh_do_send_email_action', 10, 2 );


/**
 * Get a dropdown of all the available emails
 * rudementary copy of wp_dropdown_pages
 *
 * @return array list of available emails
 */
function wpgh_dropdown_emails( $args )
{
    wp_enqueue_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css' );
    wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js' );

    $defaults = array(
        'selected' => 0, 'echo' => 1,
        'name' => 'email_id', 'id' => '',
        'class' => '', 'width' => '100%',
        'show_option_none' => '', 'show_option_no_change' => '',
        'option_none_value' => '', 'required' => false
    );

    $r = wp_parse_args( $args, $defaults );

    $emails = wpgh_get_emails();

    $output = '';
    // Back-compat with old system where both id and name were based on $name argument
    if ( empty( $r['id'] ) ) {
        $r['id'] = $r['name'];
    }

    if ( ! empty( $emails ) ) {
        $class = '';
        if ( ! empty( $r['class'] ) ) {
            $class = " class='" . esc_attr( $r['class'] ) . "'";
        }

        $required = ( $r['required'] === true )? "required" : "";

        $output = "<select style='width:" . esc_attr( $r['width'] ) . ";' name='" . esc_attr( $r['name'] ) . "'" . $class . " id='" . esc_attr( $r['id'] ) . "' " . $required . ">\n";
        if ( $r['show_option_no_change'] ) {
            $output .= "\t<option value=\"-1\">" . $r['show_option_no_change'] . "</option>\n";
        }
        if ( $r['show_option_none'] ) {
            $output .= "\t<option value=\"" . esc_attr( $r['option_none_value'] ) . '">' . $r['show_option_none'] . "</option>\n";
        }

        //$output .= walk_email_dropdown_tree( $emails, $r['depth'], $r );

        foreach ( $emails as $item ) {

            $selected = ( intval( $item['ID'] ) === intval( $r['selected'] ) )? "selected='selected'" : '' ;

            $output .= "<option value=\"" . $item['ID'] . "\" $selected >" . $item['subject'] . " (" . wpgh_email_status( $item['ID'] ).  ")</option>";
        }

        $output .= "</select>\n";
    }

    $output .= "<script>jQuery(document).ready(function(){jQuery( '#" . esc_attr( $r['id'] ) . "' ).select2()});</script>";

    /**
     * Filters the HTML output of a list of pages as a drop down.
     *
     * @since 2.1.0
     * @since 4.4.0 `$r` and `$pages` added as arguments.
     *
     * @param string $output HTML output for drop down list of pages.
     * @param array  $r      The parsed arguments array.
     * @param array  $pages  List of WP_Post objects returned by `get_pages()`
     */
    $html = apply_filters( 'wpgh_dropdown_emails', $output, $r, $emails );

    if ( $r['echo'] ) {
        echo $html;
    }

    return $html;
}

/**
 * Create a new email and redirect to the email editor.
 */
function wpgh_create_new_email()
{
    if ( isset( $_POST[ 'email_template' ] ) ){

        include dirname(__FILE__) . '/templates/email-templates.php';
        /* @var $email_templates array included from email-templates.php*/
        $email_content = $email_templates[ $_POST[ 'email_template' ] ][ 'content' ];

    } else if ( isset( $_POST[ 'email_id' ] ) ) {

        $email = wpgh_get_email_by_id( intval( $_POST['email_id'] ) );
        $email_content = $email->content;

    } else {

        ?><div class="notice notice-error"><p><?php _e( 'Could not create email. PLease select a template.', 'groundhogg' ); ?></p></div><?php
        return;

    }

    $email_id = wpgh_insert_new_email( $email_content, '', '', get_current_user_id(), get_current_user_id() );

    if ( isset( $_GET['step'] ) ){

        $step_id = intval( $_GET['step'] );

        wpgh_update_step_meta( $step_id, 'email_id', $email_id );

        $funnel_id = wpgh_get_step_funnel( $step_id );

        wp_redirect( admin_url( 'admin.php?page=gh_emails&action=edit&email=' .  $email_id . '&return_funnel=' . $funnel_id . '&return_step=' . $step_id ) );

    } else {

        wp_redirect( admin_url( 'admin.php?page=gh_emails&action=edit&email=' .  $email_id ) );

    }

    die();
}

add_action( 'wpgh_add_email', 'wpgh_create_new_email' );

/**
 * Save and update an email
 *
 * @param $email_id int, the Email's ID
 */
function wpgh_save_email( $email_id )
{
    do_action( 'wpgh_email_update_before', $email_id );

    $status = ( isset( $_POST['status'] ) )? sanitize_text_field( trim( stripslashes( $_POST['status'] ) ) ): 'draft';
    wpgh_update_email( $email_id, 'email_status', $status );

    $from_user =  ( isset( $_POST['from_user'] ) )? intval( $_POST['from_user'] ): -1;
    wpgh_update_email( $email_id, 'from_user', $from_user );

    $subject =  ( isset( $_POST['subject'] ) )? wp_strip_all_tags( sanitize_text_field( trim( stripslashes( $_POST['subject'] ) ) ) ): '';
    wpgh_update_email( $email_id, 'subject', $subject );

    $pre_header =  ( isset( $_POST['pre_header'] ) )? wp_strip_all_tags( sanitize_text_field( trim( stripslashes( $_POST['pre_header'] ) ) ) ): '';
    wpgh_update_email( $email_id, 'pre_header', $pre_header );

    $alignment =  ( isset( $_POST['email_alignment'] ) )? sanitize_text_field( trim( stripslashes( $_POST['email_alignment'] ) ) ): '';
    wpgh_update_email_meta( $email_id, 'alignment', $alignment );

    $content =  ( isset( $_POST['content'] ) )? apply_filters( 'wpgh_sanitize_email_content', wpgh_minify_html( trim( stripslashes( $_POST['content'] ) ) ) ): '';
//        $content =  ( isset( $_POST['content'] ) )? wp_kses( stripslashes( $_POST['content'] ), wpgh_emails_allowed_html() ): '';
    wpgh_update_email( $email_id, 'content', $content );

    do_action( 'wpgh_email_update_after', $email_id );
}

add_action( 'wpgh_update_email', 'wpgh_save_email' );

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
 * Send a test email
 *
 * @param $email_id int the ID pf the email
 */
function wpgh_send_test_email( $email_id )
{
    if ( isset( $_POST['send_test'] ) ){

        do_action( 'wpgh_before_send_test_email', $email_id );

        $test_email_uid =  ( isset( $_POST['test_email'] ) )? intval( $_POST['test_email'] ): '';
        wpgh_update_email_meta( $email_id, 'test_email', $test_email_uid );

        $sent = wpgh_send_email( get_userdata( $test_email_uid )->user_email, $email_id );

        if ( ! $sent )
            wp_die( 'Could not send test.' );

        do_action( 'wpgh_after_send_test_email', $email_id );
    }
}

add_action( 'wpgh_email_update_after', 'wpgh_send_test_email' );


/**
 * Add utm parameters and contact args to the end of all email links
 *
 * @param $string string
 * @return string
 */
function wpgh_suffix_emails( $string )
{
    $regex = '#(<a href=")([^"]*)("[^>]*?>)#i';

    return preg_replace_callback( $regex, 'wpgh_email_suffix_callback', $string );
}

/**
 * This is to add the relevant query args to the end of an email link back to the site.
 *
 * @param $match string
 * @return string
 */
function wpgh_email_suffix_callback( $match )
{
    $url = $match[2];

    if (strpos($url, '?') === false) {
        $url .= '?';
    }

    $url .= '&utm_source=email&utm_medium=email&utm_campaign=product_notify&contact_key=';

    return $match[1].$url.$match[3];
}

/**
 * Retutn the status of an email...
 *
 * @param $id int the ID of the email
 * @return string thhe email status
 */
function wpgh_email_status( $id )
{
    $email = wpgh_get_email_by_id( intval( $id) );

    if ( ! $email )
        return false;

    switch ( $email->email_status){
        case 'ready':
            return __( 'Ready', 'groundhogg' );
            break;
        case 'draft':
            return __( 'Draft', 'groundhogg' );
            break;
        case 'trash':
            return __( 'Trash', 'groundhogg' );
            break;
        default:
            return '';
            break;
    }
}


/**
 * Set a cookie to track which step they came from...
 *
 * @param $id int the ID of the step
 *
 * @return int|false the given ID
 */
function wpgh_set_the_email( $id )
{
    if ( ! is_numeric( $id )  )
        return false;

    setcookie( 'gh_email', wpgh_encrypt_decrypt( absint( $id ), 'e' ) , time() + 24 * HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );

    return $id;
}

/**
 * Get the funnel step the user came from
 *
 * @return bool
 */
function wpgh_get_current_email()
{
    if ( is_admin() )
        return false;

    if ( isset( $_COOKIE[ 'gh_email' ] ) ){
        return intval( wpgh_encrypt_decrypt( $_COOKIE[ 'gh_email' ], 'd' ) );
    } else if ( isset( $_GET['email'] ) ) {
        return intval( $_GET['email'] );
    }

    return false;
}

/**
 * Perform the stats collection when a contact clicks a link in an email.
 */
function wpgh_process_email_click()
{
    if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/gh-tracking/email/click/' ) === false )
        return;

    $funnel = wpgh_set_the_funnel( wpgh_get_current_funnel() );
    $step = wpgh_set_the_step( wpgh_get_current_step() );
    $email = wpgh_set_the_email( wpgh_get_current_email() );

    /* get the link target */
    if ( ! isset( $_GET['ref'] ) || empty( $_GET['ref'] ) )
        $ref = site_url();
    else
        $ref = urldecode( $_GET[ 'ref' ] );

    $contact = wpgh_get_the_contact();

    if ( ! $contact ){
        /* do not do tracking if there is no contact to associate */
        wp_redirect( $ref );
        die();
    }

    /* set the contact cookie */
    wpgh_set_the_contact( $contact->get_id() );

    /* if the open is not tracked, track it now. */
    if ( ! wpgh_activity_exists( $contact->get_id(), $funnel, $step, 'email_opened', $email ) ){
        wpgh_log_activity( $contact->get_id(), $funnel, $step, 'email_opened', $email );
    }

    /* track the click every time */
    wpgh_log_activity( $contact->get_id(), $funnel, $step, 'email_link_click', $email, $ref );

    /* send to original destination. */

    do_action( 'wpgh_email_link_click', $contact->get_id(), $email, $step, $funnel );

    wp_redirect( $ref );
    die();
}

add_action( 'init', 'wpgh_process_email_click' );

/**
 * Process the email open tracking. Add activity and attributions.
 */
function wpgh_process_email_open()
{
    if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/gh-tracking/email/open/' ) === false )
        return;

    $contact = wpgh_get_current_contact();

    $funnel_id = wpgh_get_current_funnel() ? wpgh_get_current_funnel() : 0;

    $step_id = wpgh_get_current_step() ? wpgh_get_current_step() : 0;

    $email_id = wpgh_get_current_email();

    /* if the open is not tracked, track it now. */
    if ( ! wpgh_activity_exists( $contact->get_id(), $funnel_id, $step_id, 'email_opened', $email_id ) ){
        wpgh_log_activity( $contact->get_id(), $funnel_id, $step_id, 'email_opened', $email_id );
    }

    do_action( 'wpgh_email_opened', $contact->get_id(), $email_id, $step_id, $funnel_id );

    die();
}

add_action( 'init', 'wpgh_process_email_open' );