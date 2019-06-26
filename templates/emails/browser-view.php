<?php
namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit;

include GROUNDHOGG_PATH . 'templates/managed-page.php';

$email_id = absint( get_query_var( 'email_id' ) );
$email = Plugin::$instance->utils->get_email( $email_id );

if ( ! $email ){
    wp_die( __( 'Could not load email...' ) );
}

$contact = Plugin::$instance->tracking->get_current_contact();
$email->set_contact( $contact );

$subject = $email->get_merged_subject_line();

managed_page_head( $subject, 'view' );

?>
<div class="box">
    <iframe width="100%" src="<?php echo esc_url( site_url( 'gh/emails/' . $email_id ) ); ?>"></iframe>
</div>
<?php

managed_page_footer();
