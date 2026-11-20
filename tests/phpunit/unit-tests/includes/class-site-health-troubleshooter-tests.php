<?php

use Groundhogg\Admin\Help\Help_Page;
use Groundhogg\Plugin;

/**
 * The troubleshooter's possible issues are the Site Health tests that aren't good, so a check is written once, and the
 * updates check that used to be the troubleshooter's own.
 */
class Site_Health_Troubleshooter_Tests extends GH_UnitTestCase {

	public function tear_down() {
		delete_site_transient( 'update_plugins' );
		remove_all_filters( 'groundhogg/site_health/tests' );
		parent::tear_down();
	}

	protected function set_updates( array $basenames ) {

		$updates           = new stdClass();
		$updates->response = [];

		foreach ( $basenames as $basename ) {
			$updates->response[ $basename ] = (object) [ 'new_version' => '99.0' ];
		}

		set_site_transient( 'update_plugins', $updates );
	}

	public function test_an_update_for_the_plugin_is_recommended() {

		$this->set_updates( [ GROUNDHOGG_PLUGIN_BASE ] );

		$result = Plugin::instance()->site_health->test_updates();

		$this->assertSame( 'recommended', $result['status'] );
		$this->assertArrayHasKey( admin_url( 'update-core.php' ), $result['links'] );
	}

	public function test_updates_for_other_plugins_are_left_to_wordpress() {

		$this->set_updates( [ 'some-other-plugin/some-other-plugin.php' ] );

		$this->assertSame( 'good', Plugin::instance()->site_health->test_updates()['status'] );

		delete_site_transient( 'update_plugins' );

		$this->assertSame( 'good', Plugin::instance()->site_health->test_updates()['status'] );
	}

	public function test_the_troubleshooter_shows_the_checks_that_arent_good_most_serious_first() {

		$result = function ( $id, $status ) {
			return function () use ( $id, $status ) {
				return [
					'test'        => 'groundhogg_' . $id,
					'status'      => $status,
					'label'       => "<b>$id</b>",
					'description' => "<p>About $id</p><script>alert(1)</script>",
					'links'       => [ 'https://example.com/fix' => "Fix $id" ],
				];
			};
		};

		add_filter( 'groundhogg/site_health/tests', function () use ( $result ) {
			return [
				'fine'     => [ 'Fine', $result( 'fine', 'good' ) ],
				'minor'    => [ 'Minor', $result( 'minor', 'recommended' ) ],
				'broken'   => [ 'Broken', $result( 'broken', 'critical' ) ],
				'minor_2'  => [ 'Minor 2', $result( 'minor_2', 'recommended' ) ],
				'broken_2' => [ 'Broken 2', $result( 'broken_2', 'critical' ) ],
			];
		} );

		$problems = Help_Page::health_problems();

		$this->assertSame( [ 'broken', 'broken_2', 'minor', 'minor_2' ], wp_list_pluck( $problems, 'id' ) );

		$this->assertSame( 'broken', $problems[0]['label'] );
		$this->assertSame( 'critical', $problems[0]['status'] );
		$this->assertStringContainsString( '<p>About broken</p>', $problems[0]['description'] );
		$this->assertStringNotContainsString( '<script>', $problems[0]['description'] );
		$this->assertSame( [ [ 'url' => 'https://example.com/fix', 'text' => 'Fix broken' ] ], $problems[0]['links'] );
	}

	/**
	 * The troubleshooter's cron step shows the same status an agent gets, and exactly what the cron job should run
	 */
	public function test_the_troubleshooter_gets_the_same_cron_status_as_an_agent() {

		$status = \Groundhogg\Abilities\Setup\Get_Cron_Status::status();

		$this->assertSame( site_url( 'gh-cron.php' ), $status['event_queue_cron']['job']['url'] );
		$this->assertStringContainsString( 'gh-cron.php', $status['event_queue_cron']['job']['command'] );

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/get-cron-status' ) ) {
			return;
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$ability = wp_get_ability( 'groundhogg/get-cron-status' )->execute( [] );

		$this->assertSame( $status['event_queue_cron'], $ability['event_queue_cron'] );
		$this->assertSame( $status['wordpress_cron'], $ability['wordpress_cron'] );
	}

	public function test_every_health_check_says_how_its_fixed() {

		// without registering it
		$ability = ( new ReflectionClass( \Groundhogg\Abilities\Setup\Get_Setup_Status::class ) )->newInstanceWithoutConstructor();
		$fixes   = ( function () {
			return $this->health_fixes();
		} )->call( $ability );

		foreach ( array_keys( Plugin::instance()->site_health->tests() ) as $id ) {
			$this->assertArrayHasKey( $id, $fixes, $id );
		}
	}
}
