<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bounce Checker
 *
 * This will add an action to the recurring WPGH_cron_event o check the bounce inbox (if given) for bounced email addresses
 *
 * We have HEAVILY modified the BounceHandler class as it was incompatible at the time of implementation with modern PHP 7
 *
 * @uses BounceHandler
 *
 * @package     Include
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Bounce_Checker {
	/**
	 * The inbox in which bounces are located
	 *
	 * @var mixed|void
	 */
	protected $inbox;

	/**
	 * The inbox password
	 *
	 * @var mixed|void
	 */
	protected $password;

	/**
	 * The bounce handler class
	 *
	 * @var \BounceHandler
	 */
	protected $bounce_handler;

	const ACTION = 'gh_check_bounces';

	public function __construct() {
		/* run whenever these jobs are run */
		add_action( 'init', array( $this, 'setup_cron' ) );

		add_action( self::ACTION, array( $this, 'check' ) );

		if ( is_admin() && get_request_var( 'test_imap_connection' ) ) {
			add_action( 'init', array( $this, 'do_test_connection' ) );
		}
	}

	public function setup_cron() {
		if ( ! wp_next_scheduled( self::ACTION ) ) {
			wp_schedule_event( time(), 'hourly', self::ACTION );
		}
	}

	public function test_connection_ui() {
		$this->setup();

		if ( $this->inbox && $this->password ) {
			?>
			<a href="<?php echo wp_nonce_url( add_query_arg( 'test_imap_connection', '1', $_SERVER['REQUEST_URI'] ) ); ?>"
			   class="button-secondary"><?php _ex( 'Test IMAP Connection', 'action', 'groundhogg' ) ?></a>
			<?php
		}

	}

	/**
	 * @return string|false
	 */
	public function get_bounce_inbox_pw() {
		return Plugin::$instance->settings->get_option( 'bounce_inbox_password' );
	}

	/**
	 * @return string|false
	 */
	public function get_bounce_inbox() {
		return Plugin::$instance->settings->get_option( 'bounce_inbox' );
	}

	/**
	 * @return string
	 */
	public function get_mail_server() {
		return Plugin::$instance->settings->get_option( 'bounce_inbox_host', wp_parse_url( home_url(), PHP_URL_HOST ) );
	}

	/**
	 * @return int
	 */
	public function get_port() {
		return Plugin::$instance->settings->get_option( 'bounce_inbox_port', 993 );
	}

	/**
	 * get the bounce handler
	 *
	 * @return \BounceHandler
	 */
	private function get_bounce_handler() {

		if ( ! $this->bounce_handler ) {

			if ( ! class_exists( '\BounceHandler' ) ) {
				include_once __DIR__ . '/lib/PHP-Bounce-Handler-master/bounce_driver.class.php';
			}

			$this->bounce_handler = new \BounceHandler();
		}

		return $this->bounce_handler;
	}

	/**
	 * Setup the bounce checker
	 */
	private function setup() {
		$this->inbox    = get_option( 'gh_bounce_inbox' );
		$this->password = get_option( 'gh_bounce_inbox_password' );
	}

	/**
	 * Test the bounce inbox connection
	 */
	public function do_test_connection() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( get_request_var( '_wpnonce' ) ) ) {
			return;
		}

		$test = $this->test_connection();

		if ( is_wp_error( $test ) ) {
			Plugin::$instance->notices->add( $test );

			return;
		}

		Plugin::$instance->notices->add( 'imap_success', _x( 'Successful IMAP connection established.', 'notice', 'groundhogg' ) );

	}

	/**
	 * Test the bounce inbox connection
	 *
	 * @return bool|\WP_Error
	 */
	public function test_connection() {

		$this->setup();

		if ( empty( $this->password ) || empty( $this->inbox ) ) {
			return false;
		}

		$domain = explode( '@', $this->inbox );

		if ( ! empty( $domain ) ) {
			$domain = $domain[1];
		}

		$domain = get_option( 'gh_bounce_inbox_host', $domain );
		$port   = get_option( 'gh_bounce_inbox_port', 993 );

		$hostname = sprintf( '{%s:%d/imap/ssl/novalidate-cert}INBOX', $domain, $port );

		if ( ! function_exists( 'imap_open' ) ) {
			return new \WP_Error( 'PHP IMAP library is not installed and is required to use this function.' );
		}

		/* try to connect */
		try {
			$inbox = @\imap_open( $hostname, $this->inbox, $this->password, OP_READONLY );

			if ( $inbox ) {
				\imap_close( $inbox );
			}
		} catch ( \Exception $e ) {
			$inbox = new \WP_Error( $e->getCode(), $e->getMessage() );
		}

		if ( is_wp_error( $inbox ) ) {
			return $inbox;
		}

		if ( ! $inbox ) {
			return new \WP_Error( 'imap_failed', sprintf( "Failed to connect. Error: %s", imap_last_error() ) );
		}

		return true;
	}

	/**
	 * Check the inbox for bounces.
	 */
	public function check() {

		$this->setup();

		if ( ! function_exists( 'imap_open' ) ) {
			return;
		}

		if ( empty( $this->password ) || empty( $this->inbox ) ) {
			return;
		}

		$domain = explode( '@', $this->inbox );

		if ( ! empty( $domain ) ) {
			$domain = $domain[1];
		}

		$domain = \get_option( 'gh_bounce_inbox_host', $domain );


		$port = \get_option( 'gh_bounce_inbox_port', 993 );

		$hostname = sprintf( '{%s:%d/imap/ssl/novalidate-cert}INBOX', $domain, $port );

		/* try to connect */
		$inbox = @\imap_open( $hostname, $this->inbox, $this->password, OP_READONLY );

		if ( ! $inbox ) {
			return;
		}

		/* grab emails, for now assume these messages go unread */
		$emails = @\imap_search( $inbox, sprintf( 'SINCE "%s" UNSEEN', date( 'j F Y', strtotime( '1 day ago' ) ) ) );

		if ( ! $emails ) {
			return;
		}

		$this->get_bounce_handler();

		foreach ( $emails as $email_number ) {

			/* get information specific to this email */
			$message    = @\imap_fetchbody( $inbox, $email_number, "" );
			$multiArray = $this->bounce_handler->get_the_facts( $message );

			foreach ( $multiArray as $the ) {

				$contact = get_contactdata( $the['recipient'] );

				if ( ! is_a_contact( $contact ) ) {
					continue;
				}

				switch ( $the['action'] ) {
					case 'failed':
						//do something
						if ( $contact->get_optin_status() !== Preferences::HARD_BOUNCE ) {
							$contact->add_note( sprintf( $this->bounce_handler->fetch_status_messages( $the['status'] ) ) );
							$contact->change_marketing_preference( Preferences::HARD_BOUNCE );
						}
						break;
					case 'transient':
						//do something else
						break;
					case 'autoreply':
						//do something different
						break;
					default:
						//don't do anything
						break;
				}
			}
		}

		@\imap_close( $inbox );
	}

}