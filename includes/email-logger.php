<?php

namespace Groundhogg;

use Groundhogg\Queue\Event_Queue;

class Email_Logger {

	/**
	 * @var int
	 */
	private static $log_item_id;

	/**
	 * @var Email_Log_Item
	 */
	private static $log_item;


	/**
	 * @var bool
	 */
	private static $is_sensitive = false;

	/**
	 * Email_Logger constructor.
	 */
	public function __construct() {

		// Do last
		add_action( 'phpmailer_init', [ $this, 'phpmailer_init_callback' ], 99 );
		// Do first
		add_action( 'wp_mail_failed', [ $this, 'wp_mail_failed_callback' ], 1 );

		// Whenever retrieve_password happens, then following email should be sensitive
		add_action( 'retrieve_password', [ self::class, 'email_is_sensitive' ] );
	}

	public static function is_enabled() {
		return is_option_enabled( 'gh_log_emails' );
	}

	/**
	 * Clear any persistent data
	 */
	public static function clear() {
		self::$log_item_id = null;
		self::$log_item    = null;
	}

	/**
	 * Set the current log item
	 *
	 * @param $log Email_Log_Item
	 */
	public function set_log( $log ) {
		self::$log_item    = $log;
		self::$log_item_id = $log->get_id();
	}

	/**
	 * Set the current log to be marked as sensitive information
	 *
	 * @return void
	 */
	public static function email_is_sensitive() {
		self::$is_sensitive = true;
	}

	/**
	 * Retrieve the ID of the last log created by the logger
	 *
	 * @return int
	 */
	public static function get_last_log_id() {
		return self::$log_item_id;
	}

	/**
	 * Log any emails sent through calls to PHPMailer
	 * Most Groundhogg plugins use this method, as do many other SMTP plugins.
	 *
	 * @param $phpmailer \PHPMailer
	 */
	public function phpmailer_init_callback( $phpmailer ) {

		if ( ! self::is_enabled() ) {
			return;
		}

		self::clear();

		do_action( 'groundhogg/email_logger/before_create_log', $this );

		$recipients = array_keys( $phpmailer->getAllRecipientAddresses() );

		$headers = [
			[ 'Content-Type', $phpmailer->ContentType ],
			[ 'From', sprintf( "%s <%s>", $phpmailer->FromName, $phpmailer->From ) ],
		];

		if ( $phpmailer->Sender ) {
			$headers[] = [ 'Sender', $phpmailer->Sender ];
		}

		$headers = array_merge( $headers, $phpmailer->getCustomHeaders() );

		$log_data = [
			'recipients'      => $recipients,
			'from_address'    => $phpmailer->From,
			'subject'         => redact( $phpmailer->Subject ?? '' ),
			'content'         => redact( $phpmailer->Body ?? '' ),
			'altbody'         => redact( $phpmailer->AltBody ?? ''),
			'headers'         => $headers,
			'message_type'    => \Groundhogg_Email_Services::get_current_message_type(),
			'email_service'   => \Groundhogg_Email_Services::get_current_email_service(),
			'queued_event_id' => Event_Queue::is_processing() ? event_queue()->get_current_event()->get_id() : false,
			'error_code'      => '',
			'error_message'   => '',
			'status'          => 'sent',
			'is_sensitive'    => self::$is_sensitive,
		];

		$log_data = apply_filters( 'groundhogg/email_logger/before_create_log/log_data', $log_data, $this );

		if ( self::$log_item_id && self::$log_item->exists() ) {
			self::$log_item->update( $log_data );
		} else {
			self::$log_item_id = get_db( 'email_log' )->add( $log_data );
			self::$log_item    = new Email_Log_Item( self::$log_item_id );
		}

		do_action( 'groundhogg/email_logger/after_create_log', self::$log_item, $this );

		// Reset $is_sensitive for the next email log
		if ( self::$is_sensitive ) {
			self::$is_sensitive = false;
		}
	}

	/**
	 * Set the message ID, useful for API senders to log the exact transactional message ID
	 *
	 * @param $msg_id string
	 */
	public static function set_msg_id( $msg_id ) {

		if ( ! self::is_enabled() || ! self::$log_item || ! self::$log_item->exists() ) {
			return;
		}

		self::$log_item->update( [
			'msg_id' => $msg_id
		] );
	}

	/**
	 * Log any errors that occurred to the current email log item.
	 *
	 * @param $error \WP_Error
	 */
	public function wp_mail_failed_callback( $error ) {

		if ( ! self::is_enabled() || ! is_wp_error( $error ) || ! self::$log_item || ! self::$log_item->exists() ) {
			return;
		}

		self::$log_item->update( [
			'status'        => 'failed',
			'error_code'    => $error->get_error_code(),
			'error_message' => $error->get_error_message()
		] );

	}

}
