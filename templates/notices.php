<?php

namespace Groundhogg\Notices;

use function Groundhogg\bold_it;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Redirect the contact and add a notice.
 *
 * @param $url
 * @param $notice
 */
function redirect_with_notice( $url, $notice ){
	wp_safe_redirect( add_query_arg( 'notice', $notice, $url ) );
    exit;
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
        <p><?php
		    printf(
		        /* translators: 1: the current user's role name */
                esc_html__( 'You are currently logged in as an %s. This means any actions you take will affect the contact record associated with your user account.', 'groundhogg' ),
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated HTML
                bold_it( esc_html( get_role( array_shift( wp_get_current_user()->roles ) )->name ) ) );
            ?></p>
        <p><?php printf(
	            /* translators: 1: open <a>, 2: closing </a> */
                esc_html__( 'If you are trying to test with another contact record (not the one associated with your user account) use an incognito window, logout, or %1$sdisable logged in user precedence in the settings%2$s.', 'groundhogg' ),
                "<a href='https://help.groundhogg.io/article/294-why-is-my-email-not-being-confirmed'>",
                "</a>"
            ); ?></p>
    </div>
	<?php
}

function notice_general_issue_message(){
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( "There was an issue processing your request.", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_email_issue(){
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( "Email failed to send due to an error!", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_general_success_message(){
	?>
	<div class="notice notice-success">
		<p><?php esc_html_e( "Your request was processed!", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_preferences_link_sent(){
	?>
	<div class="notice notice-success">
		<p><?php esc_html_e( "An email with a special link has been sent to your inbox.", 'groundhogg' ); ?></p>
	</div>
	<?php
}


function notice_gdpr_email_sent(){
	?>
	<div class="notice notice-success">
		<p><?php esc_html_e( "A transcript of your contact profile has been sent to your inbox.", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_email_verification_required(){
	?>
	<div class="notice notice-warning">
		<p><?php esc_html_e( "You must verify your email address.", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_profile_updated(){
	?>
	<div class="notice notice-success">
		<p><?php esc_html_e( "Your profile has been updated.", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_preferences_updated(){
	?>
	<div class="notice notice-success">
		<p><?php esc_html_e( "Your preferences have been updated.", 'groundhogg' ); ?></p>
	</div>
	<?php
}

function notice_unsubscribed(){
	?>
    <div class="notice notice-success">
        <p><?php esc_html_e( "You have been unsubscribed.", 'groundhogg' ); ?></p>
    </div>
	<?php
}
