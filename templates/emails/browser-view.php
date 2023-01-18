<?php
namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit;

include GROUNDHOGG_PATH . 'templates/managed-page.php';

$email_id = absint( get_query_var( 'email_id' ) );
$email = new Email( $email_id );

if ( ! $email ){
    wp_die( __( 'Could not load email...' ) );
}

$contact = get_contactdata();

if ( ! $contact ){

    // Create a new contact record for the current user if they are an admin
    if ( is_user_logged_in() && current_user_can( 'add_contacts' ) ){
        $contact = create_contact_from_user( wp_get_current_user() );
    }

    // if still no contact, die
    if ( ! $contact ){
	    wp_die( 'No contact record available for preview...' );
    }
}

$email->set_contact( $contact );

$subject = $email->get_merged_subject_line();

managed_page_head( $subject, 'view' );

?>
<div class="box">
    <iframe width="100%" src="<?php echo esc_url( managed_page_url( 'emails/' . $email_id ) ); ?>"></iframe>
</div>
<?php

managed_page_footer();
