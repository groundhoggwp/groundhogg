<?php

use Groundhogg\Broadcast;
use Groundhogg\Campaign;
use Groundhogg\Rewrites;
use function Groundhogg\get_db;
use function Groundhogg\list_broadcasts_archive;

/**
 * The public archive of a campaign: which broadcasts it shows, and who it's shown to
 *
 * @see Rewrites::is_in_campaign_archive()
 * @see list_broadcasts_archive()
 */
class Campaign_Archive_Tests extends GH_UnitTestCase {

	protected $run_id;

	public function setUp(): void {
		parent::setUp();
		$this->run_id = uniqid();
		wp_set_current_user( 0 );
	}

	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	protected function campaign( $visibility ): Campaign {

		$id = get_db( 'campaigns' )->add( [
			'name'       => 'Archive ' . $visibility,
			'slug'       => $visibility . '-' . $this->run_id,
			'visibility' => $visibility,
		] );

		return new Campaign( $id );
	}

	/**
	 * @param string        $status
	 * @param Campaign|null $campaign
	 * @param string        $type email or sms
	 */
	protected function broadcast( string $status, ?Campaign $campaign, string $type = 'email' ): Broadcast {

		$id = get_db( 'broadcasts' )->add( [
			'object_id'   => 1,
			'object_type' => $type,
			'status'      => $status,
			'send_time'   => time() - HOUR_IN_SECONDS,
			'query'       => [],
		] );

		$broadcast = new Broadcast( $id );

		if ( $campaign ) {
			$broadcast->create_relationship( $campaign );
		}

		return $broadcast;
	}

	public function test_a_sent_email_of_the_campaign_is_in_its_archive() {

		$public = $this->campaign( 'public' );

		$this->assertTrue( Rewrites::is_in_campaign_archive( $this->broadcast( 'sent', $public ), $public ) );
	}

	public function test_a_broadcast_that_has_not_been_sent_is_not() {

		$public = $this->campaign( 'public' );

		foreach ( [ 'scheduled', 'pending', 'sending', 'cancelled' ] as $status ) {
			$this->assertFalse( Rewrites::is_in_campaign_archive( $this->broadcast( $status, $public ), $public ), $status );
		}
	}

	public function test_a_broadcast_of_another_campaign_or_none_is_not_even_if_it_was_sent() {

		$public = $this->campaign( 'public' );
		$hidden = $this->campaign( 'hidden' );

		$this->assertFalse( Rewrites::is_in_campaign_archive( $this->broadcast( 'sent', $hidden ), $public ), 'one of a hidden campaign, through the slug of a public one' );
		$this->assertFalse( Rewrites::is_in_campaign_archive( $this->broadcast( 'sent', null ), $public ), 'one with no campaign' );
	}

	public function test_a_broadcast_that_is_not_an_email_or_does_not_exist_is_not() {

		$public = $this->campaign( 'public' );

		$this->assertFalse( Rewrites::is_in_campaign_archive( $this->broadcast( 'sent', $public, 'sms' ), $public ) );
		$this->assertFalse( Rewrites::is_in_campaign_archive( new Broadcast( 999999999 ), $public ) );
	}

	public function test_a_broadcast_in_two_campaigns_is_in_both() {

		$one = $this->campaign( 'public' );
		$two = new Campaign( get_db( 'campaigns' )->add( [ 'name' => 'Second', 'slug' => 'second-' . $this->run_id, 'visibility' => 'public' ] ) );

		$broadcast = $this->broadcast( 'sent', $one );
		$broadcast->create_relationship( $two );

		$this->assertTrue( Rewrites::is_in_campaign_archive( $broadcast, $one ) );
		$this->assertTrue( Rewrites::is_in_campaign_archive( $broadcast, $two ) );
	}

	public function test_the_list_of_a_hidden_campaign_is_only_for_the_people_that_manage_campaigns() {

		$hidden = $this->campaign( 'hidden' );
		$this->broadcast( 'sent', $hidden );

		try {
			list_broadcasts_archive( [ 'campaign' => $hidden ] );
			$this->fail( 'a visitor got the list of a hidden campaign' );
		} catch ( \Exception $e ) {
			$this->assertSame( 'Campaign is not public', $e->getMessage() );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertThrows( fn() => list_broadcasts_archive( [ 'campaign' => $hidden ] ) );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$list = list_broadcasts_archive( [ 'campaign' => $hidden ] );

		$this->assertSame( 1, $list['total_items'] );
	}

	public function test_the_list_of_a_public_campaign_is_for_everyone_and_only_has_what_was_sent() {

		$public = $this->campaign( 'public' );
		$sent   = $this->broadcast( 'sent', $public );
		$this->broadcast( 'scheduled', $public );
		$this->broadcast( 'sent', null );

		$list = list_broadcasts_archive( [ 'campaign' => $public ] );

		$this->assertSame( 1, $list['total_items'] );
		$this->assertSame( $sent->get_id(), $list['items'][0]->get_id() );
	}

	public function test_a_campaign_that_does_not_exist_has_no_list_for_anyone() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->assertThrows( fn() => list_broadcasts_archive( [ 'campaign' => 999999999 ] ) );
	}

	protected function assertThrows( callable $call ) {
		try {
			$call();
		} catch ( \Exception $e ) {
			$this->addToAssertionCount( 1 );

			return;
		}

		$this->fail( 'It didn\'t throw.' );
	}
}
