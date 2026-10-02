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

	const ACTION = 'groundhogg/check_bounces';

	/**
	 * What the last run found, for the settings page, and where the cron job carries on from next time
	 */
	const LAST_RUN_OPTION = 'gh_bounce_last_run';
	const CURSOR_OPTION   = 'gh_bounce_checked_until';

	const NONCE_ACTION = 'gh_handle_bounces_now';

	/**
	 * How far back the cron job looks past where it got to, the date of an email isn't always the order it arrived in
	 */
	const CRON_OVERLAP = 6 * HOUR_IN_SECONDS;

	public function __construct() {

		/* run whenever these jobs are run */
		add_action( 'groundhogg/cleanup', [ $this, 'check' ] );

		if ( is_admin() && get_request_var( 'test_imap_connection' ) ) {
			add_action( 'init', array( $this, 'do_test_connection' ) );
		}

		if ( is_admin() && get_request_var( 'handle_bounces_now' ) ) {
			add_action( 'init', array( $this, 'do_handle_bounces_now' ) );
		}
	}

	public function test_connection_ui() {
		$this->setup();

		if ( $this->inbox && $this->password ) {
			?>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'test_imap_connection', '1', get_request_uri() ) ) ); ?>"
			   class="button-secondary"><?php echo esc_html_x( 'Test IMAP Connection', 'action', 'groundhogg' ) ?></a>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'handle_bounces_now', '1', get_request_uri() ), self::NONCE_ACTION ) ); ?>"
			   class="button-secondary"><?php echo esc_html_x( 'Handle Bounces Now', 'action', 'groundhogg' ) ?></a>
			<?php

			$last = get_option( self::LAST_RUN_OPTION );

			if ( is_array( $last ) && ! empty( $last['time'] ) ) {
				?>
				<p class="description">
					<?php echo esc_html( sprintf(
					/* translators: 1: when, 2: what was found */
						__( 'Last run %1$s: %2$s', 'groundhogg' ),
						wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last['time'] ),
						$this->summarize( (array) ( $last['counts'] ?? [] ) )
					) ); ?>
				</p>
				<?php if ( ! empty( $last['details'] ) ): ?>
					<details>
						<summary><?php esc_html_e( 'What was decided for each recipient', 'groundhogg' ); ?></summary>
						<ul>
							<?php foreach ( $last['details'] as $row ): ?>
								<li><?php echo esc_html( sprintf(
								/* translators: 1: email address, 2: decision, 3: action from the report, 4: status code from the report */
									__( '%1$s: %2$s (action: %3$s, status: %4$s)', 'groundhogg' ),
									$row['recipient'] ?: '-',
									$row['decision'],
									$row['action'] ?: '-',
									$row['status'] ?: '-'
								) ); ?></li>
							<?php endforeach; ?>
						</ul>
					</details>
				<?php endif;
			}
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

		Plugin::$instance->notices->add( 'imap_success', esc_html_x( 'Successful IMAP connection established.', 'notice', 'groundhogg' ) );

	}

	/**
	 * The IMAP mailbox to look in
	 *
	 * @return string
	 */
	protected function get_hostname() {

		$domain = explode( '@', (string) $this->inbox );
		$domain = $domain[1] ?? '';
		$domain = \get_option( 'gh_bounce_inbox_host', $domain );
		$port   = \get_option( 'gh_bounce_inbox_port', 993 );

		return sprintf( '{%s:%d/imap/ssl/novalidate-cert}INBOX', $domain, $port );
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

		if ( ! function_exists( 'imap_open' ) ) {
			return new \WP_Error( 'PHP IMAP library is not installed and is required to use this function.' );
		}

		/* try to connect */
		try {
			$inbox = @\imap_open( $this->get_hostname(), $this->inbox, $this->password, OP_READONLY );

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
	 * Check the inbox for bounces, this is what the cron job does. It carries on from where the last run got to and
	 * doesn't depend on whether the email was read, someone opening the inbox shouldn't stop a bounce from counting.
	 */
	public function check() {
		$this->run( false );
	}

	/**
	 * Check the inbox now, looking further back than the cron job does
	 *
	 * @return array|\WP_Error see run()
	 */
	public function check_now() {
		return $this->run( true );
	}

	/**
	 * Handle the Handle Bounces Now button on the settings page
	 */
	public function do_handle_bounces_now() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( get_request_var( '_wpnonce' ), self::NONCE_ACTION ) ) {
			return;
		}

		$result = $this->check_now();

		if ( is_wp_error( $result ) ) {
			Plugin::$instance->notices->add( $result );

			return;
		}

		Plugin::$instance->notices->add( 'bounces_handled', esc_html( $this->summarize( $result['counts'] ) ) );
	}

	/**
	 * What a run found, in a sentence
	 *
	 * @param array $counts
	 *
	 * @return string
	 */
	public function summarize( array $counts ) {

		$counts = array_merge( [ 'messages' => 0, 'hard' => 0, 'soft' => 0, 'already' => 0, 'not_found' => 0, 'skipped' => 0 ], $counts );

		return sprintf(
		/* translators: 1: emails looked at, 2: hard bounces, 3: soft bounces, 4: contacts that already were bounced, 5: recipients that are not contacts, 6: not understood */
			__( '%1$d emails checked. %2$d marked as bounced, %3$d soft bounces left alone, %4$d already bounced, %5$d recipients not found in your contacts, %6$d not understood.', 'groundhogg' ),
			$counts['messages'],
			$counts['hard'],
			$counts['soft'],
			$counts['already'],
			$counts['not_found'],
			$counts['skipped']
		);
	}

	/**
	 * What a failure report means for the recipient. A permanent failure is a hard bounce. A temporary one, like a full
	 * mailbox (4.2.2), is not, whatever its action says, so it never marks the contact as bounced.
	 *
	 * @param array $the action and status as the bounce handler found them
	 *
	 * @return string hard, soft or skip
	 */
	public static function classify( array $the ) {

		$action = strtolower( trim( (string) ( $the['action'] ?? '' ) ) );
		$status = trim( (string) ( $the['status'] ?? '' ) );

		if ( preg_match( '/^4\./', $status ) ) {
			return 'soft';
		}

		if ( $action === 'failed' || preg_match( '/^5\./', $status ) ) {
			return 'hard';
		}

		if ( in_array( $action, [ 'transient', 'delayed' ], true ) ) {
			return 'soft';
		}

		return 'skip';
	}

	/**
	 * Look at one failure report for a recipient, and mark the contact as bounced if it's a hard bounce
	 *
	 * @param array $the what the bounce handler found: recipient, action, status
	 *
	 * @return array recipient, action, status and the decision, which is one of hard, soft,
	 *               already, not_found or skipped
	 */
	public function handle_recipient( array $the ) {

		$row = [
			'recipient' => trim( (string) ( $the['recipient'] ?? '' ), " \t\n\r\0\x0B<>" ),
			'action'    => (string) ( $the['action'] ?? '' ),
			'status'    => (string) ( $the['status'] ?? '' ),
			'decision'  => 'skipped',
		];

		$class = self::classify( $the );

		if ( $class === 'skip' ) {
			return $row;
		}

		// get_contactdata() takes an empty value to mean whoever is being processed or tracked right now
		if ( ! is_email( $row['recipient'] ) ) {
			return $row;
		}

		$contact = get_contactdata( $row['recipient'] );

		if ( ! is_a_contact( $contact ) ) {
			$row['decision'] = 'not_found';

			return $row;
		}

		if ( $class === 'soft' ) {
			$row['decision'] = 'soft';

			return $row;
		}

		if ( $contact->get_optin_status() === Preferences::HARD_BOUNCE ) {
			$row['decision'] = 'already';

			return $row;
		}

		$note = '';

		if ( $row['status'] !== '' ) {
			$note = (string) @$this->get_bounce_handler()->fetch_status_messages( $row['status'] );

			// a code that it doesn't have a description for
			if ( trim( str_replace( '-', '', wp_strip_all_tags( $note ) ) ) === '' ) {
				$note = '';
			}
		}

		$contact->add_note( $note ?: __( 'The email could not be delivered, the address was reported as permanently failing.', 'groundhogg' ) );
		$contact->change_marketing_preference( Preferences::HARD_BOUNCE );

		$row['decision'] = 'hard';

		return $row;
	}

	/**
	 * Look at the emails in the bounce inbox, and handle the bounces in them
	 *
	 * @param bool $manual from the button on the settings page, which looks back a set number of days, not from where the cron job got to
	 *
	 * @return array|\WP_Error time, manual, counts (messages, hard, soft, already, not_found, skipped) and details, a row for every recipient
	 */
	protected function run( bool $manual ) {

		$this->setup();

		// Using an API based email client means that there is no need to check bounces remotely
		if ( ! \Groundhogg_Email_Services::service_in_use( 'wp_mail' ) && ! \Groundhogg_Email_Services::service_in_use( 'smtp' ) ) {
			return new \WP_Error( 'not_needed', __( 'Bounces are reported by the email service in use, there is no inbox to check.', 'groundhogg' ) );
		}

		if ( ! function_exists( 'imap_open' ) ) {
			return new \WP_Error( 'no_imap', __( 'PHP IMAP library is not installed and is required to use this function.', 'groundhogg' ) );
		}

		if ( empty( $this->password ) || empty( $this->inbox ) ) {
			return new \WP_Error( 'not_configured', __( 'There is no bounce inbox set up.', 'groundhogg' ) );
		}

		/* try to connect */
		try {
			$inbox = @\imap_open( $this->get_hostname(), $this->inbox, $this->password, OP_READONLY );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'imap_failed', $e->getMessage() );
		}

		if ( ! $inbox ) {
			return new \WP_Error( 'imap_failed', sprintf( 'Failed to connect. Error: %s', \imap_last_error() ) );
		}

		$started = time();
		$cursor  = absint( get_option( self::CURSOR_OPTION ) );

		/**
		 * How many days back Handle Bounces Now looks
		 *
		 * @param int $days
		 */
		$days = absint( apply_filters( 'groundhogg/bounce_checker/manual_days', 7 ) );

		/**
		 * How many emails one run handles, the cron job carries on with the rest next time
		 *
		 * @param int  $limit
		 * @param bool $manual
		 */
		$limit = absint( apply_filters( 'groundhogg/bounce_checker/limit', $manual ? 500 : 200, $manual ) );

		// A manual run looks back a set time. The cron job carries on from where it got to, or from 2 days ago the first time
		$since = $manual ? $started - $days * DAY_IN_SECONDS : ( $cursor ? $cursor - self::CRON_OVERLAP : $started - 2 * DAY_IN_SECONDS );

		$counts  = [ 'messages' => 0, 'hard' => 0, 'soft' => 0, 'already' => 0, 'not_found' => 0, 'skipped' => 0 ];
		$details = [];

		// Whether the email was read doesn't matter. SINCE is the day and not the time, so look a day further back than
		// needed, and go by the date of each email
		$found  = @\imap_search( $inbox, sprintf( 'SINCE "%s"', gmdate( 'j F Y', $since - DAY_IN_SECONDS ) ) );
		$emails = [];

		if ( $found ) {
			foreach ( (array) @\imap_fetch_overview( $inbox, implode( ',', $found ), 0 ) as $overview ) {
				if ( isset( $overview->msgno ) && (int) ( $overview->udate ?? 0 ) >= $since ) {
					$emails[ (int) $overview->msgno ] = (int) $overview->udate;
				}
			}
		}

		asort( $emails ); // oldest first, so what's left over for next time is the newest

		$next_cursor = $started;

		if ( $limit && count( $emails ) > $limit ) {
			$emails      = array_slice( $emails, 0, $limit, true );
			$next_cursor = max( $emails );
		}

		$this->get_bounce_handler();

		foreach ( array_keys( $emails ) as $email_number ) {

			$counts['messages'] ++;

			/* get information specific to this email */
			$message    = @\imap_fetchbody( $inbox, $email_number, '' );
			$multiArray = $this->bounce_handler->get_the_facts( $message );

			foreach ( $multiArray as $the ) {

				$row = $this->handle_recipient( $the );

				$counts[ $row['decision'] ] ++;
				$details[] = $row;
			}
		}

		@\imap_close( $inbox );

		// the cron job carries on from here next time, a manual run doesn't change where it is
		if ( ! $manual ) {
			update_option( self::CURSOR_OPTION, $next_cursor, false );
		}

		$result = [
			'time'    => $started,
			'manual'  => $manual,
			'counts'  => $counts,
			'details' => array_slice( $details, - 50 ),
		];

		update_option( self::LAST_RUN_OPTION, $result, false );

		return $result;
	}

}
