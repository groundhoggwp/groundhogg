<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Plugin;
use WP_REST_Server;
use function Groundhogg\email_kses;
use function Groundhogg\get_default_from_email;
use function Groundhogg\get_default_from_name;
use function Groundhogg\send_email_notification;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Emails_Api extends Base_Object_Api {

	public function register_routes() {
		parent::register_routes();

		register_rest_route( self::NAME_SPACE, "emails/send/", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'send_email' ],
				'permission_callback' => [ $this, 'send_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/emails/(?P<id>\d+)/send", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'send_email_by_id' ],
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


		$contact = self::get_contact_from_request( $request );
		if ( is_wp_error( $contact ) ) {
			return $contact;
		}

		//get email
		$email_id = absint( $request->get_param( 'id' ) );

		if ( ! Plugin::$instance->dbs->get_db( 'emails' )->exists( $email_id ) ) {
			return self::ERROR_400( 'no_email', sprintf( _x( 'Email with ID %d not found.', 'api', 'groundhogg' ), $email_id ) );
		}

		//send emails
		$status = send_email_notification( $email_id, $contact->get_id() );

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
		wp_send_json_error( $error );
	}

	/**
	 * Really basic send email handler
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function send_email( \WP_REST_Request $request ) {

		$to         = sanitize_email( $request->get_param( 'to' ) );
		$from_email = sanitize_email( $request->get_param( 'from_email' ) ) ?: get_default_from_email();
		$from_name  = sanitize_email( $request->get_param( 'from_name' ) ) ?: get_default_from_name();
		$content    = email_kses( $request->get_param( 'content' ) );
		$subject    = sanitize_text_field( $request->get_param( 'subject' ) );
		$type       = sanitize_text_field( $request->get_param( 'type' ) ?: 'marketing' );

		add_action( 'wp_mail_failed', [ $this, 'handle_wp_mail_error' ] );

		$result = \Groundhogg_Email_Services::send_type( $type, $to, $subject, $content, [
			sprintf( "From: %s <%s>", $from_name, $from_email )
		] );

		if ( ! $result ) {
			return self::ERROR_500();
		}

		return self::SUCCESS_RESPONSE();
	}

	public function get_db_table_name() {
		return 'emails';
	}

	public function send_permissions_callback() {
		return current_user_can( 'send_emails' );
	}

	public function read_permissions_callback() {
		return current_user_can( 'view_emails' );
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
