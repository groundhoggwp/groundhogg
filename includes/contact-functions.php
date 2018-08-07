<?php
/**
 * Contact Functions
 *
 * @package     wp-funnels
 * @subpackage  Includes
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Add the contacts menu items to the menu.
 */
function wpfn_add_contact_menu_items()
{
	$contact_admin_id = add_menu_page(
		'Contacts',
		'Contacts',
		'manage_options',
		'contacts',
		'wpfn_contacts_page',
		'dashicons-universal-access'
	);

	$contact_admin_add = add_submenu_page(
		'contacts',
		'Add Contact',
		'Add New',
		'manage_options',
		'add_contact',
		'wpfn_add_contacts_page'
	);
}

add_action( 'admin_menu', 'wpfn_add_contact_menu_items' );

/**
 * Include the relevant admin file to display the output.
 */
function wpfn_contacts_page()
{
	include dirname( __FILE__ ) . '/admin/contacts/contacts.php';
}

/**
 * Include the relevant admin file to display the output.
 */
function wpfn_add_contacts_page()
{
	include dirname( __FILE__ ) . '/admin/contacts/add-contact.php';
}


/**
 * Get the text explanation for the optin status of a contact
 * 0 = unconfirmed, can send email
 * 1 = confirmed, can send email
 * 2 = opted out, can't send email
 *
 * @param int $status OptIn Status Int of a contact
 *
 * @return bool|string
 */
function wpfn_get_optin_status_text( $status )
{

	if ( ! is_numeric( $status ) )
		return false;

	$status = absint( $status );

	switch ( $status ){

		case 0:
			return __( 'Unconfirmed. They will receive emails.', 'wp-funnels' );
			break;
		case 1:
			return __( 'Confirmed. They will receive emails.', 'wp-funnels' );
			break;
		case 2:
			return __( 'Opted Out. They will not receive emails.', 'wp-funnels' );
			break;
		default:
			return __( 'Unconfirmed. They will receive emails.', 'wp-funnels' );
			break;
	}
}

/**
 * Whether we can send emails to this contact.
 *
 * @param int $status OptIn stats of a contact
 *
 * @return bool
 */
function wpfn_can_send_emails_to_contact( $status )
{
	if ( ! $status || ! is_numeric( $status ) )
		return false;

	$status = absint( $status );
	if ( ! $status )
		return false;

	return $status === 1 || $status === 0;
}

/**
 * Log activity of the client. Simple text meta field for easy manipulation.
 *
 * @param $contact_id int The Contact's ID
 * @param $activity string The activity to log
 *
 * @return bool True on success, false on failure
 */
function wpfn_log_contact_activity( $contact_id, $activity )
{
	if ( ! $activity || ! is_string( $activity ) )
		return false;

	$date_time = date( 'Y-m-d H:i:s', strtotime( 'now' ) );

	$activity = sanitize_text_field( $activity );

	$last_activity = wpfn_get_contact_meta( $contact_id, 'activity_log', true );

	if ( ! $last_activity ){
		$last_activity = '';
	}

	$new_activity = $date_time . ' | ' . $activity . PHP_EOL . $last_activity;

	do_action( 'wpfn_contact_activity_logged', $contact_id );

	return wpfn_update_contact_meta( $contact_id, 'activity_log', $new_activity );
}

/**
 * Quick add a new contact.
 *
 * @param $email string Email
 * @param $first string First Name
 * @param $last string Last Name
 * @param $phone string Phone Number
 * @param $extension string Phone Extension
 *
 * @return int|bool contact ID on success, false on failure
 */
function wpfn_quick_add_contact( $email, $first='', $last='', $phone='', $extension='' )
{
	$contact_exists = wpfn_get_contact_by_email( $email );

	if ( $contact_exists ){
		return false;
	}

	$id = wpfn_insert_new_contact( $email, $first, $last );

	if ( ! $id ){
		return false;
	}

	wpfn_add_contact_meta( $id, 'primary_phone', $phone );
	wpfn_add_contact_meta( $id, 'primary_phone_extension', $extension );

	if ( is_admin() ){
		wpfn_log_contact_activity( $id, 'Contact Created Via Admin.' );
	}

	return $id;

}

/**
 * Return the contact ID of the current contact, perhaps if they are broswing the site based on a cookie.
 *
 * @return WPFN_Contact|false the ID of the contact, false if a contact ID doesn't exist
 */
function wpfn_get_the_contact()
{
    //todo implement a get the contact function
    // this will likely return the contact ID based on a cookie, or possibly the URL if in the admin.

    return new WPFN_Contact( 0 ) ;
}

/**
 * Return whether a contact is in a funnel or not.
 *
 * @param $contact_id int the Contcat's ID
 * @return bool whether the contact is in a funnel or not
 */
function wpfn_contact_is_in_funnel( $contact_id )
{
    //todo implement
    return true;
}