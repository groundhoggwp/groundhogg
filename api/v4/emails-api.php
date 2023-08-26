<?php

namespace Groundhogg\Api\V4;

// Exit if accessed directly
use Groundhogg\Contact;
use Groundhogg\Dynamic_Block_Handler;
use Groundhogg\Email;
use Groundhogg\Event;
use WP_REST_Server;
use function Groundhogg\array_map_to_contacts;
use function Groundhogg\base64_json_decode;
use function Groundhogg\do_replacements;
use function Groundhogg\email_kses;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_default_from_email;
use function Groundhogg\get_default_from_name;
use function Groundhogg\is_sending;
use function Groundhogg\is_template_site;
use function Groundhogg\process_events;
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

		register_rest_route( self::NAME_SPACE, "/{$route}/test", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'sent_test' ],
				'permission_callback' => [ $this, 'send_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/(?P<{$key}>\d+)/preview", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate_preview' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/preview", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate_preview' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/{$route}/blocks/(?P<block_type>\w+)/", [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'render_block' ],
				'permission_callback' => [ $this, 'update_permissions_callback' ]
			],
		] );
	}

	/**
	 * Render a dynamic block
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function render_block( \WP_REST_Request $request ) {

		$block = $request->get_param( 'block_type' );
		$props = base64_json_decode( $request->get_param( 'props' ) );

		$html = Dynamic_Block_Handler::instance()->render_block( $block, $props );

		return self::SUCCESS_RESPONSE( [
			'content' => $html
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

		add_action( 'wp_mail_failed', [ $this, 'handle_wp_mail_error' ] );

		$result = process_events( $contact );

		if ( $result !== true ) {
			return $result[0];
		}

		if ( ! $status ) {
			return self::ERROR_UNKNOWN();
		}

		if ( $this->has_errors() ) {
			return $this->get_last_error();
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

		is_sending( true );

		$to  = array_map( 'sanitize_email', $request->get_param( 'to' ) ?: [] );
		$cc  = array_map( 'sanitize_email', $request->get_param( 'cc' ) ?: [] );
		$bcc = array_map( 'sanitize_email', $request->get_param( 'bcc' ) ?: [] );

		if ( empty( $to ) && empty( $cc ) && empty( $bcc ) ) {
			return self::ERROR_401( 'no_recipients', 'No recipients were defined.' );
		}

		// Get relevant contact records
		$contactRecords = $to;
		$contactRecords = array_map_to_contacts( $contactRecords );
		$contact        = array_shift( $contactRecords );

		$from_email = sanitize_email( $request->get_param( 'from_email' ) ) ?: get_default_from_email();
		$from_name  = sanitize_text_field( $request->get_param( 'from_name' ) ) ?: get_default_from_name();

		$content = $request->get_param( 'content' );

		// Replacements will be based on the first email address provided
		if ( $contact && $contact->exists() ) {
			$content = do_replacements( $content, $contact );
		}

		if ( apply_filters( 'groundhogg/add_custom_footer_text_to_personal_emails', true ) ) {
			$content .= wpautop( get_option( 'gh_custom_email_footer_text' ) );
		}

		$content = email_kses( $content );
		$subject = sanitize_text_field( $request->get_param( 'subject' ) );
		$type    = sanitize_text_field( $request->get_param( 'type' ) ?: 'wordpress' );

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

		if ( $email_id ) {
			$email = new Email( $email_id );

			if ( ! $email->exists() ) {
				return $this->ERROR_RESOURCE_NOT_FOUND();
			}
		} // Temp email
		else {
			$email = new Email();
		}

		$to = array_filter( array_map( 'sanitize_email', wp_parse_list( $request->get_param( 'to' ) ) ) );

		if ( empty( $to ) ) {
			return self::ERROR_401( 'error', 'Invalid email address provided' );
		}

		update_user_meta( get_current_user_id(), 'gh_test_emails', $to );

		// Use the current user as the contact data
		$contact = new Contact( [
			'email' => wp_get_current_user()->user_email
		] );

		if ( $request->has_param( 'data' ) && $request->has_param( 'meta' ) ) {
			// Override with the dump
			$email->data = $request->get_param( 'data' );
			$email->meta = $request->get_param( 'meta' );
		}

		$email->enable_test_mode();

		add_filter( 'groundhogg/email/to', function ( $emails ) use ( $to ) {
			return implode( ',', $to );
		} );

		$sent = $email->send( $contact, new Event() );

		return self::SUCCESS_RESPONSE( [
			'sent' => $sent
		] );
	}

	/**
	 * Takes a dump of an email and generates a preview of the content without saving it
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function generate_preview( \WP_REST_Request $request ) {

		//get email
		$email_id = absint( $request->get_param( $this->get_primary_key() ) );

		if ( $email_id ) {
			$email = new Email( $email_id );

			if ( ! $email->exists() ) {
				return $this->ERROR_RESOURCE_NOT_FOUND();
			}
		} // Temp email
		else {
			$email = new Email();
		}

		// Override with the dump
		$email->data = $request->get_param( 'data' );
		$email->meta = $request->get_param( 'meta' );

		return self::SUCCESS_RESPONSE( [ 'item' => $email ] );
	}

	public function get_db_table_name() {
		return 'emails';
	}

	public function send_permissions_callback() {
		return current_user_can( 'send_emails' );
	}

	public function read_permissions_callback() {
		return is_template_site() || current_user_can( 'view_emails' ) || current_user_can( 'edit_emails' );
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
