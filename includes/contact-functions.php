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

define( 'WPGH_UNCONFIRMED', 0 );
define( 'WPGH_CONFIRMED', 1 );
define( 'WPGH_UNSUBSCRIBED', 2 );
define( 'WPGH_WEEKLY', 3 );
define( 'WPGH_MONTHLY', 4 );
define( 'WPGH_HARD_BOUNCE', 5 );
define( 'WPGH_SPAM', 6 );

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
function wpgh_get_optin_status_text( $status )
{

	if ( ! is_numeric( $status ) )
		return false;

	$status = absint( $status );

	switch ( $status ){

		case WPGH_UNCONFIRMED:
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
 * Whether we can send emails to this contact.
 *
 * @param int $contact_id ID of the contact.
 *
 * @return bool
 */
function wpgh_can_send_email( $contact_id )
{
    if (!$contact_id || !is_numeric($contact_id))
        return false;

    $contact_id = absint($contact_id);
    if (!$contact_id)
        return false;

    $contact = new WPGH_Contact($contact_id);

    /* check for strict GDPR settings */
    if ( wpgh_is_gdpr() && in_array( 'on', get_option( 'gh_strict_gdpr', array() ) ) )
    {
        $consent = wpgh_get_contact_meta( $contact_id, 'gdpr_consent', true );

        if ( $consent !== 'yes' )
            return false;
    }

    switch ( $contact->get_optin_status() )
    {
        case WPGH_UNCONFIRMED:
            /* check for grace period if necessary */
            if ( in_array( 'on', get_option( 'gh_strict_confirmation', array() ) ) )
            {
                $grace = intval( get_option( 'gh_confirmation_grace_period', 14 ) ) * 24 * HOUR_IN_SECONDS;
                $time_passed = time() - strtotime( $contact->date_created );
                if ( $time_passed > $grace )
                    return false;
            }

            return true;
            break;
        case WPGH_CONFIRMED:
            return true;
            break;
        case WPGH_SPAM;
        case WPGH_HARD_BOUNCE;
        case WPGH_UNSUBSCRIBED:
            return false;
            break;
        case WPGH_WEEKLY:
            $last_sent = wpgh_get_contact_meta( $contact_id, 'last_sent', true );
            return ( time() - intval( $last_sent ) ) > 7 * 24 * HOUR_IN_SECONDS;
            break;
        case WPGH_MONTHLY:
            $last_sent = wpgh_get_contact_meta( $contact_id, 'last_sent', true );
            return ( time() - intval( $last_sent ) ) > 30 * 24 * HOUR_IN_SECONDS;
            break;
        default:
            return true;
            break;
    }
}

/**
 * Log activity of the client. Simple text meta field for easy manipulation.
 *
 * @param $contact_id int The Contact's ID
 * @param $note string The note to add
 *
 * @return bool True on success, false on failure
 */
function wpgh_add_note( $contact_id, $note )
{
	if ( ! $note || ! is_string( $note ) )
		return false;

    $note = sanitize_textarea_field( $note );

    $current_notes = wpgh_get_contact_meta( $contact_id, 'notes', true );

    $new_notes = sprintf( "===== %s =====\n\n", date_i18n( get_option( 'date_format' ) ) );
    $new_notes .= sprintf( "%s\n\n", $note );
    $new_notes .= $current_notes;

    $new_notes = sanitize_textarea_field( $new_notes );

    wpgh_update_contact_meta( $contact_id, 'notes', $new_notes );

	do_action( 'wpgh_contact_note_added', $contact_id );

	return true;
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
 * @return int|bool|WP_Error contact ID on success, false on failure, or a WP error in case of spam.
 */
function wpgh_quick_add_contact( $email, $first='', $last='', $phone='', $extension='' )
{

    do_action( 'wpgh_quick_add_contact_before' );

    /* do a spam check based on the comment blacklist of WP. */
    $blacklist = get_option( 'blacklist_keys', false );

    if ( $blacklist ){
        $keys = explode( PHP_EOL, $blacklist );

        foreach ( $keys as $key ) {
            if ( strpos( $email, $key ) !== false
            || strpos( $first, $key ) !== false
            || strpos( $last, $key ) !== false
            || strpos( wpgh_get_visitor_ip(), $key ) !== false
            ){
                do_action( 'wpgh_quick_add_spam_filter' );

                return new WP_Error( 'SPAMMED', __( 'You are blacklisted.' ) );
            }
        }
    }


	$contact_exists = wpgh_get_contact_by_email( $email );

	/* update the contact instead */
	if ( $contact_exists ){

	    $id = intval( $contact_exists[ 'ID' ] );

	    if ( ! empty( $first ) )
	        wpgh_update_contact( $id, 'first_name', $first );
        if ( ! empty( $last ) )
            wpgh_update_contact( $id, 'last_name', $last );

        if ( ! empty( $phone ) )
            wpgh_update_contact_meta( $id, 'primary_phone', $phone );
        if ( ! empty( $extension ) )
            wpgh_update_contact_meta( $id, 'primary_phone_extension', $extension );

        return $id;
	}

	if ( ! $email )
	    return false;

	$id = wpgh_insert_new_contact( $email, $first, $last );

	if ( ! $id ){
		return false;
	}

	wpgh_add_contact_meta( $id, 'primary_phone', $phone );
	wpgh_add_contact_meta( $id, 'primary_phone_extension', $extension );

	if ( is_admin() ){
		wpgh_add_note( $id, 'Contact Created Via Admin.' );
	}

	do_action( 'wpgh_quick_add_contact_after', $id );

	return $id;

}

/**
 * Update the contact
 *
 * @param $id
 */
function wpgh_save_contact( $id )
{
    if ( ! $id ){
        return;
    }

    $contact = new WPGH_Contact( $id );

    do_action( 'wpgh_admin_update_contact_before', $id );

    //todo security check

    /* Save the meta first... as actual fields might overwrite it later... */
    $cur_meta = wpgh_get_contact_meta( $id );
    $posted_meta = $_POST[ 'meta' ];

    foreach ( $cur_meta as $key => $value ){
        if ( isset( $posted_meta[ $key ] ) ){
            wpgh_update_contact_meta( $id, $key, sanitize_text_field( $posted_meta[ $key ] ), $value[0] );
        } else {
            wpgh_delete_contact_meta( $id, $key );
        }
    }

    /* add new meta */
    if ( isset( $_POST[ 'newmetakey' ] ) && isset( $_POST[ 'newmetavalue' ] ) ){
        $new_meta_keys = $_POST[ 'newmetakey' ];
        $new_meta_vals = $_POST[ 'newmetavalue' ];

        foreach ( $new_meta_keys as $i => $new_meta_key ){
            wpgh_update_contact_meta( $id, sanitize_key( $new_meta_key ), sanitize_text_field( $new_meta_vals[ $i ] ) );
        }
    }

    if ( isset( $_POST[ 'unsubscribe' ] ) )
    {
        wpgh_update_contact( $id, 'optin_status', WPGH_UNSUBSCRIBED );

        do_action( 'wpgh_preference_unsubscribe', $id );

        wpgh_add_notice(
		    esc_attr( 'unsubscribed' ),
		    __( 'This contact will no longer receive email communication', 'groundhogg' ),
		    'info'
	    );
    }

    if ( isset( $_POST[ 'email' ] ) )
    {
        wpgh_update_contact_email( $id, sanitize_email(  $_POST[ 'email' ] ) );
    }

    if ( isset( $_POST['first_name'] ) ){
        wpgh_update_contact( $id, 'first_name', sanitize_text_field( $_POST['first_name'] ) );
    }

    if ( isset( $_POST['owner'] ) ){
        wpgh_update_contact( $id, 'owner_id', intval( $_POST['owner'] ) );
    }

    if ( isset( $_POST['last_name'] ) ){
        wpgh_update_contact( $id, 'last_name', sanitize_text_field( $_POST['last_name'] ) );
    }

    if ( isset( $_POST['primary_phone'] ) ){
        wpgh_update_contact_meta( $id, 'primary_phone', sanitize_text_field( $_POST['primary_phone'] ) );
    }

    if ( isset( $_POST['primary_phone_extension'] ) ){
        wpgh_update_contact_meta( $id, 'primary_phone_extension', sanitize_text_field( $_POST['primary_phone_extension'] ) );
    }

    if ( isset( $_POST[ 'notes' ] ) ){
        wpgh_update_contact_meta( $id, 'notes', sanitize_textarea_field( $_POST['notes'] ) );
    }

    if ( isset( $_POST[ 'leadsource' ] ) ){
        wpgh_update_contact_meta( $id, 'leadsource', esc_url_raw( $_POST['leadsource'] ) );
    }

    if ( isset( $_POST[ 'page_source' ] ) ){
        wpgh_update_contact_meta( $id, 'page_source', esc_url_raw( $_POST['page_source'] ) );
    }

    if ( isset( $_POST[ 'tags' ] ) ){
        $tags = wpgh_validate_tags( $_POST['tags' ] );
        $cur_tags = $contact->get_tags();
        $new_tags = $tags;

        $delete_tags = array_diff( $cur_tags, $new_tags );
        if ( ! empty( $delete_tags ) ) {
            foreach ( $delete_tags as $tag )
            {
                wpgh_remove_tag( $id, $tag );
            }
        }

        $add_tags = array_diff( $new_tags, $cur_tags );
        if ( ! empty( $add_tags ) ){
            foreach ( $add_tags as $tag )
            {
                wpgh_apply_tag( $id, $tag );
            }
        }
    }

    do_action( 'wpgh_admin_update_contact_after', $id );

}

add_action( 'wpgh_update_contact', 'wpgh_save_contact' );

/**
 * Save function for inline editing...
 */
function wpgh_save_contact_inline()
{
    if ( ! wp_doing_ajax() )
        wp_die( 'should not be calling this function' );

    //todo security check

    $id             = (int) $_POST['ID'];

    do_action( 'wpgh_inline_update_contact_before', $id );

    $email          = sanitize_email( $_POST['email'] );
    $first_name     = sanitize_text_field( $_POST['first_name'] );
    $last_name      = sanitize_text_field( $_POST['last_name'] );
    $optin_status   = intval( $_POST['optin_status' ] );
    $owner          = intval( $_POST['owner' ] );
    $tags           = wpgh_validate_tags( $_POST['tags' ] );

    $err = array();

    if( !$email ) {
        $err[] = 'Email can not be blank';
    } else if ( ! is_email( $email ) ) {
        $err[] = 'Invalid email address';
    }

    if( !$first_name ) {
        $err[] = 'First name can not be blank';
    }

    if( $err ) {
        echo implode(', ', $err);
        exit;
    }

    wpgh_update_contact_email( $id, $email );

    wpgh_update_contact($id, 'first_name', $first_name );
    wpgh_update_contact($id, 'last_name', $last_name );
    wpgh_update_contact($id, 'owner_id', $owner );

    $contact = new WPGH_Contact( $id );
    $cur_tags = $contact->get_tags();
    $new_tags = $tags;

    $delete_tags = array_diff( $cur_tags, $new_tags );
    if ( ! empty( $delete_tags ) ) {
        foreach ( $delete_tags as $tag )
        {
            wpgh_remove_tag( $id, $tag );
        }
    }

    $add_tags = array_diff( $new_tags, $cur_tags );
    if ( ! empty( $add_tags ) ){
        foreach ( $add_tags as $tag )
        {
            wpgh_apply_tag( $id, $tag );
        }
    }

    if ( isset( $_POST['unsubscribe'] ) ){
        if ( $contact->get_optin_status() !== WPGH_UNSUBSCRIBED )
        {
            do_action( 'wpgh_preference_unsubscribe', $id );
            wpgh_update_contact($id, 'optin_status', WPGH_UNSUBSCRIBED );
        }
    }

    do_action( 'wpgh_inline_update_contact_after', $id );

    if ( ! class_exists( 'WPGH_Contacts_Table' ) )
    {
        include dirname( __FILE__ ) . '/admin/contacts/class-contacts-table.php';
    }

    $contactTable = new WPGH_Contacts_Table;
    $contactTable->single_row( wpgh_get_contact_by_id( $id, ARRAY_A ) );
    wp_die();
}

add_action('wp_ajax_wpgh_inline_save_contacts', 'wpgh_save_contact_inline');

/**
 * Save the contact details form the admin add form
 */
function wpgh_admin_add_contact()
{
    do_action( 'wpgh_admin_add_contact_before' );

    if ( ! isset( $_POST['email'] ) ){
        wpgh_add_notice( 'NO_EMAIL', __( "Please enter a valid email address", 'groundhogg' ), 'error' );
        return;
    }

    if ( isset( $_POST[ 'first_name' ] ) )
        $args['first'] = sanitize_text_field( $_POST[ 'first_name' ] );

    if ( isset( $_POST[ 'last_name' ] ) )
        $args['last'] = sanitize_text_field( $_POST[ 'last_name' ] );

    if ( isset( $_POST[ 'email' ] ) )
        $args['email'] = sanitize_email( $_POST[ 'email' ] );

    if ( isset( $_POST[ 'primary_phone' ] ) )
        $args['phone'] = sanitize_text_field( $_POST[ 'primary_phone' ] );

    if ( isset( $_POST[ 'primary_phone_extension' ] ) )
        $args['ext'] = sanitize_text_field( $_POST[ 'primary_phone_extension' ] );

    if ( ! is_email( $args['email'] ) ){
        wpgh_add_notice( 'BAD_EMAIL', __( "Please enter a valid email address", 'groundhogg' ), 'error' );
        return;
    }

    $args = wp_parse_args( $args, array(
        'first' => '',
        'last'  => '',
        'email' => '',
        'phone' => '',
        'ext'   => ''
    ));

    $id = wpgh_quick_add_contact( $args['email'], $args['first'], $args['last'], $args['phone'], $args['ext'] );

    if ( isset( $_POST[ 'notes' ] ) ){
        wpgh_add_note( $id, $_POST[ 'notes' ] );
    }

    if ( isset( $_POST[ 'tags' ] ) ) {
        $tags = wpgh_validate_tags($_POST['tags']);
        foreach ($tags as $tag) {
            wpgh_apply_tag($id, $tag);
        }
    }

    wpgh_add_notice( 'created', __( "Contact created!", 'groundhogg' ), 'success' );

    do_action( 'wpgh_admin_add_contact_after', $id );

    wp_redirect( admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $id ) );
    die();
}

add_action( 'wpgh_admin_add_contact', 'wpgh_admin_add_contact' );

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
 * Set the contact cookie for reference
 *
 * @param $id int the ID of the contact
 */
function wpgh_set_the_contact( $id )
{
    setcookie( 'gh_contact', wpgh_encrypt_decrypt( $id, 'e' ) , time() + 24 * HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
}

/**
 * Return the contact ID of the current contact, perhaps if they are broswing the site based on a cookie.
 *
 * @return WPGH_Contact|false the ID of the contact, false if a contact ID doesn't exist
 */
function wpgh_get_the_contact()
{
    if ( is_admin() && ! wp_doing_ajax() )
        return false;

    if ( isset( $_COOKIE[ 'gh_contact' ] ) ){
        /* if the contact cookie has been set. */
        $id = wpgh_encrypt_decrypt( sanitize_text_field( $_COOKIE[ 'gh_contact' ] ), 'd' );

    } else if ( is_user_logged_in() ) {
    	$user = wp_get_current_user();
    	$contact = wpgh_get_contact_by_email( $user->user_email );
    	if ( ! $contact )
    		return false;
    	$id = intval( $contact['ID'] );

    } else if ( isset( $_GET[ 'contact' ] ) ){
        /* if the contact is coming from an email link */
        $id = wpgh_encrypt_decrypt( urldecode( $_GET[ 'contact' ] ), 'd' );

    } else if ( isset( $_REQUEST[ 'email' ] ) ) {
        /* possibly they are in the process of a form submission and the cookie has yet to be sent.*/
        if ( is_email( sanitize_email( $_REQUEST['email'] ) ) ){
            $contact = wpgh_get_contact_by_email( sanitize_email( $_REQUEST['email'] ) );
            if ( ! empty( $contact ) ){
                $id = intval( $contact['ID'] );
            }
        }
    } else {
        $id = apply_filters( 'gh_get_current_contact', false );
    }

    if ( ! $id )
        return false;

    return new WPGH_Contact( $id ) ;
}

/**
 * wrapper function for wpgh_get_the_contact
 *
 * @return false|WPGH_Contact the contact instance or false on failure.
 */
function wpgh_get_current_contact()
{
    return wpgh_get_the_contact();
}

/**
 * Get a list of WPGH_contacts given a tag ID
 *
 * @param $tag_id int the Id of thre tag
 * @return bool|array list of contacts or false on failure
 */
function wpgh_get_contacts_by_tag( $tag_id )
{
    $ids = wpgh_get_contact_ids_by_tag( $tag_id );

    if ( ! $ids )
        return false;

    $contacts = array();

    foreach ( $ids as $relationship ){

        $contact_id = intval( $relationship[ 'contact_id' ] );

        $contacts[ $contact_id ] = new WPGH_Contact( $contact_id );

    }

    return $contacts;
}


/**
 * Add a tag from the add tag form.
 */
function wpgh_add_tag()
{
	if ( isset( $_POST['bulk_add'] ) ){

		$tag_names = explode( PHP_EOL, trim( sanitize_textarea_field( wp_unslash( $_POST['bulk_tags'] ) ) ) );

		foreach ($tag_names as $name)
		{
			$tagid = wpgh_insert_tag( $name );
		}
	} else {
		$tagname = sanitize_text_field( wp_unslash( $_POST['tag_name'] ) );
		$tagdesc = sanitize_text_field( wp_unslash( $_POST['tag_description'] ) );
		$tagid = wpgh_insert_tag( $tagname, $tagdesc );
	}
}

add_action( 'wpgh_add_tag', 'wpgh_add_tag' );

/**
 * update a tag
 */
function wpgh_save_tag( $id )
{
	$tag_name = sanitize_text_field( wp_unslash( $_POST[ 'name' ] ) );
	$tag_description = sanitize_textarea_field( wp_unslash( $_POST[ 'description' ] ) );

	wpgh_update_tag( $id, 'tag_description', $tag_description );
	wpgh_update_tag( $id, 'tag_name', $tag_name );
	wpgh_update_tag( $id, 'tag_slug', sanitize_title( $tag_name ) );
}

add_action( 'wpgh_update_tag', 'wpgh_save_tag' );

/**
 * Wrapper function to see if a contact has a particluar tag.
 *
 * @param $contact_id int the ID of the contact
 * @param $tag_id_or_slug int the Id of the tag
 * @return bool whether the contact has the tag.
 */
function wpgh_has_tag( $contact_id, $tag_id_or_slug )
{
    if ( ! is_numeric( $tag_id_or_slug ) )
    {
        $tag = wpgh_get_tag( $tag_id_or_slug );

        if ( ! $tag ){
            return false;
        }

        $tag_id_or_slug = $tag[ 'tag_id' ];
    }

    return 1 && wpgh_get_contact_tag_relationship( intval( $contact_id ), intval( $tag_id_or_slug ) );
}

/**
 * Applies a tag to a contact
 *
 * @param $contact_id int th ID of the contact
 * @param $tag_id int the ID of the tag
 * @return bool whether the application was successful
 */
function wpgh_apply_tag( $contact_id, $tag_id )
{
    $rel = wpgh_insert_contact_tag_relationship( intval( $contact_id ), intval( $tag_id ) );
    if ( $rel )
        do_action( 'wpgh_tag_applied', $contact_id, intval( $tag_id ) );

    return $rel;
}

/**
 * Delete a tag from the contact
 *
 * @param $contact_id int the ID of the contact
 * @param $tag_id int the ID of the tag
 * @return bool whether the deletion was successful
 */
function wpgh_remove_tag( $contact_id, $tag_id  )
{
    $rel = wpgh_delete_contact_tag_relationship(intval( $contact_id ), intval( $tag_id ) );

    if ( $rel )
        do_action( 'wpgh_tag_removed', $contact_id, intval( $tag_id ) );

    return $rel;
}

/**
 * Process the apply tag action
 *
 * @param $step_id int the email step's id
 * @param $contact_id int The contact's ID
 * @return void
 */
function wpgh_do_apply_tag_action( $step_id, $contact_id )
{
    $tags = wpgh_get_step_meta( $step_id, 'tags', true );

    foreach ( $tags as $tag_id ){
        if ( wpgh_tag_exists( intval( $tag_id ) ) && wpgh_get_contact_by_id( $contact_id ) && ! wpgh_has_tag( $contact_id, intval( $tag_id ) ) ){
            wpgh_apply_tag( $contact_id, intval( $tag_id ) );
        }
    }
}

add_action( 'wpgh_do_action_apply_tag', 'wpgh_do_apply_tag_action', 10, 2 );

/**
 * Process the apply tag action
 *
 * @param $step_id int the email step's id
 * @param $contact_id int The contact's ID
 * @return void
 */
function wpgh_do_remove_tag_action( $step_id, $contact_id )
{
    $tags = wpgh_get_step_meta( $step_id, 'tags', true );
    foreach ( $tags as $tag_id ){
        if ( wpgh_tag_exists( intval( $tag_id ) ) && wpgh_get_contact_by_id( $contact_id ) && wpgh_has_tag( $contact_id, intval( $tag_id ) ) ){
            wpgh_remove_tag( $contact_id, intval( $tag_id ) );
        }
    }
}

add_action( 'wpgh_do_action_remove_tag', 'wpgh_do_remove_tag_action', 10, 2 );

/**
 * Iterate through a list of supposed tags.
 * If the tag exists, then great, otherwise create it for simplicity.
 *
 * @param $maybe_tags array list of supposed tags.
 * @return mixed
 */
function wpgh_validate_tags( $maybe_tags )
{

    $tags = array();

    foreach ( $maybe_tags as $i => $tag_id_or_string )
    {

        if ( is_int( $tag_id_or_string ) ){
            if ( wpgh_tag_exists( $tag_id_or_string ) ) {
                $tags[] = $tag_id_or_string;
            }
        } else {
            $slug = sanitize_title( $tag_id_or_string );

            if ( wpgh_tag_exists( $slug ) ) {
                $tag = wpgh_get_tag( $slug );
                $tags[] = intval( $tag['tag_id'] );
            } else {
                $tags[] = wpgh_insert_tag( $tag_id_or_string );
            }
        }
    }

    return $tags;
}

/**
 * Get a tag selector to select tags...
 *
 * @param $args array args for the tag selector
 * @return string html content for the selector
 */
function wpgh_dropdown_tags( $args )
{
    $defaults = array(
        'selected' => array(), 'echo' => 1,
        'name' => 'tags[]', 'id' => 'tags',
        'width' => '100%', 'class' => '',
        'show_option_none' => '', 'show_option_no_change' => '',
        'option_none_value' => '', 'required' => false,
        'select2' => true
    );

    $r = wp_parse_args( $args, $defaults );

    $tags = wpgh_get_tags();

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

            $output .= "<option value=\"" . $item['tag_id'] . "\" $selected >" . $item['tag_name'] . " (" . wpgh_count_contact_tag_relationships( 'tag_id', $item['tag_id'] ). ")</option>";
        }

        $output .= "</select>\n";
    }

    if ( $r[ 'select2' ] === true ){

        wp_enqueue_style( 'select2' );
        wp_enqueue_script( 'select2' );

        $output .= "<script>jQuery(document).ready(function(){jQuery( '#" . esc_attr( $r['id'] ) . "' ).select2({tags:true,tokenSeparators: ['/',',',';']})});</script>";
    }


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
    $html = apply_filters( 'wpgh_dropdown_tags', $output, $r, $tags );

    if ( $r['echo'] ) {
        echo $html;
    }

    return $html;
}

/**
 * If the contact is visiting the confirmation page then confirm the email address!
 */
function wpgh_process_email_confirmation()
{
    if ( strpos( $_SERVER[ 'REQUEST_URI' ], '/gh-confirmation/via/email' ) === false )
        return;

    $contact = wpgh_get_the_contact();

    if ( ! $contact )
        return;

    wpgh_update_contact( $contact->get_id(), 'optin_status', WPGH_CONFIRMED );

    $conf_page = get_permalink( get_option( 'gh_confirmation_page' ) );

    do_action( 'wpgh_email_confirmed', $contact->get_id(), wpgh_get_current_funnel() );

    wp_redirect( $conf_page );
    die();
}

add_action( 'init', 'wpgh_process_email_confirmation' );

/**
 * Get the IP address of the current visiotor
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