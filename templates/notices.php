<?php

namespace Groundhogg\Notices;

/**
 * Redirect the contact and add a notice.
 *
 * @param $url
 * @param $notice
 */
function wp_redirect_with_notice( $url, $notice ){
	die( wp_safe_redirect( add_query_arg( 'notice', $notice, $url ) ) );
}

/**
 * Prints the notices
 */
function print_notices(){
	do_action( 'groundhogg/managed_page_notices' );
}

/**
 * Add a notice to appear above the managed page...
 *
 * @param $callback
 */
function add_notice( $callback ){

	$func = __NAMESPACE__ . '\\' . $callback;

	if ( ! function_exists( $func ) ){
		return;
	}

	add_action( 'groundhogg/managed_page_notices', $func );
}

function notice_general_issue_message(){
	?>
	<div class="notice notice-error">
		<p><?php _e( "There was an issue processing your request.", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_general_success_message(){
	?>
	<div class="notice notice-success">
		<p><?php _e( "Your request was processed!", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_preferences_link_sent(){
	?>
	<div class="notice notice-success">
		<p><?php _e( "An email with a special link has been sent to your inbox.", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_gdpr_email_sent(){
	?>
	<div class="notice notice-success">
		<p><?php _e( "A transcript of your contact profile has been sent to your inbox.", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_email_verification_required(){
	?>
	<div class="notice notice-warning">
		<p><?php _e( "You must verify your email address.", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_profile_updated(){
	?>
	<div class="notice notice-success">
		<p><?php _e( "Your profile has been updated.", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_preferences_updated(){
	?>
	<div class="notice notice-success">
		<p><?php _e( "Your preferences have been updated.", 'groundhogg' ); ?></p>
	</div>
	<?php
}

