<?php

use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\get_contactdata;

/**
 * How often a contact can complete a trigger, the trigger frequency settings in the flow editor
 */
class Step_Trigger_Frequency_Tests extends GH_UnitTestCase {

	/**
	 * @var Step
	 */
	protected $trigger;

	/**
	 * @var \Groundhogg\Contact
	 */
	protected $contact;

	public function setUp(): void {
		parent::setUp();

		$this->factory()->truncate();

		$funnel = new Funnel( [
			'title'  => 'trigger frequency',
			'status' => 'active',
		] );

		$this->trigger = $funnel->add_step( [
			'step_type'   => 'tag_applied',
			'step_group'  => Step::BENCHMARK,
			'step_status' => 'active',
			'step_order'  => 1,
			'step_level'  => 1,
		] );

		$funnel->add_step( [
			'step_type'   => 'delay_timer',
			'step_group'  => Step::ACTION,
			'step_status' => 'active',
		] );

		$funnel->set_step_levels();

		$this->trigger = new Step( $this->trigger->get_id() );
		$this->contact = get_contactdata( $this->factory()->contacts->create() );
	}

	/**
	 * Settings the flow editor saves, active steps stage meta so write it directly
	 */
	protected function set_frequency( array $meta ) {
		foreach ( $meta as $key => $value ) {
			$this->trigger->update_meta_bypass_changes( $key, $value );
		}

		$this->trigger = new Step( $this->trigger->get_id() );
	}

	/**
	 * A time the contact completed the trigger
	 */
	protected function completed( int $days_ago = 0 ) {
		$this->factory()->events->create( [
			'contact_id' => $this->contact->get_id(),
			'funnel_id'  => $this->trigger->get_funnel_id(),
			'step_id'    => $this->trigger->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::COMPLETE,
			'time'       => time() - $days_ago * DAY_IN_SECONDS,
		] );
	}

	protected function can_trigger() {
		return $this->trigger->check_trigger_frequency( $this->contact );
	}

	public function test_it_is_a_starting_trigger() {
		$this->assertTrue( $this->trigger->is_starting() );
	}

	public function test_unlimited_by_default() {
		$this->completed();
		$this->completed();

		$this->assertTrue( $this->can_trigger() );

		$this->set_frequency( [ '_trigger_frequency' => 'unlimited' ] );

		$this->assertTrue( $this->can_trigger() );
	}

	public function test_once() {
		$this->set_frequency( [ '_trigger_frequency' => 'once' ] );

		$this->assertTrue( $this->can_trigger() );

		$this->completed( 400 );

		$this->assertFalse( $this->can_trigger() );
		$this->assertFalse( $this->trigger->can_complete( $this->contact ) );
	}

	public function test_x_times() {
		$this->set_frequency( [
			'_trigger_frequency'         => 'x',
			'_trigger_frequency_x_times' => 2,
		] );

		$this->completed( 100 );

		$this->assertTrue( $this->can_trigger() );

		$this->completed( 50 );

		$this->assertFalse( $this->can_trigger() );
	}

	public function test_x_times_within_x_days() {
		$this->set_frequency( [
			'_trigger_frequency'         => 'x',
			'_trigger_frequency_x_times' => 2,
			'_trigger_frequency_range'   => 'x_days',
			'_trigger_frequency_x_days'  => 30,
		] );

		// longer ago than 30 days don't count
		$this->completed( 60 );
		$this->completed( 45 );
		$this->completed( 10 );

		$this->assertTrue( $this->can_trigger() );

		$this->completed( 1 );

		$this->assertFalse( $this->can_trigger() );
	}

	public function test_other_contacts_dont_count() {
		$this->set_frequency( [ '_trigger_frequency' => 'once' ] );

		$this->factory()->events->create( [
			'contact_id' => $this->factory()->contacts->create(),
			'funnel_id'  => $this->trigger->get_funnel_id(),
			'step_id'    => $this->trigger->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::COMPLETE,
		] );

		$this->assertTrue( $this->can_trigger() );
	}
}
