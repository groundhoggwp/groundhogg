<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Classes\Activity;
use Groundhogg\Email;
use Groundhogg\Reporting\New_Reports\Chart_Draw;
use Groundhogg\Reporting\Reporting;
use Groundhogg\Utils\Graph;
use function Groundhogg\get_array_var;
use Groundhogg\Preferences;
use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\Event;
use function Groundhogg\get_db;
use function Groundhogg\isset_not_empty;
use Groundhogg\HTML;
use function Groundhogg\html;
use Groundhogg\Plugin;
use function Groundhogg\percentage;
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
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/send-email.png';
	}

	public function admin_scripts() {}

	/**
	 * Display the settings
	 *
	 * @param $step Step
	 */
	public function settings( $step ) {

		$html = Plugin::$instance->utils->html;

		$email_id = $this->get_setting( 'email_id' );
		$email    = new Email( $email_id );

		$html->start_form_table();

		$html->start_row();

		$html->th( __( 'Select an email to send:', 'groundhogg' ) );
		$html->td( [
			// EMAIL ID DROPDOWN
			$html->dropdown_emails( [
				'name'     => $this->setting_name_prefix( 'email_id' ),
				'id'       => $this->setting_id_prefix( 'email_id' ),
				'selected' => $this->get_setting( 'email_id' ),
			] ),
			// ROW ACTIONS
			"<div style='margin-top: 10px'>",
			// EDIT EMAIL
			$html->button( [
				'title' => 'Edit Email',
				'text'  => _x( 'Edit Email', 'action', 'groundhogg' ),
				'class' => 'button button-primary edit-email',
			] ),
			'&nbsp;',
			// ADD NEW EMAIL
			$html->button( [
				'title' => 'Create New Email',
				'text'  => _x( 'Create New Email', 'action', 'groundhogg' ),
				'class' => 'button button-secondary add-email',
			] ),
			"</div>",
			// ADD EMAIL OVERRIDE
			$html->input( [
				'type'  => 'hidden',
				'name'  => $this->setting_name_prefix( 'add_email_override' ),
				'id'    => $this->setting_id_prefix( 'add_email_override' ),
				'class' => 'add-email-override',
			] )
		] );

		$html->end_row();

		if ( $email && $email->is_confirmation_email() ) {
			$html->add_form_control( [
				'label'       => __( 'Skip if confirmed?', 'groundhogg' ),
				'type'        => HTML::CHECKBOX,
				'field'       => [
					'name'    => $this->setting_name_prefix( 'skip_if_confirmed' ),
					'id'      => $this->setting_id_prefix( 'skip_if_confirmed' ),
					'label'   => __( 'Enable', 'groundhogg' ),
					'checked' => (bool) $this->get_setting( 'skip_if_confirmed' )
				],
				'description' => __( 'Skip to next <b>Email Confirmed</b> benchmark if email is already confirmed.', 'groundhogg' ),
			] );
		}

		$html->end_form_table();
	}

	/**
	 * Save the settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$email_id = absint( $this->get_posted_data( 'email_id' ) );

		$this->save_setting( 'email_id', $email_id );

		$this->save_setting( 'skip_if_confirmed', ( bool ) $this->get_posted_data( 'skip_if_confirmed', false ) );
	}

	/**
	 * Process the apply note step...
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return bool|\WP_Error
	 */
	public function run( $contact, $event ) {

		$email_id = absint( $this->get_setting( 'email_id' ) );
		$email    = new Email( $email_id );

		// No email defined, skip over this step...
		if ( ! $email ) {
			return false;
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

		// legacy import
		if ( isset_not_empty( $args, 'content' ) ) {

			$args = wp_parse_args( $args, [
				'content'    => '',
				'subject'    => '',
				'pre_header' => '',
				'title'      => '',
				'from_user'  => get_current_user_id(),
				'author'     => get_current_user_id()
			] );

			$email_id = get_db( 'emails' )->add( $args );

			if ( $email_id ) {
				$step->update_meta( 'email_id', $email_id );
			}

			return;
		}

		// New import
		if ( isset_not_empty( $args, 'email' ) ) {

			$email = new Email();

			// override the from user and author as if the is coming
			// from a different site we don't want to use those values
			$data = array_merge( $args['email']['data'], [
				'from_user' => get_current_user_id(),
				'author'    => get_current_user_id()
			] );

			$email->create( $data );
			$email->update_meta( $args['email']['meta'] );

			$step->update_meta( 'email_id', $email->get_id() );
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

		$email = new Email( $email_id );

		if ( ! $email || ! $email->exists() ) {
			return $args;
		}

		$args['email'] = $email;

		return $args;
	}
}