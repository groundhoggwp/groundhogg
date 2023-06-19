<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once __DIR__ . '/../managed-page.php';

$contact         = get_contactdata();
$permissions_key = get_permissions_key( 'view_archive', true );

if ( ! $contact ) {

	// Create a new contact record for the current user if they are an admin
	if ( is_user_logged_in() && current_user_can( 'add_contacts' ) ) {
		$contact = create_contact_from_user( wp_get_current_user() );
	}

	// if still no contact, die
	if ( ! $contact ) {
		wp_die( 'No contact record available for preview...' );
	}
}

// Check permissions...
// can view emails
// is logged in
// Or has permissions key
if ( current_user_can( 'view_emails' ) || current_contact_and_logged_in_user_match() || check_permissions_key( $permissions_key, $contact, 'view_archive' ) ):

	$event_id = absint( get_query_var( 'event_id' ) );
	$event       = new Event( $event_id, 'events', 'queued_id' );

	// Event does not exist, or mismatched contact ID
	if ( ! $event->exists() || $event->get_contact_id() !== $contact->get_id() ) {
		wp_die( 'Unable to view archive...' );
	}

	$email_id = $event->email_id;
	$email    = new Email( $email_id );

	if ( ! $email->exists() ) {
		wp_die( __( 'Could not load email...' ) );
	}

	$email->set_contact( $contact );
	$email->set_event( $event );

	$subject = $email->get_merged_subject_line();

	managed_page_head( $subject, 'view' );

	?>
	<div class="box">
		<h2 class="subject-line"><?php _e( $subject ); ?></h2>
		<iframe width="100%" id="email-preview"></iframe>
		<script>

          const setFrameContent = (iframe, content) => {
            var blob = new Blob([content], { type: 'text/html; charset=utf-8' })
            iframe.onload = () => {
              iframe.style.height = iframe.contentWindow.document.body.offsetHeight + 10 + 'px'
            }
            iframe.src = URL.createObjectURL(blob)
          }

          let iframe = document.getElementById('email-preview')

          let email = <?php echo wp_json_encode( $email ); ?>;

          setFrameContent(iframe, email.context.built)
		</script>
	</div>
	<?php

	add_filter( 'groundhogg/managed_page/footer_links', function ( $links ) {

		$links[] = html()->e( 'a', [
			'href' => managed_page_url( 'archive' )
		], __( 'Email Archive', 'groundhogg' ) );

		return $links;
	} );

	managed_page_footer();

else:

	include __DIR__ . '/../preferences.php';

endif;