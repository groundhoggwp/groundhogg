<?php
/**
 * Created by PhpStorm.
 * User: Adrian
 * Date: 2018-07-28
 * Time: 3:09 PM
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

	$email = wpfn_get_email_by_id( $email_id );

	$title = get_bloginfo( 'name' );

	$subject_line = wpfn_do_replacements( $contact_id, $email->subject );

	$pre_header = wpfn_do_replacements( $contact_id, $email->pre_header );

	$content = apply_filters( 'the_content', wpfn_do_replacements( $contact_id, $email->content ) );

	//merged in email template

	ob_start();

	include dirname( __FILE__ ) . '/templates/email.php';

	$email_content = ob_get_contents();

	ob_end_clean();

	$contact = new WPFN_Contact( $contact_id );

	$headers[] = 'From: ' . $email->from_name . ' <' . $email->from_email . '>';
	$headers[] = 'Reply To: ' . $email->from_email;
	$headers[] = 'Content-Type: text/html; charset=UTF-8';

	return wp_mail( $contact->getEmail() , $subject_line, $email_content, $headers );

}