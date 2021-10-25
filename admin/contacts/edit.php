<?php
namespace Groundhogg\Admin\Contacts;

// Exit if accessed directly
use Groundhogg\Plugin;
use function Groundhogg\current_user_is;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$id = absint( get_request_var( 'contact' ) );

$contact = get_contactdata( $id );

if ( ! $contact || ! $contact->exists() ) {
	wp_die( _x( 'This contact has been deleted.', 'contact_record', 'groundhogg' ) );
}

// The current user cannot edit this contact because they are not the owner
if ( ! current_user_can( 'view_all_contacts' ) && $contact->get_owner_id() !== get_current_user_id() ) {
	wp_die( _x( 'You are not the owner of this contact.', 'contact_record', 'groundhogg' ) );
}

?>
<div id="app"></div>
