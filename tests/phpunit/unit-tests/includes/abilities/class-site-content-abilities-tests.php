<?php

use Groundhogg\Contact;
use Groundhogg\Funnel;
use Groundhogg\Preferences;
use function Groundhogg\get_contactdata;

/**
 * The abilities that bring people and content into a new site: sync-users, import-contacts, list-flow-templates and
 * import-flow-template, and the form embed codes in create-flow and get-flow.
 */
class Site_Content_Abilities_Tests extends GH_UnitTestCase {

	const LIBRARY = 'https://library.test/wp-json/gh/v4/';

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/import-contacts' ) ) {
			$this->markTestSkipped( 'The abilities are not registered.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {
		delete_option( 'gh_disable_user_sync' );
		remove_all_filters( 'groundhogg/library/get_library_url' );
		remove_all_filters( 'pre_http_request' );

		parent::tearDown();
	}

	protected function run_ability( string $name, array $input = [] ) {
		return wp_get_ability( $name )->execute( $input );
	}

	public function test_sync_users_makes_contacts_for_users_without_one() {

		update_option( 'gh_disable_user_sync', true );

		$user_id = self::factory()->user->create( [ 'user_email' => 'unsynced@example.com' ] );

		$this->assertFalse( get_contactdata( 'unsynced@example.com' ) );

		delete_option( 'gh_disable_user_sync' );

		$result = $this->run_ability( 'groundhogg/sync-users' );

		$this->assertFalse( $result['background'] );
		$this->assertGreaterThan( 0, $result['synced'] );

		$contact = get_contactdata( 'unsynced@example.com' );

		$this->assertInstanceOf( Contact::class, $contact );
		$this->assertSame( $user_id, absint( $contact->user_id ) );
	}

	protected function import( array $input ) {
		return $this->run_ability( 'groundhogg/import-contacts', array_merge( [
			'csv'                => "Email,First,Last\nada@example.com,Ada,Lovelace\nalan@example.com,Alan,Turing",
			'field_map'          => [ 'Email' => 'email', 'First' => 'first_name', 'Last' => 'last_name' ],
			'email_is_confirmed' => false,
			'gdpr_consent'       => false,
			'marketing_consent'  => false,
		], $input ) );
	}

	public function test_import_contacts_from_csv_text() {

		$result = $this->import( [ 'tags' => [ 'From the old CRM' ], 'email_is_confirmed' => true ] );

		$this->assertSame( 2, $result['rows'] );
		$this->assertSame( 2, $result['imported'] );
		$this->assertFalse( $result['background'] );

		$ada = get_contactdata( 'ada@example.com' );

		$this->assertSame( 'Ada', $ada->get_first_name() );
		$this->assertTrue( $ada->has_tag( $result['import_tag_id'] ) );
		$this->assertTrue( $ada->has_tag( 'From the old CRM' ) );
		$this->assertSame( Preferences::CONFIRMED, absint( $ada->get_optin_status() ) );
	}

	/**
	 * Whether a list agreed to marketing is the person's to say, so there is no default
	 */
	public function test_import_needs_every_consent_answer() {

		// get_contactdata() caches, so an address no other test uses
		$result = $this->run_ability( 'groundhogg/import-contacts', [
			'csv'                => "Email\nno-consent-answer@example.com",
			'field_map'          => [ 'Email' => 'email' ],
			'email_is_confirmed' => false,
			'gdpr_consent'       => false,
		] );

		$this->assertWPError( $result );
		$this->assertFalse( get_contactdata( 'no-consent-answer@example.com' ) );
	}

	public function test_import_checks_the_map() {

		// get_contactdata() caches, so an address no other test uses
		$csv = "Email,First\nbad-map@example.com,Bad";

		// a column the file doesn't have
		$this->assertWPError( $this->import( [ 'csv' => $csv, 'field_map' => [ 'E-mail' => 'email' ] ] ) );

		// a field contacts don't have
		$this->assertWPError( $this->import( [ 'csv' => $csv, 'field_map' => [ 'Email' => 'email', 'First' => 'nickname' ] ] ) );

		// nothing to match contacts by
		$this->assertWPError( $this->import( [ 'csv' => $csv, 'field_map' => [ 'First' => 'first_name' ] ] ) );

		$this->assertFalse( get_contactdata( 'bad-map@example.com' ) );
	}

	public function test_import_takes_csv_or_url_not_both() {
		$this->assertWPError( $this->import( [ 'url' => 'https://example.com/list.csv' ] ) );
	}

	public function test_form_steps_say_how_to_embed_them() {

		$created = $this->run_ability( 'groundhogg/create-flow', [
			'title' => 'Signup',
			'steps' => [ [ 'type' => 'web_form', 'title' => 'Signup form', 'settings' => (object) [] ] ],
		] );

		$this->assertIsArray( $created );

		$step = $created['steps'][0];

		$this->assertStringContainsString( sprintf( 'id="%d"', $step['id'] ), $step['embed']['shortcode'] );
		$this->assertStringContainsString( 'forms/', $step['embed']['hosted_url'] );
		$this->assertStringContainsString( '<script', $step['embed']['iframe'] );

		$flow = $this->run_ability( 'groundhogg/get-flow', [ 'flow_id' => $created['id'] ] );

		$this->assertSame( $step['embed'], $flow['steps'][0]['embed'] );
	}

	/**
	 * Point the library at a fake one with two templates, one with a step type this site doesn't have
	 */
	protected function fake_library() {

		$template = function ( $id, $type ) {
			return [
				'ID'        => $id,
				'data'      => [ 'title' => "Template $id" ],
				'meta'      => [ 'description' => '<p>A <b>test</b> template.</p>' ],
				'campaigns' => [],
				'steps'     => [
					[
						'ID'     => $id * 10,
						'data'   => [
							'step_title'  => 'Wait a day',
							'step_type'   => $type,
							'step_group'  => 'action',
							'step_order'  => 1,
							'step_status' => 'active',
							'branch'      => 'main',
						],
						'meta'   => [],
						'export' => [],
					],
				],
			];
		};

		add_filter( 'groundhogg/library/get_library_url', function () {
			return self::LIBRARY;
		} );

		add_filter( 'pre_http_request', function ( $response, $args, $url ) use ( $template ) {

			if ( strpos( $url, self::LIBRARY ) !== 0 ) {
				return $response;
			}

			$path = strtok( substr( $url, strlen( self::LIBRARY ) ), '?' );
			$body = $path === 'funnels/5'
				? [ 'item' => $template( 5, 'delay_timer' ) ]
				: [ 'items' => [ $template( 5, 'delay_timer' ), $template( 6, 'not_a_step_type' ) ] ];

			return [
				'headers'  => [],
				'body'     => wp_json_encode( $body ),
				'response' => [ 'code' => 200, 'message' => 'OK' ],
				'cookies'  => [],
				'filename' => null,
			];
		}, 10, 3 );
	}

	public function test_flow_templates_only_list_what_this_site_can_build() {

		$this->fake_library();

		$templates = $this->run_ability( 'groundhogg/list-flow-templates' )['templates'];

		$this->assertSame( [ 5 ], wp_list_pluck( $templates, 'id' ) );
		$this->assertSame( 'A test template.', $templates[0]['description'] );
		$this->assertSame( [ [ 'type' => 'delay_timer', 'group' => 'action', 'title' => 'Wait a day' ] ], $templates[0]['steps'] );

		$this->assertWPError( $this->run_ability( 'groundhogg/import-flow-template', [ 'template_id' => 6 ] ) );
	}

	public function test_importing_a_template_makes_an_inactive_flow() {

		$this->fake_library();

		$result = $this->run_ability( 'groundhogg/import-flow-template', [ 'template_id' => 5, 'title' => 'My follow-up' ] );

		$this->assertIsArray( $result );
		$this->assertSame( 'inactive', $result['status'] );
		$this->assertSame( 'My follow-up', $result['title'] );

		$flow = new Funnel( $result['flow_id'] );

		$this->assertCount( 1, $flow->get_steps() );
		$this->assertSame( 'delay_timer', $flow->get_steps()[0]->get_type() );
	}
}
