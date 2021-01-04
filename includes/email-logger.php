<?php

namespace Groundhogg;

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
	 * Email_Logger constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Lazy load the initial actions so we can check that email logging is enabled.
	 */
	public function init() {
		if ( is_option_enabled( 'gh_log_emails' ) ) {
			// Do last
			add_action( 'phpmailer_init', [ $this, 'phpmailer_init_callback' ], 99 );
			// Do first
			add_action( 'wp_mail_failed', [ $this, 'wp_mail_failed_callback' ], 1 );
		}
	}

	/**
	 * Clear any persistent data
	 */
	public static function clear() {
		self::$log_item_id = null;
		self::$log_item    = null;
	}

	/**
	 * Log any emails sent through calls to PHPMailer
	 * Most Groundhogg plugins use this method, as do many other SMTP plugins.
	 *
	 * @param $phpmailer \PHPMailer
	 */
	public function phpmailer_init_callback( $phpmailer ) {

		self::clear();

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
			'recipients'    => $recipients,
			'from_address'  => $phpmailer->From,
			'subject'       => $phpmailer->Subject,
			'content'       => $phpmailer->Body,
			'headers'       => $headers,
			'message_type'  => \Groundhogg_Email_Services::get_current_message_type(),
			'email_service' => \Groundhogg_Email_Services::get_current_email_service(),
			'error_code'    => '',
			'error_message' => '',
			'status'        => 'sent'
		];

		self::$log_item_id = get_db( 'email_log' )->add( $log_data );
		self::$log_item    = new Email_Log_Item( self::$log_item_id );
	}

	/**
	 * Log any errors that occurred to the current email log item.
	 *
	 * @param $error \WP_Error
	 */
	public function wp_mail_failed_callback( $error ) {

		if ( ! is_wp_error( $error ) || ! self::$log_item ) {
			return;
		}

		self::$log_item->update( [
			'status'        => 'failed',
			'error_code'    => $error->get_error_code(),
			'error_message' => $error->get_error_message()
		] );

	}

}