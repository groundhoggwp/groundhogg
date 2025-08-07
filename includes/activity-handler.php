<?php

namespace Groundhogg;

use Groundhogg\Form\Submission_Handler;
use function Groundhogg\Admin\Reports\Views\get_funnel_id;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * handles various site activities and tracks them
 *
 * Class Activity_Handler
 * @package Groundhogg
 */
class Activity_Handler {

	function __construct() {
		// Core WordPress
		// todo account created
		// todo account updated
		// todo user role changed
		add_action( 'wp_login', [ $this, 'wp_login' ], 11 );
		add_action( 'wp_logout', [ $this, 'wp_logout' ], 11 );

		add_action( 'groundhogg/form/submission_handler/after', [ $this, 'handle_form_submission' ], 10, 3 );
	}

	/**
	 * When someone logs into WordPress
	 */
	function wp_login() {
		track_live_activity( 'wp_login' );
	}

	/**
	 * When someone logs out of WordPress
	 */
	function wp_logout() {
		track_live_activity( 'wp_logout' );
	}

	/**
	 * Handles a form submission Via Groundhogg
	 *
	 * @param $submission Submission
	 * @param $contact    Contact
	 * @param $handler    Submission_Handler
	 */
	function handle_form_submission( $submission, $contact, $handler ) {

		track_activity( $contact, 'form_submission', [
			'funnel_id' => $handler->get_step()->get_funnel_id(),
			'step_id'   => $handler->get_step()->get_id()
		], [
			'form_id'       => $submission->get_step_id(),
			'submission_id' => $submission->get_id()
		] );

	}

}
