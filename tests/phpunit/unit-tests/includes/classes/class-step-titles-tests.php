<?php

use Groundhogg\Email;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\get_db;

/**
 * The flow editor shows titles from assets/js/admin/funnels/step-titles.js while a change is being saved, which should
 * be the same as generate_step_title(). These cases are written out for tests/js/step-titles.test.js, set
 * GH_UPDATE_JS_FIXTURES=1 to write them, otherwise the server's titles are checked against them.
 */
class Step_Titles_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		$this->factory()->truncate();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function fixture_file() {
		return dirname( __DIR__, 4 ) . '/js/fixtures/titles.json';
	}

	/**
	 * Each case's type, settings, and the names the title needs, keyed by what they're IDs of
	 *
	 * @return array[]
	 */
	protected function cases() {

		$tag = function ( $name ) {
			return get_db( 'tags' )->add( [ 'tag_name' => $name ] );
		};

		$tags = [
			$tag( 'Customer' ),
			$tag( 'Lead' ),
			$tag( 'Tom & Jerry' ),
			$tag( 'VIP' ),
			$tag( 'Newsletter' ),
		];

		$email = new Email( [ 'title' => 'Welcome & <thanks>', 'subject' => 'Hi', 'status' => 'ready' ] );
		$flow  = new Funnel( [ 'title' => 'Onboarding & more', 'status' => 'active' ] );

		$cases = [];

		foreach ( [ 'apply_tag', 'remove_tag' ] as $type ) {
			foreach ( [ 0, 1, 2, 3, 5 ] as $count ) {
				$cases[] = [ 'type' => $type, 'meta' => [ 'tags' => array_slice( $tags, 0, $count ) ] ];
			}
		}

		foreach ( [ 'tag_applied', 'tag_removed' ] as $type ) {
			foreach ( [ 0, 1, 2, 3, 5 ] as $count ) {
				foreach ( [ 'any', 'all' ] as $condition ) {
					$cases[] = [ 'type' => $type, 'meta' => [ 'tags' => array_slice( $tags, 0, $count ), 'condition' => $condition ] ];
				}
			}
		}

		$cases[] = [ 'type' => 'if_else', 'meta' => [] ];
		$cases[] = [ 'type' => 'if_else', 'meta' => [ 'include_display' => 'Has tag <b>Customer</b>' ] ];
		$cases[] = [ 'type' => 'if_else', 'meta' => [ 'exclude_display' => 'Has tag <b>Lead</b>' ] ];
		$cases[] = [ 'type' => 'if_else', 'meta' => [ 'include_display' => 'Has tag <b>Customer</b>', 'exclude_display' => 'Has tag <b>Lead</b>' ] ];

		$cases[] = [ 'type' => 'send_email', 'meta' => [] ];
		$cases[] = [ 'type' => 'send_email', 'meta' => [ 'email_id' => $email->get_id() ] ];

		$cases[] = [ 'type' => 'add_to_flow', 'meta' => [] ];
		$cases[] = [ 'type' => 'add_to_flow', 'meta' => [ 'funnel_id' => $flow->get_id() ] ];

		$names = [
			'tag'    => [],
			'email'  => [ $email->get_id() => $email->get_title() ],
			'funnel' => [ $flow->get_id() => $flow->get_title() ],
		];

		foreach ( $tags as $id ) {
			$names['tag'][ $id ] = get_db( 'tags' )->get( $id )->tag_name;
		}

		$funnel = new Funnel( [ 'title' => 'titles', 'status' => 'inactive' ] );

		foreach ( $cases as &$case ) {

			$step = $funnel->add_step( [
				'step_type'  => $case['type'],
				'step_group' => Step::ACTION,
				'meta'       => $case['meta'],
			] );

			$element = $step->get_step_element();
			$element->set_current_step( $step );

			$case['title'] = (string) $element->generate_step_title( $step );
		}

		return [
			'names' => $names,
			'cases' => $cases,
		];
	}

	public function test_the_server_titles_match_the_js_fixtures() {

		$fixture = $this->cases();
		$file    = $this->fixture_file();

		if ( getenv( 'GH_UPDATE_JS_FIXTURES' ) ) {
			file_put_contents( $file, wp_json_encode( $fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
			$this->assertFileExists( $file );

			return;
		}

		$this->assertFileExists( $file, 'Run with GH_UPDATE_JS_FIXTURES=1 to write the fixtures' );

		$saved = json_decode( file_get_contents( $file ), true );

		// IDs differ between runs, the titles don't
		$this->assertSame( wp_list_pluck( $saved['cases'], 'title' ), wp_list_pluck( $fixture['cases'], 'title' ), 'The server generates different titles than the JS fixtures, run with GH_UPDATE_JS_FIXTURES=1 and check tests/js' );
	}
}
