<?php

namespace Groundhogg\AI;

use function Groundhogg\base64url_encode;
use function Groundhogg\get_master_license;
use function Groundhogg\remote_post_json;
use function Groundhogg\verify_admin_ajax_nonce;

/**
 * Generate an email address where the user can forward an email to and Groundhogg will convert that email into blocks for them.
 *
 * @return void
 */
function ajax_generate_ai_converter_email_address() {

	if ( ! verify_admin_ajax_nonce() ) {
		wp_send_json_error();
	}

	$license = get_master_license();

	if ( ! $license ) {
		wp_send_json_error();
	}

	// the `/ready` endpoint preloads a job_id without actually sending anything to the AI,
	// and links it with a pre-determined email address so that we can poll the result
	$res = remote_post_json( "https://ai.groundhogg.io/ready", [
		"license_key" => $license
	] );

	if ( is_wp_error( $res ) ) {
		wp_send_json_error( $res );
	}

	if ( empty( $res->job_id ) || empty( $res->prefix ) ) {
		wp_send_json_error( $res );
	}

	$job_id       = $res->job_id;
	$email_prefix = $res->prefix;

	wp_send_json( [
		'job_id' => $job_id,
		'email'  => $email_prefix. '@ai.groundhogg.io'
	] );
}

add_action( 'wp_ajax_gh_generate_ai_converter_email_address', __NAMESPACE__ . '\ajax_generate_ai_converter_email_address' );
