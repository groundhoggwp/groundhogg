<?php

namespace Groundhogg;

use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Query\Query;
use Webklex\PHPIMAP\Query\WhereQuery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Imap_Inbox {
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

	public function __construct() {

		if ( is_admin() && get_request_var( 'test_imap_connection' ) ) {
			add_action( 'init', array( $this, 'do_test_connection' ) );
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
	public function get_inbox_pw() {
		return get_option( 'gh_imap_inbox_password' );
	}

	/**
	 * @return string|false
	 */
	public function get_username() {
		return get_option( 'gh_imap_inbox_address' );
	}

	/**
	 * @return string
	 */
	public function get_mail_server() {
		return get_option( 'gh_imap_inbox_host', wp_parse_url( home_url(), PHP_URL_HOST ) );
	}

	/**
	 * @return int
	 */
	public function get_port() {
		return get_option( 'gh_imap_inbox_port', 993 );
	}

	/**
	 * Setup the bounce checker
	 */
	private function setup() {
		$this->inbox    = $this->get_username();
		$this->password = $this->get_inbox_pw();
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

		$domain = $this->get_mail_server();
		$port   = $this->get_port();

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

	public function use_php_imap( $contact ) {

		$cm = new ClientManager();

		$client = $cm->make( [
			'host'          => $this->get_mail_server(),
			'port'          => $this->get_port(),
			'encryption'    => 'ssl',
			'validate_cert' => false,
			'username'      => $this->get_username(),
			'password'      => $this->get_inbox_pw(),
			'protocol'      => 'imap'
		] );

		$client->connect();

		$inbox = $client->getFolder('INBOX');

		$msgs = $inbox->query()->where([
			'TO' => $contact->email,
			'FROM' => $contact->email,
		])->get();

		$client->disconnect();

		$msgs = array_map( function ( $msg ) {
			return [
				'subject' => ''
			];
		}, $msgs );

		return $msgs;

	}

	/**
	 * Get emails associated with the contact record
	 *
	 * @param $contact Contact
	 *
	 * @return array
	 */
	public function fetch( $contact ) {

		return $this->use_php_imap( $contact );

		$this->setup();

		if ( ! function_exists( 'imap_open' ) ) {
			return [];
		}

		if ( empty( $this->password ) || empty( $this->inbox ) ) {
			return [];
		}

		$domain = $this->get_mail_server();

		$port     = $this->get_port();
		$hostname = sprintf( '{%s:%d/imap/ssl/novalidate-cert}INBOX', $domain, $port );

		if ( ! function_exists( 'imap_open' ) ) {
			return [];
		}

		/* try to connect */
		try {
			$inbox = @\imap_open( $hostname, $this->inbox, $this->password, OP_READONLY );
		} catch ( \Exception $e ) {
			return [];
		}

		if ( ! $inbox ) {
			return [];
		}

		$emails = array_values( array_unique( array_merge(
			@\imap_search( $inbox, sprintf( 'FROM "%s"', $contact->get_email() ) ) ?: [],
			@\imap_search( $inbox, sprintf( 'TO "%s"', $contact->get_email() ) ) ?: []
		) ) );

//		$emails = imap_fetch_overview( $hostname, implode( ',', $emails ), 0 );

//		var_dump( $emails );

		@\imap_close( $inbox );

		return $emails;
	}

}