<?php

use Groundhogg\Abilities\Funnels\Get_Flow;
use Groundhogg\Email;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\get_edit_version;
use function Groundhogg\maybe_refresh_lock;
use function Groundhogg\set_lock;

/**
 * The edit lock's version, which tells an open editor that something else changed what it's editing
 */
class Edit_Lock_Version_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function heartbeat( $object, $version = null ) {

		$received = [
			'id'   => $object->get_id(),
			'type' => $object->_get_object_type(),
		];

		if ( $version !== null ) {
			$received['version'] = $version;
		}

		return maybe_refresh_lock( [], [ 'groundhogg-refresh-lock' => $received ], 'screen' )['groundhogg-refresh-lock'];
	}

	public function test_an_emails_version_changes_when_it_changes() {

		$email = new Email( [ 'title' => 'Version', 'subject' => 'Hello', 'content' => '<p>Hi</p>' ] );

		$version = get_edit_version( new Email( $email->get_id() ) );

		// refreshing the lock isn't a change
		set_lock( $email );
		$this->assertEquals( $version, get_edit_version( new Email( $email->get_id() ) ) );

		$email->update( [ 'subject' => 'Changed' ] );
		$this->assertNotEquals( $version, get_edit_version( new Email( $email->get_id() ) ) );

		$version = get_edit_version( new Email( $email->get_id() ) );

		$email->update_meta( 'css', 'p { color: red; }' );
		$this->assertNotEquals( $version, get_edit_version( new Email( $email->get_id() ) ) );
	}

	public function test_a_flows_version_is_its_draft_revision() {

		$funnel = new Funnel( [ 'title' => 'Version', 'status' => 'active' ] );

		$funnel->add_step( [
			'step_type'   => 'delay_timer',
			'step_group'  => Step::ACTION,
			'step_status' => 'active',
		] );

		$revision = $funnel->while_editing( function () use ( $funnel ) {
			return Get_Flow::revision( $funnel );
		} );

		$this->assertEquals( $revision, get_edit_version( new Funnel( $funnel->get_id() ) ) );

		// refreshing the lock isn't a change, and nor is the lock on its emails
		set_lock( $funnel );
		$this->assertEquals( $revision, get_edit_version( new Funnel( $funnel->get_id() ) ) );

		// a staged change, like an ability makes
		$funnel->while_editing( function () use ( $funnel ) {
			$funnel->add_step( [
				'step_type'   => 'delay_timer',
				'step_group'  => Step::ACTION,
				'step_status' => 'inactive',
			] );
		} );

		$this->assertNotEquals( $revision, get_edit_version( new Funnel( $funnel->get_id() ) ) );
	}

	public function test_the_heartbeat_sends_the_version_when_asked() {

		$email = new Email( [ 'title' => 'Heartbeat', 'subject' => 'Hello' ] );

		$this->assertArrayNotHasKey( 'version', $this->heartbeat( $email ) );

		$sent = $this->heartbeat( $email, 'old' );
		$this->assertEquals( get_edit_version( new Email( $email->get_id() ) ), $sent['version'] );
	}

	public function test_the_heartbeat_doesnt_send_the_version_to_someone_else_while_its_locked() {

		$email = new Email( [ 'title' => 'Locked', 'subject' => 'Hello' ] );

		set_lock( $email );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$sent = $this->heartbeat( $email, 'old' );

		$this->assertArrayHasKey( 'lock_error', $sent );
		$this->assertArrayNotHasKey( 'version', $sent );
	}
}
