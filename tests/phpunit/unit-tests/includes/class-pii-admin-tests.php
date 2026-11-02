<?php

use Groundhogg\Admin\Contacts\Tables\Contact_Table_Columns;
use Groundhogg\Admin\Contacts\Tables\Contacts_Table;
use Groundhogg\Api\V4\Contacts_Api;
use Groundhogg\Pii;
use function Groundhogg\get_contactdata;

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
}
