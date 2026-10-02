<?php

use Groundhogg\Api\V4\Emails_Api;
use Groundhogg\DB\Email_Log;
use Groundhogg\Email;
use Groundhogg\Email_Logger;
use function Groundhogg\generate_permissions_key;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\is_sending;
use function Groundhogg\redactor;

/**
 * Keys that sign someone in must not end up readable in the email log.
 *
 * Two layers: the key is registered with the redactor so the stored subject/body/altbody have it
 * blacked out, and the log is flagged sensitive so only super admins can read it back.
 *
 * @see \Groundhogg\generate_permissions_key() registers the key, including when it is served from the cache
 * @see Email_Logger::phpmailer_init_callback()
 * @see Email_Log the sensitive restriction on query(), advanced_query(), get() and get_by()
 */
class Email_Log_Sensitive_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		is_sending( false );
		update_option( 'gh_log_emails', [ 'on' ] );
		redactor( true );
		wp_cache_flush();
	}

	public function tearDown(): void {
		delete_option( 'gh_log_emails' );
		wp_set_current_user( 0 );
		Email_Logger::clear();
		parent::tearDown();
	}

	/**
	 * @return int
	 */
	protected function user( $role ) {
		return self::factory()->user->create( [ 'role' => $role ] );
	}

	/**
	 * Mail something through the logger
	 *
	 * @param bool $sensitive whether the mail is marked sensitive, as it is when a key is minted
	 *
	 * @return int the log ID
	 */
	protected function log_mail( $subject, $body = 'body', $sensitive = false ) {

		Email_Logger::clear();

		if ( $sensitive ) {
			Email_Logger::email_is_sensitive();
		}

		wp_mail( 'someone@example.test', $subject, $body );

		$id = Email_Logger::get_last_log_id();
		$this->assertNotEmpty( $id, 'the email was not logged' );

		return $id;
	}

	/**
	 * What is stored, whoever is logged in
	 */
	protected function stored_log( $id ) {
		return Email_Log::as_system( fn() => get_db( 'email_log' )->get( $id ) );
	}

	/* ---------------------------------------------------------------------
	 * Redaction
	 * ------------------------------------------------------------------- */

	/**
	 * Capture what PHPMailer is actually given, after the logger (priority 99) has run
	 *
	 * @return \stdClass->body the sent body, null if nothing was sent
	 */
	protected function capture_sent_body() {

		$sent = new stdClass();
		$sent->body = null;

		add_action( 'phpmailer_init', function ( $phpmailer ) use ( $sent ) {
			$sent->body = $phpmailer->Body;
		}, 999 );

		return $sent;
	}

	public function test_a_key_minted_by_sending_an_email_is_in_the_email_but_redacted_in_the_log() {

		$user_id = $this->user( 'subscriber' );
		$contact = get_contactdata( $user_id, true );

		wp_set_current_user( $this->user( 'administrator' ) );

		$email = new Email( [
			'subject' => 'Your sign in link',
			'content' => '<p>Sign in: {auto_login_link}</p>',
			'status'  => 'ready',
		] );

		$this->assertTrue( $email->exists() );

		$sent = $this->capture_sent_body();

		$email->send( $contact );

		$this->assertNotNull( $sent->body, 'the email was not sent' );
		$this->assertMatchesRegularExpression( '/pk=([A-Za-z0-9]{20})/', $sent->body, 'sending is where the key is minted' );
		preg_match( '/pk=([A-Za-z0-9]{20})/', $sent->body, $m );
		$key = $m[1];

		$log = $this->stored_log( Email_Logger::get_last_log_id() );

		$this->assertStringNotContainsString( $key, $log->content );
		$this->assertStringNotContainsString( $key, $log->altbody );
		$this->assertStringNotContainsString( $key, $log->subject );
		$this->assertStringContainsString( '█', $log->content );
		$this->assertSame( '1', (string) $log->is_sensitive );
	}

	/**
	 * Composed emails turn is_sending() on without an Email being built, and the recipients are whoever
	 * the sender says, so a key put in one could be read by the sender through bcc.
	 */
	public function test_a_composed_email_never_gets_a_key_even_with_the_sender_in_bcc() {

		$contact = get_contactdata( $this->user( 'administrator' ), true );
		$rep_id  = $this->user( 'sales_rep' );

		// the contact has to be one the rep can view
		$contact->update( [ 'owner_id' => $rep_id ] );

		wp_set_current_user( $rep_id );

		// an address of the rep's with no contact record, so there is nothing stopping them from using it
		$rep_address = uniqid( 'rep-' ) . '@example.test';
		$sent        = $this->capture_sent_body();

		$request = new WP_REST_Request( 'POST', '/gh/v4/emails/send' );
		$request->set_param( 'to', [ $contact->get_email() ] );
		$request->set_param( 'bcc', [ $rep_address ] );
		$request->set_param( 'subject', 'Your sign in link' );
		$request->set_param( 'content', '<p>Sign in: {auto_login_link}</p>' );

		$response = ( new Emails_Api() )->send_email( $request );

		$this->assertNotWPError( $response );
		$this->assertNotNull( $sent->body, 'the email was not sent' );
		$this->assertStringContainsString( '/auto-login', $sent->body );
		$this->assertStringNotContainsString( 'pk=', $sent->body );
		$this->assertCount( 0, get_db( 'permissions_keys' )->query( [
			'contact_id' => $contact->get_id(),
			'usage_type' => 'auto_login',
		] ) );
	}

	public function test_a_cached_key_is_still_redacted_after_the_redactor_resets() {

		$contact = get_contactdata( $this->user( 'subscriber' ), true );

		$key1 = generate_permissions_key( $contact, 'auto_login', DAY_IN_SECONDS, true );

		// what 'groundhogg/event/run/before' does between two events in one queue run
		redactor( true );

		$key2 = generate_permissions_key( $contact, 'auto_login', DAY_IN_SECONDS, true );
		$this->assertSame( $key1, $key2, 'precondition: the second call is served from the cache' );

		$log = $this->stored_log( $this->log_mail( 'Link', "<p>https://example.test/gh/auto-login/?pk=$key2</p>" ) );

		$this->assertStringNotContainsString( $key2, $log->content );
	}

	public function test_a_key_is_redacted_in_the_subject_and_on_every_line() {

		$contact = get_contactdata( $this->user( 'subscriber' ), true );
		$key     = generate_permissions_key( $contact, 'auto_login', DAY_IN_SECONDS, true );

		$log = $this->stored_log( $this->log_mail( "Link $key", "line one\nhttps://example.test/?pk=$key\nline three" ) );

		$this->assertStringNotContainsString( $key, $log->subject );
		$this->assertStringNotContainsString( $key, $log->content );
		$this->assertStringContainsString( 'line one', $log->content );
		$this->assertStringContainsString( 'line three', $log->content );
	}

	/* ---------------------------------------------------------------------
	 * Who can read a sensitive log
	 * ------------------------------------------------------------------- */

	public function test_a_rep_cannot_read_a_sensitive_log_by_any_route() {

		$secret = uniqid( 'secret-' );
		$id     = $this->log_mail( $secret, 'body', true );
		$plain  = $this->log_mail( uniqid( 'plain-' ) );

		wp_set_current_user( $this->user( 'sales_rep' ) );

		$db = get_db( 'email_log' );

		$this->assertEmpty( $db->query( [ 'ID' => $id ] ), 'query by ID' );
		$this->assertEmpty( $db->query( [ 'subject' => $secret ] ), 'query by column' );
		$this->assertNotContains( $id, wp_list_pluck( $db->query( [] ), 'ID' ), 'query with no filter' );
		$this->assertEmpty( $db->advanced_query( [ 'where' => [ [ 'col' => 'subject', 'val' => $secret, 'compare' => '=' ] ] ] ), 'advanced_query' );
		$this->assertNull( $db->get( $id ), 'get' );
		$this->assertNull( $db->get_by( 'subject', $secret ), 'get_by' );

		// ordinary logs are unaffected
		$this->assertNotNull( $db->get( $plain ) );
	}

	public function test_a_where_of_the_callers_own_cannot_get_around_the_restriction() {

		$secret = uniqid( 'secret-' );
		$id     = $this->log_mail( $secret, 'body', true );

		wp_set_current_user( $this->user( 'sales_rep' ) );

		$db = get_db( 'email_log' );

		// OR with something that is always true
		$this->assertEmpty( $db->advanced_query( [
			'where' => [
				'relationship' => 'OR',
				[ 'col' => 'subject', 'val' => $secret, 'compare' => '=' ],
				[ 'col' => 'ID', 'val' => 0, 'compare' => '>' ],
			],
		] ), 'OR relationship' );

		// claiming to be parsed already
		$this->assertEmpty( $db->advanced_query( [
			'_was_parsed' => true,
			'operation'   => 'SELECT',
			'select'      => array_keys( $db->get_columns() ),
			'limit'       => false,
			'offset'      => false,
			'orderby'     => 'ID',
			'order'       => 'desc',
			'search'      => false,
			'func'        => false,
			'where'       => [ 'relationship' => 'AND', [ 'col' => 'ID', 'val' => $id, 'compare' => '=' ] ],
		] ), '_was_parsed' );

		// explicitly asking for sensitive ones
		$this->assertEmpty( $db->query( [ 'ID' => $id, 'is_sensitive' => 1 ] ), 'asking for is_sensitive = 1' );
	}

	public function test_results_cached_for_a_super_admin_are_not_served_to_a_rep() {

		$secret = uniqid( 'secret-' );
		$id     = $this->log_mail( $secret, 'body', true );

		$vars = [ 'where' => [ [ 'col' => 'subject', 'val' => $secret, 'compare' => '=' ] ] ];

		wp_set_current_user( $this->user( 'administrator' ) );
		$this->assertCount( 1, get_db( 'email_log' )->advanced_query( $vars ), 'precondition: an admin sees it' );

		wp_set_current_user( $this->user( 'sales_rep' ) );
		$this->assertEmpty( get_db( 'email_log' )->advanced_query( $vars ) );
	}

	public function test_super_admins_and_requests_without_a_user_still_read_sensitive_logs() {

		$secret = uniqid( 'secret-' );
		$id     = $this->log_mail( $secret, 'body', true );

		// cron / CLI
		wp_set_current_user( 0 );
		$this->assertNotNull( get_db( 'email_log' )->get( $id ), 'no user' );

		wp_set_current_user( $this->user( 'administrator' ) );
		$db = get_db( 'email_log' );
		$this->assertNotNull( $db->get( $id ), 'get' );
		$this->assertCount( 1, $db->query( [ 'subject' => $secret ] ), 'query' );
		$this->assertCount( 1, $db->advanced_query( [ 'where' => [ [ 'col' => 'subject', 'val' => $secret, 'compare' => '=' ] ] ] ), 'advanced_query' );
	}

	public function test_a_rep_sending_a_sensitive_email_can_still_have_it_logged_and_updated() {

		wp_set_current_user( $this->user( 'sales_rep' ) );

		$id = $this->log_mail( uniqid( 'rep-sent-' ), 'body', true );

		// the logger has to have picked its own log back up for this to land
		Email_Logger::set_msg_id( 'msg-123' );

		$this->assertSame( 'msg-123', $this->stored_log( $id )->msg_id );
		$this->assertSame( '1', (string) $this->stored_log( $id )->is_sensitive );
	}

	public function test_as_system_is_scoped_to_the_callback() {

		$id = $this->log_mail( uniqid( 'secret-' ), 'body', true );

		wp_set_current_user( $this->user( 'sales_rep' ) );

		$this->assertNotNull( Email_Log::as_system( fn() => get_db( 'email_log' )->get( $id ) ) );
		$this->assertNull( get_db( 'email_log' )->get( $id ) );

		try {
			Email_Log::as_system( function () {
				throw new RuntimeException( 'boom' );
			} );
		} catch ( RuntimeException $e ) {
			// swallowed, we only care that the restriction comes back
		}

		$this->assertNull( get_db( 'email_log' )->get( $id ) );
	}
}
