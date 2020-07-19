<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Email;
use function Groundhogg\get_array_var;
use Groundhogg\Preferences;
use Groundhogg\Contact;
use Groundhogg\Event;
use function Groundhogg\get_db;
use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;
use function Groundhogg\managed_page_url;
use function Groundhogg\search_and_replace_domain;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Send Email
 *
 * This will send an email to the contact using WP_MAIL
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WPGH_Email::send()
 * @since       File available since Release 0.9
 */
class Send_Email extends Action {

	const TYPE = 'send_email';

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/send-email/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Send Email', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'send_email';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Send an email to a contact.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/send-email.png';
	}

	/**
	 * Save the settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$email_id = absint( $this->get_posted_data( 'add_email_override', $this->get_posted_data( 'email_id' ) ) );

		$this->save_setting( 'email_id', $email_id );

		$email = new Email( $this->get_setting( 'email_id' ) );

		$email_display = [ 'value' => $email->get_id(), 'label' => $email->get_title() ];

		$this->save_setting( 'email_display', $email_display );
		$this->save_setting( 'is_confirmation_email', $email->is_confirmation_email() );
		$this->save_setting( 'skip_if_confirmed', ( bool ) $this->get_posted_data( 'skip_if_confirmed', false ) );
	}

	/**
	 * Provide the additional edit context
	 *
	 * @param mixed[] $context
	 * @param Step $step
	 *
	 * @return array|mixed[]
	 */
	public function context( $context, $step ) {

		$email_id = $this->get_setting( 'email_id' );

		$email = new Email( $email_id );

		$context[ 'email_display' ] = [
			'label' => $email->get_title(),
			'value' => $email->get_id(),
		];

		$context[ 'email_url_base' ] = managed_page_url( 'emails' );

		return $context;
	}

	/**
	 * Process the apply note step...
	 *
	 * @param $contact Contact
	 * @param $event Event
	 *
	 * @return bool|\WP_Error
	 */
	public function run( $contact, $event ) {

		$email_id = absint( $this->get_setting( 'email_id' ) );
		$email    = Plugin::$instance->utils->get_email( $email_id );

		if ( ! $email ) {
			return new \WP_Error( 'email_dne', 'Invalid email ID provided.' );
		}

		if ( $email->is_confirmation_email() ) {

			if ( $this->get_setting( 'skip_if_confirmed' ) && $contact->get_optin_status() === Preferences::CONFIRMED ) {

				/* This will simply get the upcoming email confirmed step and complete it. No muss not fuss */
				do_action( 'groundhogg/step/email/confirmed', $contact->get_id(), Preferences::CONFIRMED, Preferences::CONFIRMED, $event->get_funnel_id() );

				/* Return false to avoid enqueueing the next step. */

				return false;

			}

		}

		return $email->send( $contact, $event );
	}


	/**
	 * Create a new email and set the step email_id to the ID of the new email.
	 *
	 * @param $step Step
	 * @param $args array list of args to provide criteria for import.
	 */
	public function import( $args, $step ) {

		if ( ! isset_not_empty( $args, 'content' ) || ! isset_not_empty( $args, 'subject' ) ) {
			return;
		}

		if ( ! isset_not_empty( $args, 'content' ) ) {
			$args['pre_header'] = '';
		}

		$email_id = Plugin::$instance->dbs->get_db( 'emails' )->add( [
			'content'    => search_and_replace_domain( $args['content'] ),
			'subject'    => $args['subject'],
			'pre_header' => $args['pre_header'],
			'title'      => get_array_var( $args, 'title', $args['subject'] ),
			'from_user'  => get_current_user_id(),
			'author'     => get_current_user_id()
		] );

		if ( $email_id ) {
			$step->update_meta( 'email_id', $email_id );
		}
	}

	/**
	 * Export all tag related steps
	 *
	 * @param $args array of args
	 * @param $step Step
	 *
	 * @return array of tag names
	 */
	public function export( $args, $step ) {
		$email_id = absint( $step->get_meta( 'email_id' ) );

		$email = Plugin::$instance->utils->get_email( $email_id );

		if ( ! $email || ! $email->exists() ) {
			return $args;
		}

		$args['subject']    = $email->get_subject_line();
		$args['title']      = $email->get_title();
		$args['pre_header'] = $email->get_pre_header();
		$args['content']    = $email->get_content();

		return $args;
	}
}