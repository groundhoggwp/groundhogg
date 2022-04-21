<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use function Groundhogg\do_replacements;
use Groundhogg\Event;
use function Groundhogg\email_kses;
use function Groundhogg\get_default_from_email;
use function Groundhogg\get_default_from_name;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\is_replacement_code_format;
use function Groundhogg\is_sms_plugin_active;
use function Groundhogg\validate_mobile_number;

/**
 * Admin Notification
 *
 * Registers the admin notification step in the funnel builder.
 * USes WP_MAIL to send all notifications
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Notification extends Action {

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/admin-notification/';
	}

	/**
	 * An error if something goes wrong while sending the notification.
	 *
	 * @var \WP_Error
	 */
	private $mail_error;

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Admin Notification', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'admin_notification';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Send an email or SMS notification to any email or list of emails.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/admin-notification.png';
	}

	protected function is_sms() {
		return (bool) $this->get_setting( 'is_sms' );
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		$send_to = $this->get_posted_data( 'send_to' );

		if ( $send_to ) {
			$send_to = sanitize_text_field( $send_to );
			$emails  = array_map( 'trim', explode( ',', $send_to ) );
			$emails  = array_filter( $emails, function ( $email ) {
				return is_email( $email ) || is_replacement_code_format( $email );
			} );

			$send_to = implode( ',', $emails );
			$this->save_setting( 'send_to', $send_to );
		}

		$reply_to = $this->get_posted_data( 'reply_to' );

		if ( $reply_to ) {
			$reply_to = sanitize_text_field( $reply_to );

			$emails   = array_map( 'trim', explode( ',', $reply_to ) );
			$email    = array_shift( $emails );
			$reply_to = ( $email === '{email}' ) ? '{email}' : sanitize_email( $email );
			$this->save_setting( 'reply_to', $reply_to );
		}

		$from = sanitize_email( $this->get_posted_data( 'from' ) );
		$this->save_setting( 'from', $from );


		$this->save_setting( 'hide_admin_links', boolval( $this->get_posted_data( 'hide_admin_links' ) ) );
		$this->save_setting( 'subject', sanitize_text_field( $this->get_posted_data( 'subject' ) ) );
		$this->save_setting( 'note_text', email_kses( $this->get_posted_data( 'note_text' ) ) );
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

		$note = $this->get_setting( 'note_text' );

		$finished_note = sanitize_textarea_field( do_replacements( $note, $contact->get_id() ) );

		$is_sms           = $this->get_setting( 'is_sms', false );
		$hide_admin_links = $this->get_setting( 'hide_admin_links', false );

		if ( ! $hide_admin_links ) {
			$finished_note .= sprintf( "\n\n======== %s ========\nEdit: %s\nReply: %s", __( 'Manage Contact', 'groundhogg' ),
				admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $contact->get_id() ),
				$contact->get_email()
			);
		}

		$subject = $this->get_setting( 'subject' );
		$subject = sanitize_text_field( do_replacements( $subject, $contact->get_id() ) );

		$send_to  = $this->get_setting( 'send_to' );
		$reply_to = do_replacements( $this->get_setting( 'reply_to', $contact->get_email() ), $contact->get_id() );
		$from     = do_replacements( $this->get_setting( 'from', get_default_from_name() ), $contact->get_id() );

		if ( ! is_email( $send_to ) ) {
			$send_to = do_replacements( $send_to, $contact->get_id() );
		}

		if ( ! $send_to ) {
			return false;
		}

		add_action( 'wp_mail_failed', [ $this, 'mail_failed' ] );

		$from_email = is_email( $from ) ? $from : get_default_from_name();

		$headers = [
			sprintf( 'From: %s <%s>', get_default_from_name(), $from_email ),
			"Content-Type: text/html"
		];

		if ( is_email( $reply_to ) ) {
			$headers[] = sprintf( 'Reply-To: %s', $reply_to );
		}

		$sent = \Groundhogg_Email_Services::send_transactional( $send_to, wp_specialchars_decode( $subject ), $finished_note, $headers );

		remove_action( 'wp_mail_failed', [ $this, 'mail_failed' ] );

		if ( $this->has_errors() ) {
			return $this->get_last_error();
		}

		return $sent;

	}

	/**
	 * Map the error to the whatever
	 *
	 * @param $result
	 */
	public function mail_failed( $result ) {
		$this->add_error( $result );
	}
}
