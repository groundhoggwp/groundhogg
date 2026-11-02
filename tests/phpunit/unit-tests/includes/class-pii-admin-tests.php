<?php

use Groundhogg\Admin\Contacts\Contacts_Page;
use Groundhogg\Admin\Contacts\Tables\Contact_Table_Columns;
use Groundhogg\Admin\Contacts\Tables\Contacts_Table;
use Groundhogg\Admin\Events\Email_Log_Table;
use Groundhogg\Admin\Events\Events_Table;
use Groundhogg\Api\V4\Contacts_Api;
use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Pii;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\track_page_visit;

/**
 * What a user without the view_contact_pii capability sees in the admin screens that are rendered on the server
 *
 * @see \Groundhogg\Pii_Admin
 * @see Pii_Restriction_Rest_Tests for the REST API
 */
class Pii_Admin_Tests extends GH_UnitTestCase {

	const EMAIL = 'jane.doe@example.com';

	protected $contact_id;

	public function setUp(): void {
		parent::setUp();

		$this->contact_id = $this->factory()->contacts->create( [
			'email'      => self::EMAIL,
			'first_name' => 'Jane',
			'last_name'  => 'Doe',
		] );

		get_contactdata( $this->contact_id )->update_meta( [
			'primary_phone'    => '555-0100',
			'mobile_phone'     => '555-0199',
			'street_address_1' => '1 Main Street',
			'city'             => 'Springfield',
			'postal_zip'       => '90210',
			'country'          => 'US',
			'ip_address'       => '203.0.113.9',
			'birthday'         => '1990-04-12',
			'company_name'     => 'Acme',
		] );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );

		// the table needs an admin screen, which makes everything after it think it's in the admin
		set_current_screen( 'front' );

		$_GET     = [];
		$_REQUEST = [];
		parent::tearDown();
	}

	protected function admin( bool $pii ): int {

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		// WP_Roles::add_cap() doesn't refresh cached roles, so don't depend on the installer having run on this DB
		get_userdata( $user_id )->add_cap( Pii::CAP, $pii );

		wp_set_current_user( $user_id );

		return $user_id;
	}

	protected function assertNoPii( string $html ) {

		foreach ( [ self::EMAIL, 'Jane', 'Doe', '555-0100', '555-0199', 'Main Street', 'Springfield', '90210', '203.0.113.9', '1990', 'jane.doe' ] as $pii ) {
			$this->assertStringNotContainsString( $pii, $html );
		}
	}

	/**
	 * Every column of the contacts table, for a contact, as the admin would render the row
	 */
	protected function row(): string {

		$this->boot_table();

		$table = new Contacts_Table();

		ob_start();
		$table->single_row( get_contactdata( $this->contact_id ) );

		return ob_get_clean();
	}

	/**
	 * What the contacts page sets up before it builds the table
	 */
	protected function boot_table() {

		// the hooks are put back after every test, so this can't be remembered
		( new Contact_Table_Columns() )->register_core_columns();

		// every column on, not just the user's defaults
		add_filter( 'hidden_columns', '__return_empty_array' );

		set_current_screen( 'dashboard' );
		get_current_screen()->add_option( 'per_page', [ 'default' => 20, 'option' => 'gh_contacts_per_page' ] );
	}

	protected function table_items( array $get = [] ): array {

		$this->boot_table();

		$_GET = $_REQUEST = $get;

		$table = new Contacts_Table();
		$table->prepare_items();

		ob_start();
		$table->display_rows_or_placeholder();

		return [ $table->items, ob_get_clean() ];
	}

	public function test_an_unrestricted_user_sees_the_row() {
		$this->admin( true );

		$row = $this->row();

		$this->assertStringContainsString( self::EMAIL, $row );
		$this->assertStringContainsString( 'Jane', $row );
		$this->assertStringContainsString( '555-0100', $row );
	}

	public function test_a_restricted_users_row_has_no_pii_in_it() {
		$this->admin( false );

		$row = $this->row();

		$this->assertNoPii( $row );
		$this->assertStringContainsString( Pii::contact_label( $this->contact_id ), $row );
		$this->assertStringContainsString( Pii::REDACTED, $row );
		// the id still works as a handle
		$this->assertStringContainsString( 'contact-' . $this->contact_id, $row );
		$this->assertStringContainsString( 'contact=' . $this->contact_id, $row );
	}

	public function test_a_restricted_users_row_keeps_what_isnt_personal() {
		$this->admin( false );

		$row = $this->row();

		// the country is where, not who
		$this->assertStringContainsString( 'United States', $row );
	}

	public function test_a_restricted_users_picture_is_not_a_hash_of_the_email() {
		$this->admin( false );

		$this->assertSame( Pii::blank_picture(), get_contactdata( $this->contact_id )->get_profile_picture() );
	}

	public function test_a_restricted_users_table_refuses_a_search_by_name_or_email() {
		$this->admin( false );

		foreach ( [ 'jane', 'jane.doe@example.com', 'Jane Doe' ] as $search ) {
			[ $items, $html ] = $this->table_items( [ 's' => $search ] );

			$this->assertSame( [], $items, $search );
			$this->assertStringContainsString( 'not allowed', $html );
			$this->assertNoPii( $html );
		}
	}

	public function test_a_restricted_users_table_refuses_sorting_by_pii() {
		$this->admin( false );

		foreach ( [ 'email', 'first_name', 'last_name', 'cm.birthday' ] as $orderby ) {
			[ $items ] = $this->table_items( [ 'orderby' => $orderby ] );

			$this->assertSame( [], $items, $orderby );
		}
	}

	public function test_a_restricted_users_table_refuses_a_filter_on_pii() {
		$this->admin( false );

		$filters = base64_encode( wp_json_encode( [ [ [ 'type' => 'email', 'compare' => 'contains', 'value' => 'jane' ] ] ] ) );

		[ $items ] = $this->table_items( [ 'filters' => $filters ] );

		$this->assertSame( [], $items );
	}

	public function test_a_restricted_users_table_lists_contacts_without_a_search() {
		$this->admin( false );

		[ $items, $html ] = $this->table_items();

		$this->assertNotEmpty( $items );
		$this->assertNoPii( $html );
	}

	public function test_an_unrestricted_users_table_still_searches() {
		$this->admin( true );

		[ $items ] = $this->table_items( [ 's' => 'jane' ] );

		$this->assertCount( 1, $items );
	}

	public function test_the_table_row_route_serves_a_redacted_row() {
		$this->admin( false );

		$request = new WP_REST_Request( 'GET', '/gh/v4/contacts/table/row' );
		$request->set_query_params( [ 'contact' => $this->contact_id ] );

		$this->boot_table();

		// the callback itself, other tests that run earlier can leave the REST server without its routes
		$response = ( new Contacts_Api() )->admin_table_row( $request );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertNoPii( $response->get_data()['row'] );
	}

	public function test_the_sort_links_of_pii_columns_are_not_offered_to_a_restricted_user() {

		$offered = function () {
			$this->boot_table();

			$table  = new Contacts_Table();
			$method = new ReflectionMethod( $table, 'get_sortable_columns' );
			$method->setAccessible( true );

			return array_keys( $method->invoke( $table ) );
		};

		$this->admin( true );
		$this->assertContains( 'email', $offered() );
		$this->assertContains( 'first_name', $offered() );

		$this->admin( false );
		$restricted = $offered();
		$this->assertNotContains( 'email', $restricted );
		$this->assertNotContains( 'first_name', $restricted );
		$this->assertNotContains( 'birthday', $restricted );
		$this->assertContains( 'date_created', $restricted );
	}

	/**
	 * A part of the contact record page, as it's rendered with the contact in scope
	 *
	 * @param string $file relative to the admin/contacts directory
	 *
	 * @return string
	 */
	protected function render( string $file ): string {

		$contact = get_contactdata( $this->contact_id );

		ob_start();
		include GROUNDHOGG_PATH . 'admin/contacts/' . $file;

		return ob_get_clean();
	}

	/**
	 * The "Contact Information", "Location" and "Source" sections of the record
	 */
	protected function render_editor(): string {

		$contact = get_contactdata( $this->contact_id );
		$fn      = '\Groundhogg\Admin\Contacts\contact_record_general_info';

		// the editor declares the function when it is included, which can only be done once
		if ( ! function_exists( $fn ) ) {
			$_GET['contact'] = $this->contact_id;

			ob_start();
			include GROUNDHOGG_PATH . 'admin/contacts/parts/contact-editor.php';
			ob_end_clean();
		}

		ob_start();
		$fn( $contact );

		return ob_get_clean();
	}

	public function test_an_unrestricted_user_sees_the_details_card() {
		$this->admin( true );

		$html = $this->render( 'parts/details-card.php' );

		$this->assertStringContainsString( self::EMAIL, $html );
		$this->assertStringContainsString( 'Jane Doe', $html );
		$this->assertStringContainsString( '555-0100', $html );
		$this->assertStringContainsString( 'Springfield', $html );
	}

	public function test_a_restricted_users_details_card_has_no_pii_in_it() {
		$this->admin( false );

		$html = $this->render( 'parts/details-card.php' );

		$this->assertNoPii( $html );
		$this->assertStringContainsString( Pii::contact_label( $this->contact_id ), $html );
		$this->assertStringContainsString( Pii::REDACTED, $html );
		// where, not who
		$this->assertStringContainsString( 'United States', $html );
		$this->assertStringNotContainsString( 'mailto:', $html );
		$this->assertStringNotContainsString( 'google.com/maps', $html );
		$this->assertStringNotContainsString( 'example.com', $html );
	}

	public function test_a_restricted_users_editor_has_no_pii_in_it_and_cant_post_it_back() {
		$this->admin( false );

		$html = $this->render_editor();

		$this->assertNoPii( $html );
		$this->assertStringContainsString( Pii::REDACTED, $html );

		// nothing that is redacted is submitted with the form
		foreach ( [ 'first_name', 'last_name', 'email', 'primary_phone', 'mobile_phone', 'street_address_1', 'city', 'postal_zip', 'ip_address' ] as $name ) {
			$this->assertMatchesRegularExpression( '/<input[^>]*name=[\'"]' . $name . '[\'"][^>]*\sdisabled/', $html, $name );
		}

		$this->assertStringNotContainsString( 'birthday[year]', $html );
	}

	public function test_an_unrestricted_users_editor_is_editable() {
		$this->admin( true );

		$html = $this->render_editor();

		$this->assertStringContainsString( self::EMAIL, $html );
		$this->assertStringContainsString( 'birthday[year]', $html );
		$this->assertDoesNotMatchRegularExpression( '/<input[^>]*name=[\'"]email[\'"][^>]*\sdisabled/', $html );
	}

	public function test_a_restricted_users_page_titles_have_no_name_in_them() {
		$this->admin( false );

		$this->assertSame( Pii::contact_label( $this->contact_id ), Pii::contact_name( get_contactdata( $this->contact_id ) ) );

		$_GET['page']    = 'gh_contacts';
		$_GET['action']  = 'edit';
		$_GET['contact'] = $this->contact_id;
		$_REQUEST        = $_GET;

		// the constructor sets up the page and redirects, the titles only need the request
		$page = ( new ReflectionClass( Contacts_Page::class ) )->newInstanceWithoutConstructor();

		$this->assertNoPii( $page->get_title() );
		$this->assertStringContainsString( Pii::contact_label( $this->contact_id ), $page->get_title() );
		$this->assertNoPii( $page->admin_title( 'Contacts', 'Contacts' ) );
	}

	public function test_an_unrestricted_users_title_is_the_name() {
		$this->admin( true );

		$this->assertSame( 'Jane Doe', Pii::contact_name( get_contactdata( $this->contact_id ) ) );
	}

	public function test_a_restricted_users_page_visits_have_no_query_string() {
		$this->admin( false );

		track_page_visit( home_url( '/pricing/?lead=jane.doe@example.com#Jane' ), get_contactdata( $this->contact_id ) );

		$html = $this->render( 'cards/page-visits.php' );

		$this->assertStringContainsString( '/pricing/', $html );
		$this->assertNoPii( $html );
		// nothing after the path, not even where it's the card's own doing to leave it out
		$this->assertStringNotContainsString( 'lead=', $html );
		$this->assertStringNotContainsString( '?', $html );

	}

	public function test_a_restricted_users_user_card_has_no_pii_in_it() {

		$user_id = self::factory()->user->create( [
			'role'         => 'administrator',
			'user_login'   => 'janedoe',
			'user_email'   => 'jane.user@example.com',
			'display_name' => 'Jane Doe',
		] );

		// the user has a contact of their own, which gets the user, so this one takes it over
		get_db( 'contacts' )->update( [ 'user_id' => $user_id ], [ 'user_id' => 0 ] );
		get_contactdata( $this->contact_id )->update( [ 'user_id' => $user_id ] );

		$this->admin( true );
		$this->assertStringContainsString( 'janedoe', $this->render( 'cards/user.php' ) );

		$this->admin( false );
		$html = $this->render( 'cards/user.php' );

		$this->assertStringNotContainsString( 'janedoe', $html );
		$this->assertStringNotContainsString( 'jane.user', $html );
		$this->assertStringNotContainsString( 'Jane Doe', $html );
		$this->assertStringContainsString( Pii::REDACTED, $html );
	}

	public function test_a_restricted_user_cant_start_a_user_account_from_a_contacts_details() {
		$this->admin( false );

		$html = $this->render( 'cards/user.php' );

		$this->assertStringNotContainsString( 'create-user-form', $html );
		$this->assertNoPii( $html );

		$this->admin( true );

		$this->assertStringContainsString( self::EMAIL, $this->render( 'cards/user.php' ) );
	}

	public function test_a_restricted_user_isnt_offered_the_name_and_email_search() {
		$this->admin( false );

		$this->assertStringNotContainsString( 'contact-quick-search', $this->render( 'parts/quick-search.php' ) );

		$this->admin( true );

		$this->assertStringContainsString( 'contact-quick-search', $this->render( 'parts/quick-search.php' ) );
	}

	protected function page(): Contacts_Page {
		// the constructor sets up the page and redirects
		return ( new ReflectionClass( Contacts_Page::class ) )->newInstanceWithoutConstructor();
	}

	/**
	 * What the record form posts for a user who can't view PII: nothing for the fields that are shown disabled
	 */
	public function test_saving_the_record_as_a_restricted_user_leaves_the_pii_alone() {
		$this->admin( false );

		$_POST = $_REQUEST = [
			'contact'      => $this->contact_id,
			'owner_id'     => get_current_user_id(),
			'optin_status' => 2,
			'lead_source'  => 'A friend',
		];

		$this->assertTrue( $this->page()->process_edit() );

		$contact = new Contact( $this->contact_id );

		$this->assertSame( self::EMAIL, $contact->get_email() );
		$this->assertSame( 'Jane', $contact->get_first_name() );
		$this->assertSame( 'Doe', $contact->get_last_name() );
		$this->assertSame( '555-0100', $contact->get_meta( 'primary_phone' ) );
		$this->assertSame( '1 Main Street', $contact->get_meta( 'street_address_1' ) );
		$this->assertSame( '203.0.113.9', $contact->get_meta( 'ip_address' ) );
		$this->assertSame( '1990-04-12', $contact->get_meta( 'birthday' ) );
		// what isn't PII is saved
		$this->assertSame( 'A friend', $contact->get_meta( 'lead_source' ) );
		$this->assertSame( get_current_user_id(), $contact->get_owner_id() );
	}

	public function test_saving_the_record_as_a_restricted_user_ignores_pii_that_is_posted() {
		$this->admin( false );

		$_POST = $_REQUEST = [
			'contact'       => $this->contact_id,
			'email'         => 'someone.else@example.com',
			'first_name'    => 'Other',
			'primary_phone' => '555-0000',
			'birthday'      => [ 'year' => 2000, 'month' => 1, 'day' => 1 ],
		];

		$this->page()->process_edit();

		$contact = new Contact( $this->contact_id );

		$this->assertSame( self::EMAIL, $contact->get_email() );
		$this->assertSame( 'Jane', $contact->get_first_name() );
		$this->assertSame( '555-0100', $contact->get_meta( 'primary_phone' ) );
		$this->assertSame( '1990-04-12', $contact->get_meta( 'birthday' ) );
	}

	public function test_saving_the_record_as_an_unrestricted_user_still_edits_the_pii() {
		$this->admin( true );

		$_POST = $_REQUEST = [
			'contact'       => $this->contact_id,
			'email'         => 'someone.else@example.com',
			'first_name'    => 'Other',
			'last_name'     => 'Person',
			'primary_phone' => '555-0000',
		];

		$this->page()->process_edit();

		$contact = new Contact( $this->contact_id );

		$this->assertSame( 'someone.else@example.com', $contact->get_email() );
		$this->assertSame( 'Other', $contact->get_first_name() );
		$this->assertSame( '555-0000', $contact->get_meta( 'primary_phone' ) );
	}

	public function test_the_redacted_marker_is_never_stored_as_a_contacts_pii() {

		$contact = new Contact( $this->contact_id );

		$this->assertTrue( $contact->update( [
			'email'      => Pii::REDACTED,
			'first_name' => Pii::REDACTED,
			'last_name'  => ' ' . Pii::REDACTED . ' ',
		] ) );
		$contact->update_meta( 'primary_phone', Pii::REDACTED );
		$contact->update_meta( [ 'street_address_1' => Pii::REDACTED, 'birthday' => Pii::REDACTED ] );
		$contact->add_meta( 'city', Pii::REDACTED );

		$contact = new Contact( $this->contact_id );

		$this->assertSame( self::EMAIL, $contact->get_email() );
		$this->assertSame( 'Jane', $contact->get_first_name() );
		$this->assertSame( 'Doe', $contact->get_last_name() );
		$this->assertSame( '555-0100', $contact->get_meta( 'primary_phone' ) );
		$this->assertSame( '1 Main Street', $contact->get_meta( 'street_address_1' ) );
		$this->assertSame( '1990-04-12', $contact->get_meta( 'birthday' ) );
		$this->assertSame( 'Springfield', $contact->get_meta( 'city' ) );

		// what isn't PII is whatever it's set to
		$contact->update_meta( 'company_name', 'Acme 2' );
		$this->assertSame( 'Acme 2', ( new Contact( $this->contact_id ) )->get_meta( 'company_name' ) );
	}

	public function test_a_restricted_user_adding_a_contact_is_not_told_an_address_is_taken() {
		$this->admin( false );

		$_POST = $_REQUEST = [ 'email' => self::EMAIL ];

		$result = $this->page()->process_add();

		$this->assertWPError( $result );
		$this->assertStringNotContainsString( 'already', $result->get_error_message() );
		$this->assertStringNotContainsString( self::EMAIL, $result->get_error_message() );

		$this->admin( true );

		$this->assertStringContainsString( 'already', $this->page()->process_add()->get_error_message() );
	}

	protected function email_log_id( array $args = [] ): int {
		return get_db( 'email_log' )->add( array_merge( [
			'recipients'    => maybe_serialize( [ self::EMAIL ] ),
			'from_address'  => 'sender@example.org',
			'subject'       => 'Hello Jane Doe',
			'content'       => '<p>Hi Jane</p>',
			'headers'       => maybe_serialize( [ [ 'Content-Type', 'text/html' ] ] ),
			'message_type'  => 'marketing',
			'email_service' => 'wp_mail',
			'status'        => 'sent',
		], $args ) );
	}

	/**
	 * The rows of an admin table, and what it says when there are none
	 *
	 * @param WP_List_Table $table
	 * @param array         $get
	 *
	 * @return array
	 */
	protected function list_table( $table, array $get = [] ): array {

		set_current_screen( 'dashboard' );
		get_current_screen()->add_option( 'per_page', [ 'default' => 20, 'option' => 'gh_events_per_page' ] );

		$_GET = $_REQUEST = $get;

		$table->prepare_items();

		ob_start();
		$table->display_rows_or_placeholder();

		return [ $table->items, ob_get_clean() ];
	}

	public function test_a_restricted_users_email_log_has_no_pii_in_it() {

		// the lookup of a contact by address is remembered for the whole run, so the recipient has an address of its own
		$recipient_email = 'log.recipient.' . wp_generate_password( 6, false ) . '@example.org';
		$recipient_id    = $this->factory()->contacts->create( [ 'email' => $recipient_email ] );

		$this->email_log_id( [
			'recipients'    => maybe_serialize( [ $recipient_email ] ),
			'status'        => 'failed',
			'error_message' => 'Could not deliver to ' . $recipient_email,
		] );

		$this->admin( true );
		[ , $html ] = $this->list_table( new Email_Log_Table(), [ 'view' => 'failed' ] );
		$this->assertStringContainsString( $recipient_email, $html );
		$this->assertStringContainsString( 'Hello Jane Doe', $html );

		$this->admin( false );
		[ $items, $html ] = $this->list_table( new Email_Log_Table(), [ 'view' => 'failed' ] );

		$this->assertNotEmpty( $items );
		$this->assertStringNotContainsString( $recipient_email, $html );
		$this->assertNoPii( $html );
		$this->assertStringNotContainsString( 'sender@example.org', $html );
		$this->assertStringContainsString( Pii::contact_label( $recipient_id ), $html );
		$this->assertStringContainsString( Pii::REDACTED, $html );
		// the same goes for the copy of the rows that is handed to the page's scripts
		$this->assertStringNotContainsString( $recipient_email, wp_json_encode( $items ) );
		$this->assertNoPii( wp_json_encode( $items ) );
	}

	public function test_a_restricted_users_email_log_refuses_a_search_or_sort_by_pii() {
		$this->email_log_id();
		$this->admin( false );

		foreach ( [ [ 's' => 'Jane' ], [ 's' => self::EMAIL ], [ 'orderby' => 'subject' ], [ 'orderby' => 'recipients' ], [ 'search_columns' => 'subject' ] ] as $get ) {
			[ $items, $html ] = $this->list_table( new Email_Log_Table(), $get );

			$this->assertSame( [], $items, wp_json_encode( $get ) );
			$this->assertStringContainsString( 'not allowed', $html );
		}

		// what isn't personal is still sortable
		[ $items ] = $this->list_table( new Email_Log_Table(), [ 'orderby' => 'date_sent' ] );
		$this->assertNotEmpty( $items );

		// and the user who can view it still searches
		$this->admin( true );
		[ $items ] = $this->list_table( new Email_Log_Table(), [ 's' => 'Jane' ] );
		$this->assertNotEmpty( $items );
	}

	public function test_a_restricted_users_events_show_the_contact_by_label() {

		get_db( 'events' )->add( [
			'time'          => time(),
			'time_scheduled' => time(),
			'status'        => 'failed',
			'contact_id'    => $this->contact_id,
			'event_type'    => Event::FUNNEL,
			'funnel_id'     => 1,
			'step_id'       => 1,
			'error_code'    => 'failed',
			'error_message' => 'Unable to send to ' . self::EMAIL . ' for Jane',
		] );

		$this->admin( true );
		[ , $html ] = $this->list_table( new Events_Table(), [ 'status' => 'failed' ] );
		$this->assertStringContainsString( self::EMAIL, $html );

		$this->admin( false );
		[ $items, $html ] = $this->list_table( new Events_Table(), [ 'status' => 'failed' ] );

		$this->assertNotEmpty( $items );
		$this->assertNoPii( $html );
		$this->assertStringContainsString( Pii::contact_label( $this->contact_id ), $html );
		$this->assertStringContainsString( Pii::REDACTED, $html );
	}
}
