<?php
namespace Groundhogg;

define( 'GROUNDHOGG_IS_BROWSER_VIEW', true );

$email_id = absint( get_query_var( 'email_id' ) );

if ( ! $email_id ){
    wp_die( __( 'Could not load this email', 'groundhogg' ), __( 'Error loading email.', 'groundhogg' ) );
}

$email = new Email( $email_id );

if ( ! $email ){
    wp_die( 'Invalid email.' );
}

$email->set_contact( get_contactdata() );
$email->set_event( Plugin::$instance->tracking->get_current_event() );

status_header( 200 );
header( 'Content-Type: text/html; charset=utf-8' );
nocache_headers();

disable_emojis();

add_action( 'groundhogg/templates/email/head/after', function(){
	?>
<style>
	body{padding: 0 10px !important;}
</style>
		<?php
} );

if ( get_url_var( 'plain' ) ){
	?><pre><?php esc_html_e( $email->get_merged_alt_body() ); ?></pre><?php
} else {
	echo $email->build();
}
