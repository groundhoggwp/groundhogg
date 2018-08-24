<?php
/**
 * Contact Functions
 *
 * @package     groundhogg
 * @subpackage  Includes
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

define( 'WPFN_UNCONFIRMED', 0 );
define( 'WPFN_CONFIRMED', 1 );
define( 'WPFN_UNSUBSCRIBED', 2 );
define( 'WPFN_WEEKLY', 3 );
define( 'WPFN_MONTHLY', 4 );

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

		case WPFN_UNCONFIRMED:
			return __( 'Unconfirmed. They will receive emails.', 'groundhogg' );
			break;
		case WPFN_CONFIRMED:
			return __( 'Confirmed. They will receive emails.', 'groundhogg' );
			break;
		case WPFN_UNSUBSCRIBED:
			return __( 'Unsubscribed. They will not receive emails.', 'groundhogg' );
			break;
        case WPFN_WEEKLY:
            return __( 'This contact will only receive emails weekly.', 'groundhogg' );
            break;
        case WPFN_MONTHLY:
            return __( 'This contact will only receive emails monthly.', 'groundhogg' );
            break;
		default:
			return __( 'Unconfirmed. They will receive emails.', 'groundhogg' );
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
		return intval( $contact_exists[ 'ID' ] );
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


function wpfn_encrypt_decrypt( $string, $action = 'e' ) {
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
 * Set the contact cookie for reference
 *
 * @param $id int the ID of the contact
 */
function wpfn_set_the_contact( $id )
{
    setcookie( 'gh_contact', wpfn_encrypt_decrypt( $id, 'e' ) , time() + 24 * HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
}

/**
 * Return the contact ID of the current contact, perhaps if they are broswing the site based on a cookie.
 *
 * @return WPFN_Contact|false the ID of the contact, false if a contact ID doesn't exist
 */
function wpfn_get_the_contact()
{
    if ( is_admin() )
        return false;

    if ( isset( $_COOKIE[ 'gh_contact' ] ) ){
        /* if the contact cookie has been set. */
        $id = wpfn_encrypt_decrypt( sanitize_text_field( $_COOKIE[ 'gh_contact' ] ), 'd' );
    } else if ( isset( $_GET[ 'contact' ] ) ){
        /* if the contact is coming from an email link */
        $id = wpfn_encrypt_decrypt( urldecode( $_REQUEST[ 'contact' ] ), 'd' );
    } else if ( isset( $_REQUEST[ 'email' ] ) ) {
        /* possibly they are in the process of a form submission and the cookie has yet to be sent.*/
        if ( is_email( sanitize_email( $_REQUEST['email'] ) ) ){
            $contact = wpfn_get_contact_by_email( sanitize_email( $_REQUEST['email'] ) );
            if ( ! empty( $contact ) ){
                $id = intval( $contact['ID'] );
            }
        }
    } else {
        return false;
    }


    return new WPFN_Contact( $id ) ;
}

/**
 * wrapper function for wpfn_get_the_contact
 *
 * @return false|WPFN_Contact the contact instance or false on failure.
 */
function wpfn_get_current_contact()
{
    return wpfn_get_the_contact();
}

/**
 * Get a list of WPFN_contacts given a tag ID
 *
 * @param $tag_id int the Id of thre tag
 * @return bool|array list of contacts or false on failure
 */
function wpfn_get_contacts_by_tag( $tag_id )
{
    $ids = wpfn_get_contact_ids_by_tag( $tag_id );

    if ( ! $ids )
        return false;

    $contacts = array();

    foreach ( $ids as $relationship ){

        $contact_id = intval( $relationship[ 'contact_id' ] );

        $contacts[ $contact_id ] = new WPFN_Contact( $contact_id );

    }

    return $contacts;
}


/**
 * Add a tag from the add tag form.
 */
function wpfn_add_tag()
{
	if ( isset( $_POST['bulk_add'] ) ){

		$tag_names = explode( PHP_EOL, trim( sanitize_textarea_field( wp_unslash( $_POST['bulk_tags'] ) ) ) );

		foreach ($tag_names as $name)
		{
			$tagid = wpfn_insert_tag( $name );
		}
	} else {
		$tagname = sanitize_text_field( wp_unslash( $_POST['tag_name'] ) );
		$tagdesc = sanitize_text_field( wp_unslash( $_POST['tag_description'] ) );
		$tagid = wpfn_insert_tag( $tagname, $tagdesc );
	}
}

add_action( 'wpfn_add_tag', 'wpfn_add_tag' );

/**
 * update a tag
 */
function wpfn_save_tag( $id )
{
	$tag_name = sanitize_text_field( wp_unslash( $_POST[ 'name' ] ) );
	$tag_description = sanitize_textarea_field( wp_unslash( $_POST[ 'description' ] ) );

	wpfn_update_tag( $id, 'tag_description', $tag_description );
	wpfn_update_tag( $id, 'tag_name', $tag_name );
}

add_action( 'wpfn_update_tag', 'wpfn_save_tag' );

/**
 * Wrapper function to see if a contact has a particluar tag.
 *
 * @param $contact_id int the ID of the contact
 * @param $tag_id int the Id of the tag
 * @return bool whether the contact has the tag.
 */
function wpfn_has_tag( $contact_id, $tag_id )
{
    return 1 && wpfn_get_contact_tag_relationship( intval( $contact_id ), intval( $tag_id ) );
}

/**
 * Applies a tag to a contact
 *
 * @param $contact_id int th ID of the contact
 * @param $tag_id int the ID of the tag
 * @return bool whether the application was successful
 */
function wpfn_apply_tag( $contact_id, $tag_id )
{
    return wpfn_insert_contact_tag_relationship( intval( $contact_id ), intval( $tag_id ) );
}

/**
 * Delete a tag from the contact
 *
 * @param $contact_id int the ID of the contact
 * @param $tag_id int the ID of the tag
 * @return bool whether the deletion was successful
 */
function wpfn_remove_tag( $contact_id, $tag_id  )
{
    return wpfn_delete_contact_tag_relationship(intval( $contact_id ), intval( $tag_id ) );
}

/**
 * Queue the tag event in the event queue. Basically it runs immediately but is queued for the sake of semantics and reporting.
 * Used for both apply and remove tag since they are essentially the same thing.
 *
 * @param $step_id int The Id of the step
 * @param $contact_id int the Contact's ID
 */
function wpfn_enqueue_apply_tag_action( $step_id, $contact_id )
{
    $funnel_id = wpfn_get_step_funnel( $step_id );
    wpfn_enqueue_event( strtotime( 'now' ) + 10, $funnel_id,  $step_id, $contact_id );
}

add_action( 'wpfn_enqueue_next_funnel_action_apply_tag', 'wpfn_enqueue_apply_tag_action', 10, 2 );
add_action( 'wpfn_enqueue_next_funnel_action_remove_tag', 'wpfn_enqueue_apply_tag_action', 10, 2 );

/**
 * Process the apply tag action
 *
 * @param $step_id int the email step's id
 * @param $contact_id int The contact's ID
 * @return void
 */
function wpfn_do_apply_tag_action( $step_id, $contact_id )
{
    $tags = wpfn_get_step_meta( $step_id, 'tags', true );

    foreach ( $tags as $tag_id ){
        if ( wpfn_tag_exists( intval( $tag_id ) ) && wpfn_get_contact_by_id( $contact_id ) && ! wpfn_has_tag( $contact_id, intval( $tag_id ) ) ){
            wpfn_apply_tag( $contact_id, intval( $tag_id ) );
            do_action( 'wpfn_tag_applied', $contact_id, intval( $tag_id ) );
        }
    }
}

add_action( 'wpfn_do_action_apply_tag', 'wpfn_do_apply_tag_action', 10, 2 );

/**
 * Process the apply tag action
 *
 * @param $step_id int the email step's id
 * @param $contact_id int The contact's ID
 * @return void
 */
function wpfn_do_remove_tag_action( $step_id, $contact_id )
{
    $tags = wpfn_get_step_meta( $step_id, 'tags', true );
    foreach ( $tags as $tag_id ){
        if ( wpfn_tag_exists( intval( $tag_id ) ) && wpfn_get_contact_by_id( $contact_id ) && wpfn_has_tag( $contact_id, intval( $tag_id ) ) ){
            wpfn_remove_tag( $contact_id, intval( $tag_id ) );
            do_action( 'wpfn_tag_removed', $contact_id, intval( $tag_id ) );
        }
    }
}

add_action( 'wpfn_do_action_remove_tag', 'wpfn_do_remove_tag_action', 10, 2 );

/**
 * Get a tag selector to select tags...
 *
 * @param $args array args for the tag selector
 * @return string html content for the selector
 */
function wpfn_dropdown_tags( $args )
{
    wp_enqueue_style( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css' );
    wp_enqueue_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js' );

    $defaults = array(
        'selected' => array(), 'echo' => 1,
        'name' => 'tags[]', 'id' => 'tags',
        'width' => '100%', 'class' => '',
        'show_option_none' => '', 'show_option_no_change' => '',
        'option_none_value' => '', 'required' => false
    );

    $r = wp_parse_args( $args, $defaults );

    $tags = wpfn_get_tags();

    $output = '';
    // Back-compat with old system where both id and name were based on $name argument
    if ( empty( $r['id'] ) ) {
        $r['id'] = $r['name'];
    }

    if ( ! empty( $tags ) ) {
        $class = '';
        if ( ! empty( $r['class'] ) ) {
            $class = " class='" . esc_attr( $r['class'] ) . "'";
        }

        $required = ( $r['required'] === true )? "required" : "";

        $output = "<select style='width:" . esc_attr( $r['width'] ) . ";' name='" . esc_attr( $r['name'] ) . "'" . $class . " id='" . esc_attr( $r['id'] ) . "' " . $required . " multiple>\n";
        if ( $r['show_option_no_change'] ) {
            $output .= "\t<option value=\"-1\">" . $r['show_option_no_change'] . "</option>\n";
        }
        if ( $r['show_option_none'] ) {
            $output .= "\t<option value=\"" . esc_attr( $r['option_none_value'] ) . '">' . $r['show_option_none'] . "</option>\n";
        }

        //$output .= walk_email_dropdown_tree( $emails, $r['depth'], $r );

        foreach ( $tags as $item ) {

            $selected = in_array( $item['tag_id'], $r['selected'] )? "selected='selected'" : '' ;

            $output .= "<option value=\"" . $item['tag_id'] . "\" $selected >" . $item['tag_name'] . " (" . wpfn_count_contact_tag_relationships( 'tag_id', $item['tag_id'] ). ")</option>";
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
    $html = apply_filters( 'wpfn_dropdown_tags', $output, $r, $tags );

    if ( $r['echo'] ) {
        echo $html;
    }

    return $html;
}

/**
 * If the contact is visiting the confirmation page then confirm the email address!
 */
function wpfn_process_email_confirmation()
{
    if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/gh-confirmation/via/email' ) === false )
        return;

    $contact = wpfn_get_the_contact();

    if ( ! $contact )
        return;

    wpfn_update_contact( $contact->get_id(), 'optin_status', 1 );

    $conf_page = get_permalink( get_option( 'gh_confirmation_page' ) );

    do_action( 'wpfn_email_confirmed', $contact->get_id() );

    wp_redirect( $conf_page );
    die();
}

add_action( 'init', 'wpfn_process_email_confirmation' );

/**
 * Get the IP address of the current visiotor
 *
 * @return string the IP of a vsitor.
 */
function wpfn_get_visitor_ip() {
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        //check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        //to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return apply_filters( 'wpfn_get_ip', $ip );
}