<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Plugin;
use WP_REST_Server;
use function Groundhogg\email_kses;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_default_from_email;
use function Groundhogg\get_default_from_name;
use function Groundhogg\is_template_site;
use function Groundhogg\send_email_notification;
use function Groundhogg\set_user_test_email;
use function Groundhogg\track_activity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Emails_Api extends Base_Object_Api {

	public function register_routes() {
		parent::register_routes();

		$key   = $this->get_primary_key();
		$route = $this->get_route();

		register_rest_route( self::NAME_SPACE, "emails/send", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'send_email' ],
				'permission_callback' => [ $this, 'send_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/send", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'send_email_by_id' ],
				'permission_callback' => [ $this, 'send_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/test", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'sent_test' ],
				'permission_callback' => [ $this, 'send_permissions_callback' ]
			],
		] );
	}


	/**
	 * Send emails to the contact based on email and contact ID
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function send_email_by_id( \WP_REST_Request $request ) {

		//get email
		$email_id = absint( $request->get_param( $this->get_primary_key() ) );

		$email = new Email( $email_id );

		if ( ! $email->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$to      = $request->get_param( 'to' );
		$contact = get_contactdata( $to );

		if ( ! $contact ) {
			return self::ERROR_404( 'error', 'Contact not found' );
		}

		//send emails
		$status = send_email_notification( $email, $contact, $request->get_param( 'when' ) );

		if ( ! $status ) {
			return self::ERROR_UNKNOWN();
		}

		return self::SUCCESS_RESPONSE();
	}


	/**
	 * If there was an issue with WP mail send it straight away
	 *
	 * @param $error
	 */
	public function handle_wp_mail_error( $error ) {
		$this->add_error( $error );
	}

	/**
	 * Really basic send email handler
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function send_email( \WP_REST_Request $request ) {

		$to  = array_map( 'sanitize_email', $request->get_param( 'to' ) ?: [] );
		$cc  = array_map( 'sanitize_email', $request->get_param( 'cc' ) ?: [] );
		$bcc = array_map( 'sanitize_email', $request->get_param( 'bcc' ) ?: [] );

		if ( empty( $to ) && empty( $cc ) && empty( $bcc ) ) {
			return self::ERROR_401( 'no_recipients', 'No recipients were defined.' );
		}

		$from_email = sanitize_email( $request->get_param( 'from_email' ) ) ?: get_default_from_email();
		$from_name  = sanitize_text_field( $request->get_param( 'from_name' ) ) ?: get_default_from_name();

		$content = email_kses( $request->get_param( 'content' ) );
		$subject = sanitize_text_field( $request->get_param( 'subject' ) );

		$type = sanitize_text_field( $request->get_param( 'type' ) ?: 'wordpress' );

		$headers = [
			'Content-Type: text/html',
			sprintf( "From: %s <%s>", $from_name, $from_email ),
		];

		if ( ! empty( $cc ) ) {
			$headers[] = 'Cc: ' . implode( ',', $cc );
		}

		if ( ! empty( $bcc ) ) {
			$headers[] = 'Bcc: ' . implode( ',', $bcc );
		}

		add_action( 'wp_mail_failed', [ $this, 'handle_wp_mail_error' ] );

		$result = \Groundhogg_Email_Services::send_type( $type, $to, $subject, $content, $headers );

		if ( $this->has_errors() ) {
			return $this->get_last_error();
		}

		if ( ! $result ) {
			return self::ERROR_500();
		}

		$all_recipients = array_unique( array_merge( $to, $bcc, $cc ) );

		foreach ( $all_recipients as $recipient ) {
			$contact = get_contactdata( $recipient );

			if ( ! $contact ) {
				continue;
			}

			track_activity( $contact, 'composed_email_sent', [], [
				'subject' => $subject,
				'from'    => $from_email,
				'sent_by' => get_current_user_id()
			] );
		}

		return self::SUCCESS_RESPONSE();
	}

	/**
	 * Send a test email address
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function sent_test( \WP_REST_Request $request ) {

		//get email
		$email_id = absint( $request->get_param( $this->get_primary_key() ) );

		$email = new Email( $email_id );

		if ( ! $email->exists() ) {
			return $this->ERROR_RESOURCE_NOT_FOUND();
		}

		$to = sanitize_email( $request->get_param( 'to' ) );

		if ( ! is_email( $to ) ) {
			return self::ERROR_401( 'error', 'Invalid email address provided' );
		}

		set_user_test_email( $to );

		$contact = new Contact( [
			'email' => $to
		] );

		$email->enable_test_mode();

		$sent = $email->send( $contact, new Event() );

		return self::SUCCESS_RESPONSE( [
			'sent' => $sent
		] );
	}

	public function get_db_table_name() {
		return 'emails';
	}

	public function send_permissions_callback() {
		return current_user_can( 'send_emails' );
	}

	public function read_permissions_callback() {
		return is_template_site() || current_user_can( 'edit_emails' );
	}

	public function update_permissions_callback() {
		return current_user_can( 'edit_emails' );
	}

	public function create_permissions_callback() {
		return current_user_can( 'add_emails' );
	}

	public function delete_permissions_callback() {
		return current_user_can( 'delete_emails' );
	}
}
