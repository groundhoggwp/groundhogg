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

function notice_admin_logged_in_testing_warning(){

    if ( ! is_user_logged_in() ){
        return;
    }

	?>
    <div class="notice notice-warning">
        <p><?php printf( __( "You are currently logged in as an <b>%s</b>. This means any actions you take will affect the contact record associated with your user account.", 'groundhogg' ), get_role( array_shift( wp_get_current_user()->roles ) )->name ); ?></p>
        <p><?php _e( "If you are trying to test with another contact record (not the one associated with your user account) use an incognito window, logout, or <a href='https://help.groundhogg.io/article/294-why-is-my-email-not-being-confirmed'>disable logged in user precedence in the settings</a>.", 'groundhogg' ); ?></p>
    </div>
	<?php
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

function notice_unsubscribed(){
	?>
    <div class="notice notice-success">
        <p><?php _e( "You have been unsubscribed.", 'groundhogg' ); ?></p>
    </div>
	<?php
}
