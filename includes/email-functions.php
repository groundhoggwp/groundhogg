<?php
/**
 * Emailing Functions
 *
 * Anything to do with saving, manipulating, and running email functions in the event queue
 *
 * @package     wp-funnels
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Add the contacts menu items to the menu.
 */
function wpfn_add_email_menu_items()
{
	$email_admin_id = add_menu_page(
		'Emails',
		'Emails',
		'manage_options',
		'emails',
		'wpfn_emails_page',
		'dashicons-email-alt'
	);

	$email_admin_add = add_submenu_page(
		'emails',
		'Add Email',
		'Add New',
		'manage_options',
		'add_email',
		'wpfn_add_emails_page'
	);
}

add_action( 'admin_menu', 'wpfn_add_email_menu_items' );

/**
 * Include the relevant admin file to display the output.
 */
function wpfn_emails_page()
{
	include dirname( __FILE__ ) . '/admin/emails/emails.php';
}

/**
 * Include the relevant admin file to display the output.
 */
function wpfn_add_emails_page()
{
	include dirname( __FILE__ ) . '/admin/emails/add-email.php';
}

/**
 * Return the html tags allowed in emails
 *
 * @return array the allowed HTML in emails
 */
function wpfn_emails_allowed_html()
{
	//todo define custom HTML array.

	$allowed_tags = wp_kses_allowed_html();
	return $allowed_tags;
}

/**
 * Send the specified email to a contact.
 *
 * @param $contact_id
 * @param $email_id
 *
 * @return bool true on success, false on failure
 */
function wpfn_send_email( $contact_id, $email_id )
{

	if ( ! $contact_id || ! is_int( $contact_id ) || ! $email_id || ! is_int( $email_id )  )
		return false;

    $logo_url = get_option( 'logo_url', 'https://formlift.net/wp-content/uploads/2018/05/Final-logo3-transparent-lg-200x87.png' );

	$email = wpfn_get_email_by_id( $email_id );

	$title = get_bloginfo( 'name' );

	$subject_line = wpfn_do_replacements( $contact_id, $email->subject );

	$pre_header = wpfn_do_replacements( $contact_id, $email->pre_header );

	$content = apply_filters( 'the_content', wpfn_do_replacements( $contact_id, $email->content ) );

    $email_footer_text = get_option( 'email_footer_text', 'My Company Address & Phone Number' );

    $unsubscribe_link = "<a href='#'>Unsubscribe</a>";

	//merged in email template

	ob_start();

	include dirname( __FILE__ ) . '/templates/email.php';

	$email_content = ob_get_contents();

	ob_end_clean();

	$contact = new WPFN_Contact( $contact_id );

	$headers = array();
	$headers[] = 'From: ' . $email->from_name . ' <' . $email->from_email . '>';
	$headers[] = 'Reply-To: ' . $email->from_email;
	$headers[] = 'Content-Type: text/html; charset=UTF-8';

	add_filter( 'wp_mail_content_type', 'wpfn_send_html_email' );

	return wp_mail( $contact->getEmail() , $subject_line, $email_content, $headers );

}

/**
 * Convert to HTML email
 *
 * @return string the content type for the email
 */
function wpfn_send_html_email()
{
    return 'text/html';
}

/**
 * Queue the email in the event queue. Does Basically it runs immediately but is queued for the sake of semantics.
 *
 * @param $step_id int The Id of the step
 * @param $contact_id int the Contact's ID
 */
function wpfn_enqueue_send_email_action( $step_id, $contact_id )
{
    $funnel_id = wpfn_get_step_funnel( $step_id );
    wpfn_enqueue_event( strtotime( 'now' ) + 10, $funnel_id,  $step_id, $contact_id );
}

add_action( 'wpfn_enqueue_next_funnel_action_send_email', 'wpfn_enqueue_send_email_action', 10, 2 );

/**
 * Process the email action step sending and then queue up the next action in the funnel.
 *
 * @param $step_id int the email step's id
 * @param $contact_id int The contact's ID
 */
function wpfn_do_send_email_action( $step_id, $contact_id )
{
    $email_id = wpfn_get_step_meta( $step_id, 'email_id', true );
    wpfn_send_email( $contact_id, $email_id );
}

add_action( 'wpfn_do_action_send_email', 'wpfn_do_send_email_action', 10, 2 );


/**
 * Get a dropdown of all the available emails
 * rudementary copy of wp_dropdown_pages
 *
 * @return array list of available emails
 */
function wpfn_dropdown_emails( $args )
{
    wp_enqueue_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css' );
    wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js' );

    $defaults = array(
        'selected' => 0, 'echo' => 1,
        'name' => 'email_id', 'id' => '',
        'class' => '',
        'show_option_none' => '', 'show_option_no_change' => '',
        'option_none_value' => ''
    );

    $r = wp_parse_args( $args, $defaults );

    $emails = wpfn_get_emails();

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

        $output = "<select name='" . esc_attr( $r['name'] ) . "'" . $class . " id='" . esc_attr( $r['id'] ) . "'>\n";
        if ( $r['show_option_no_change'] ) {
            $output .= "\t<option value=\"-1\">" . $r['show_option_no_change'] . "</option>\n";
        }
        if ( $r['show_option_none'] ) {
            $output .= "\t<option value=\"" . esc_attr( $r['option_none_value'] ) . '">' . $r['show_option_none'] . "</option>\n";
        }

        //$output .= walk_email_dropdown_tree( $emails, $r['depth'], $r );

        foreach ( $emails as $item ) {

            $selected = ( intval( $item['ID'] ) === intval( $r['selected'] ) )? "selected='selected'" : '' ;

            $output .= "<option value=\"" . $item['ID'] . "\" $selected >" . $item['subject'] . "</option>";
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
    $html = apply_filters( 'wpfn_dropdown_emails', $output, $r, $emails );

    if ( $r['echo'] ) {
        echo $html;
    }

    return $html;
}

/**
 * Save and update an email
 *
 * @param $email_id int, the Email's ID
 */
function wpfn_save_email( $email_id )
{
    if ( isset( $_POST['edit_email_nonce'] ) && wp_verify_nonce( $_POST['edit_email_nonce'], 'edit_email' ) && current_user_can( 'manage_options' ) ) {

        do_action( 'wpfn_email_update_before', $email_id );

        $from_name =  ( isset( $_POST['from_name'] ) )? sanitize_text_field( $_POST['from_name'] ): '';
        wpfn_update_email( $email_id, 'from_name', $from_name );
        $from_email =  ( isset( $_POST['from_email'] ) )? sanitize_email( $_POST['from_email'] ): '';
        wpfn_update_email( $email_id, 'from_email', $from_email );

        $subject =  ( isset( $_POST['subject'] ) )? sanitize_text_field( $_POST['subject'] ): '';
        wpfn_update_email( $email_id, 'subject', $subject );

        $pre_header =  ( isset( $_POST['pre_header'] ) )? sanitize_text_field( $_POST['pre_header'] ): '';
        wpfn_update_email( $email_id, 'pre_header', $pre_header );

        $content =  ( isset( $_POST['content'] ) )? wp_kses_post( stripslashes( $_POST['content'] ) ): '';
//        $content =  ( isset( $_POST['content'] ) )? wp_kses( stripslashes( $_POST['content'] ), wpfn_emails_allowed_html() ): '';
        wpfn_update_email( $email_id, 'content', $content );

        do_action( 'wpfn_email_update_after', $email_id );

        ?><div class="notice notice-success is-dismissible"><p>Successfully updated email!</p></div><?php
    }
}

add_action( 'wpfn_email_editor_before_everything', 'wpfn_save_email' );

/**
 * Send a test email
 *
 * @param $email_id int the ID pf the email
 */
function wpfn_send_test_email( $email_id )
{
    if ( isset( $_POST['test_email'] ) ){

        do_action( 'wpfn_before_send_test_email', $email_id );

        //todo proper implementation of test email
        wpfn_send_email( 5, $email_id );

        do_action( 'wpfn_after_send_test_email', $email_id );

        ?><div class="notice notice-success is-dismissible"><p>Successfully sent test email!</p></div><?php
    }
}

add_action( 'wpfn_email_update_after', 'wpfn_send_test_email' );


/**
 * Add utm parameters and contact args to the end of all email links
 *
 * @param $string
 * @return string
 */
function wpfn_suffix_emails( $string )
{
    $regex = '#(<a href=")([^"]*)("[^>]*?>)#i';

    return preg_replace_callback( $regex, 'wpfn_email_suffix_callback', $string );
}

/**
 *
 * @param $match
 * @return string
 */
function wpfn_email_suffix_callback( $match )
{
    $url = $match[2];

    if (strpos($url, '?') === false) {
        $url .= '?';
    }

    $url .= '&utm_source=email&utm_medium=email&utm_campaign=product_notify&contact_key=';

    return $match[1].$url.$match[3];
}
