<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Send Email
 *
 * This will send an email to the contact using WP_MAIL
 *
 * @since       File available since Release 0.9
 * @see         WPGH_Email::send()
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 * @subpackage  Elements/Actions
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

	public function get_sub_group() {
		return 'comms';
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
//		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/send.svg';
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/send-email.svg';
	}

	public function admin_scripts() {
		wp_enqueue_script( 'groundhogg-funnel-email' );
		wp_localize_script( 'groundhogg-funnel-email', 'EmailStep', array(
			'edit_email_path'     => admin_url( 'admin.php?page=gh_emails&action=edit' ),
			'add_email_path'      => admin_url( 'admin.php?page=gh_emails&action=add' ),
			'save_changes_prompt' => _x( "You have changes which have not been saved. Are you sure you want to exit?", 'notice', 'groundhogg' ),
		) );
	}

	/**
	 * Display the settings
	 *
	 * @param $step Step
	 */
	public function settings( $step ) {

		echo html()->e( 'div', [ 'id' => 'step_' . $step->get_id() . '_send_email' ], '', false )

		?><p></p><?php
	}

	public function validate_settings( Step $step ) {
		$email = new Email( $this->get_setting( 'email_id' ) );

		if ( ! $email->exists() ) {
			$step->add_error( 'email_dne', __( 'You have not selected an email!', 'groundhogg' ) );
		}

		if ( ( $email->is_draft() && $step->get_funnel()->is_active() ) ) {
			$step->add_error( 'email_in_draft_mode', __( 'The selected email is in draft mode! It will not be sent and will cause automation to stop.' ) );
		}
	}

	/**
	 * Save the settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
//		$email_id = absint( $this->get_posted_data( 'add_email_override', $this->get_posted_data( 'email_id' ) ) );
//
//		$this->save_setting( 'email_id', $email_id );
//
//		$email = new Email( $this->get_setting( 'email_id' ) );
//
//		if ( ! $email->exists() ) {
//			$this->add_error( 'email_dne', __( 'You have not selected an email to send in one of your steps.', 'groundhogg' ) );
//		}
//
//		if ( ( $email->is_draft() && $step->get_funnel()->is_active() ) ) {
//			$this->add_error( 'email_in_draft_mode', __( 'You still have emails in draft mode! These emails will not be sent and will cause automation to stop.' ) );
//		}
//
//		$this->save_setting( 'skip_if_confirmed', ( bool ) $this->get_posted_data( 'skip_if_confirmed', false ) );
	}

	public function generate_step_title( $step ) {

		$email = new Email( $this->get_setting( 'email_id' ) );

		if ( ! $email->exists() ) {
			return 'Send an email';
		}

		return sprintf( __( 'Send %s', 'groundhogg' ), '<b>' . $email->get_title() . '</b>' );
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

		if ( ! $email->exists() ) {
			return new \WP_Error( 'email_dne', 'Invalid email ID provided.' );
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

		// Not doing new import
		if ( ! isset_not_empty( $args, 'email' ) ) {
			// Legacy import
			if ( isset_not_empty( $args, 'content' ) ){
				$this->legacy_import( $args, $step );
			}
			return;
		}

		$raw_email = json_decode( wp_json_encode( $args['email'] ), true );
		$data      = wp_array_slice_assoc( $raw_email['data'], [ 'title', 'subject', 'pre_header', 'content' ] );
		$meta      = $raw_email['meta'];

		$email = new Email();

		$email->create( $data );
		$email->update_meta( $meta );

		$step->update_meta( 'email_id', $email->get_id() );
	}

	/**
	 * Create a new email and set the step email_id to the ID of the new email.
	 *
	 * @param $step Step
	 * @param $args array list of args to provide criteria for import.
	 */
	public function legacy_import( $args, $step ) {

		if ( ! isset_not_empty( $args, 'content' ) || ! isset_not_empty( $args, 'subject' ) ) {
			return;
		}

		if ( ! isset_not_empty( $args, 'content' ) ) {
			$args['pre_header'] = '';
		}

		$email_id = get_db( 'emails' )->add( [
			'content'    => $args['content'],
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

		$email = new Email( $email_id );

		if ( ! $email->exists() ) {
			return $args;
		}

		$args['email'] = $email;

		return $args;
	}
}
