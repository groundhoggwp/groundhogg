<?php

use Groundhogg\Funnel;
use Groundhogg\Steps\Actions\Action;
use Groundhogg\Steps\Actions\Delay_Timer;
use Groundhogg\Steps\Benchmarks\Benchmark;

/**
 * Calendar reference for the fixed dates below: 1 March 2026 is a Sunday.
 */
class Delay_Timer_Tests extends GH_UnitTestCase {

	protected $timezone_string;

	public function setUp(): void {
		parent::setUp();
		$this->timezone_string = get_option( 'timezone_string' );
	}

	public function tearDown(): void {
		update_option( 'timezone_string', $this->timezone_string );
		parent::tearDown();
	}

	/**
	 * Add a delay timer with the given settings
	 *
	 * @param array $meta
	 *
	 * @return \Groundhogg\Step
	 */
	protected function make_timer( array $meta ) {
		$funnel = new Funnel( $this->factory()->funnels->create() );

		return $funnel->add_step( [
			'step_title' => 'Delay timer',
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Action::GROUP,
			'meta'       => wp_parse_args( $meta, [
				'delay_amount' => 0,
				'delay_type'   => 'none',
				'run_when'     => 'now',
			] )
		] );
	}

	/**
	 * Calculate the run time from a fixed local base date and format the result in the same timezone
	 *
	 * @param \Groundhogg\Step   $timer
	 * @param string             $base 'Y-m-d H:i:s' in $tz
	 * @param \DateTimeZone|null $tz defaults to the site timezone
	 *
	 * @return string 'Y-m-d H:i' in $tz
	 */
	protected function run_date( $timer, string $base, $tz = null ) {
		$tz        = $tz ?: wp_timezone();
		$timestamp = $timer->get_run_time( ( new \DateTime( $base, $tz ) )->getTimestamp() );

		return ( new \DateTime( '@' . $timestamp ) )->setTimezone( $tz )->format( 'Y-m-d H:i' );
	}

	/**
	 * Weekdays, a Saturday after the run time rolls to Sunday, then on to Monday
	 */
	public function test_run_on_weekday() {
		$timer = $this->make_timer( [
			'run_when'    => 'later',
			'run_time'    => '09:00:00',
			'run_on_type' => 'weekday',
		] );

		$this->assertEquals( '2026-03-16 09:00', $this->run_date( $timer, '2026-03-14 10:00:00' ) );
	}

	/**
	 * Weekends, a Wednesday rolls to Saturday and a Sunday stays put
	 */
	public function test_run_on_weekend() {
		$timer = $this->make_timer( [ 'run_on_type' => 'weekend' ] );

		$this->assertEquals( '2026-03-14 08:00', $this->run_date( $timer, '2026-03-11 08:00:00' ) );
		$this->assertEquals( '2026-03-15 08:00', $this->run_date( $timer, '2026-03-15 08:00:00' ) );
	}

	/**
	 * Any Wednesday or Friday, from a Thursday
	 */
	public function test_run_on_days_of_week() {
		$timer = $this->make_timer( [
			'run_on_type' => 'day_of_week',
			'run_on_dow'  => [ 'wednesday', 'friday' ],
		] );

		$this->assertEquals( '2026-03-13 10:00', $this->run_date( $timer, '2026-03-12 10:00:00' ) );
	}

	/**
	 * Nth weekday of any month, when this month's has passed it rolls to next month
	 */
	public function test_run_on_nth_day_of_week() {
		$timer = $this->make_timer( [
			'run_on_type'     => 'day_of_week',
			'run_on_dow_type' => 'second',
			'run_on_dow'      => [ 'tuesday' ],
		] );

		$this->assertEquals( '2026-04-14 10:00', $this->run_date( $timer, '2026-03-12 10:00:00' ) );

		$timer = $this->make_timer( [
			'run_on_type'     => 'day_of_week',
			'run_on_dow_type' => 'fourth',
			'run_on_dow'      => [ 'tuesday' ],
		] );

		$this->assertEquals( '2026-03-24 10:00', $this->run_date( $timer, '2026-03-12 10:00:00' ) );
	}

	/**
	 * Last weekday of the month, March 2026 has 5 Tuesdays
	 */
	public function test_run_on_last_day_of_week() {
		$timer = $this->make_timer( [
			'run_on_type'     => 'day_of_week',
			'run_on_dow_type' => 'last',
			'run_on_dow'      => [ 'tuesday' ],
		] );

		$this->assertEquals( '2026-03-31 10:00', $this->run_date( $timer, '2026-03-12 10:00:00' ) );
	}

	/**
	 * Last Friday of June
	 */
	public function test_run_on_last_day_of_week_in_specific_month() {
		$timer = $this->make_timer( [
			'run_on_type'       => 'day_of_week',
			'run_on_dow_type'   => 'last',
			'run_on_dow'        => [ 'friday' ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'june' ],
		] );

		$this->assertEquals( '2026-06-26 10:00', $this->run_date( $timer, '2026-03-12 10:00:00' ) );
	}

	/**
	 * Any Monday in January, which has passed this year
	 */
	public function test_run_on_day_of_week_in_specific_month_next_year() {
		$timer = $this->make_timer( [
			'run_on_type'       => 'day_of_week',
			'run_on_dow'        => [ 'monday' ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'january' ],
		] );

		$this->assertEquals( '2027-01-04 10:00', $this->run_date( $timer, '2026-03-12 10:00:00' ) );
	}

	/**
	 * The 4th of July, and the last day of February which has passed this year
	 */
	public function test_run_on_day_of_month_in_specific_month() {
		$timer = $this->make_timer( [
			'run_on_type'       => 'day_of_month',
			'run_on_dom'        => [ 4 ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'july' ],
		] );

		$this->assertEquals( '2026-07-04 10:00', $this->run_date( $timer, '2026-03-12 10:00:00' ) );

		$timer = $this->make_timer( [
			'run_on_type'       => 'day_of_month',
			'run_on_dom'        => [ 'last' ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'february' ],
		] );

		$this->assertEquals( '2027-02-28 10:00', $this->run_date( $timer, '2026-03-12 10:00:00' ) );
	}

	/**
	 * If today matches it runs today
	 */
	public function test_run_on_day_of_month_today() {
		$timer = $this->make_timer( [
			'run_on_type' => 'day_of_month',
			'run_on_dom'  => [ 12 ],
		] );

		$this->assertEquals( '2026-03-12 10:00', $this->run_date( $timer, '2026-03-12 10:00:00' ) );
	}

	/**
	 * Between 9 and 5
	 */
	public function test_run_between() {
		$timer = $this->make_timer( [
			'run_when'    => 'between',
			'run_time'    => '09:00:00',
			'run_time_to' => '17:00:00',
		] );

		$this->assertEquals( '2026-03-12 09:00', $this->run_date( $timer, '2026-03-12 07:00:00' ) );
		$this->assertEquals( '2026-03-12 12:00', $this->run_date( $timer, '2026-03-12 12:00:00' ) );
		$this->assertEquals( '2026-03-13 09:00', $this->run_date( $timer, '2026-03-12 18:00:00' ) );
	}

	/**
	 * The run time is relative to the base timestamp, not the current time
	 */
	public function test_run_later_relative_to_base_time() {
		$timer = $this->make_timer( [
			'run_when' => 'later',
			'run_time' => '09:00:00',
		] );

		$this->assertEquals( '2026-03-12 09:00', $this->run_date( $timer, '2026-03-12 08:00:00' ) );
		$this->assertEquals( '2026-03-13 09:00', $this->run_date( $timer, '2026-03-12 10:00:00' ) );
	}

	/**
	 * Run at 9 am in the contact's timezone rather than the site's
	 */
	public function test_run_in_contact_timezone() {
		update_option( 'timezone_string', 'UTC' );

		$timer = $this->make_timer( [
			'run_when'         => 'later',
			'run_time'         => '09:00:00',
			'send_in_timezone' => true,
		] );

		$contact = $this->factory()->contacts->create_and_get();
		$contact->update_meta( 'time_zone', 'America/New_York' );
		$timer->enqueued_contact = $contact;

		$new_york = new \DateTimeZone( 'America/New_York' );

		// 8 am in New York
		$this->assertEquals( '2026-03-12 09:00', $this->run_date( $timer, '2026-03-12 08:00:00', $new_york ) );
	}

	/**
	 * The wall clock time is kept across a DST change (8 March 2026 in New York)
	 */
	public function test_run_on_day_of_month_across_dst() {
		update_option( 'timezone_string', 'America/New_York' );

		$timer = $this->make_timer( [
			'run_on_type' => 'day_of_month',
			'run_on_dom'  => [ 9 ],
		] );

		$this->assertEquals( '2026-03-09 10:00', $this->run_date( $timer, '2026-03-07 10:00:00' ) );
	}

	/**
	 * The 31st skips months that don't have one instead of overflowing into the 1st of the next month
	 */
	public function test_run_on_day_of_month_skips_short_months() {
		$timer = $this->make_timer( [
			'run_on_type' => 'day_of_month',
			'run_on_dom'  => [ 31 ],
		] );

		$this->assertEquals( '2026-05-31 10:00', $this->run_date( $timer, '2026-04-10 10:00:00' ) );

		$timer = $this->make_timer( [
			'run_on_type'       => 'day_of_month',
			'run_on_dom'        => [ 30 ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'february', 'march' ],
		] );

		$this->assertEquals( '2026-03-30 10:00', $this->run_date( $timer, '2026-02-10 10:00:00' ) );
	}

	/**
	 * From the 31st of January, the 15th of February must not be skipped
	 */
	public function test_run_on_day_of_month_from_end_of_month() {
		$timer = $this->make_timer( [
			'run_on_type' => 'day_of_month',
			'run_on_dom'  => [ 15 ],
		] );

		$this->assertEquals( '2026-02-15 10:00', $this->run_date( $timer, '2026-01-31 10:00:00' ) );
	}

	/**
	 * After a long delay the day constraints are still applied, relative to the delayed date
	 */
	public function test_run_on_specific_month_after_long_delay() {
		// 20 March 2027 after the delay, so next is 15 March 2028
		$timer = $this->make_timer( [
			'delay_amount'      => 14,
			'delay_type'        => 'months',
			'run_on_type'       => 'day_of_month',
			'run_on_dom'        => [ 15 ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'march' ],
		] );

		$this->assertEquals( '2028-03-15 10:00', $this->run_date( $timer, '2026-01-20 10:00:00' ) );

		// first Monday of March 2027 is the 1st, so next is 6 March 2028
		$timer = $this->make_timer( [
			'delay_amount'      => 14,
			'delay_type'        => 'months',
			'run_on_type'       => 'day_of_week',
			'run_on_dow_type'   => 'first',
			'run_on_dow'        => [ 'monday' ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'march' ],
		] );

		$this->assertEquals( '2028-03-06 10:00', $this->run_date( $timer, '2026-01-20 10:00:00' ) );
	}

	/**
	 * A date that never exists doesn't fall back to the delayed date, that's a day that the timer was set not to run
	 * on. It's looked for for 5 years, so that it doesn't loop forever, and then there's no time to run it.
	 */
	public function test_run_on_impossible_date_has_no_run_time() {
		$timer = $this->make_timer( [
			'delay_amount'      => 1,
			'delay_type'        => 'days',
			'run_on_type'       => 'day_of_month',
			'run_on_dom'        => [ 30 ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'february' ],
		] );

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( '5 years' );

		$this->run_date( $timer, '2026-03-12 10:00:00' );
	}

	/**
	 * Nothing selected to run on isn't a restriction, it's any day, as it was before
	 *
	 * @dataProvider empty_selections
	 */
	public function test_nothing_selected_to_run_on_is_any_day( array $meta ) {
		$timer = $this->make_timer( array_merge( [ 'delay_amount' => 1, 'delay_type' => 'days' ], $meta ) );

		$this->assertEquals( '2026-03-13 10:00', $this->run_date( $timer, '2026-03-12 10:00:00' ) );
	}

	public function empty_selections() {
		return [
			'no days of the week'            => [ [ 'run_on_type' => 'day_of_week', 'run_on_dow' => [] ] ],
			'no days of the month'           => [ [ 'run_on_type' => 'day_of_month', 'run_on_dom' => [] ] ],
			'no months, by day of the week'  => [ [ 'run_on_type' => 'day_of_week', 'run_on_dow' => [ 'friday' ], 'run_on_month_type' => 'specific', 'run_on_months' => [] ] ],
			'no months, by day of the month' => [ [ 'run_on_type' => 'day_of_month', 'run_on_dom' => [ 13 ], 'run_on_month_type' => 'specific', 'run_on_months' => [] ] ],
		];
	}

	/**
	 * Only the last day of the month, with nothing else, is a selection
	 */
	public function test_the_last_day_alone_is_a_selection() {
		$timer = $this->make_timer( [
			'delay_amount' => 1,
			'delay_type'   => 'days',
			'run_on_type'  => 'day_of_month',
			'run_on_dom'   => [ 'last' ],
		] );

		$this->assertEquals( '2026-03-31 10:00', $this->run_date( $timer, '2026-03-12 10:00:00' ) );
	}

	/**
	 * A contact isn't put in a timer that has no time to run, and what they were waiting for stays
	 */
	public function test_a_contact_is_not_enqueued_in_a_timer_with_no_run_time() {

		$timer = $this->make_timer( [
			'delay_amount'      => 1,
			'delay_type'        => 'days',
			'run_on_type'       => 'day_of_month',
			'run_on_dom'        => [ 30 ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'february' ],
		] );

		$contact = $this->factory()->contacts->create_and_get();

		// something that they're waiting for in the same flow
		$waiting = $this->factory()->event_queue->create( [
			'funnel_id'  => $timer->get_funnel_id(),
			'step_id'    => $timer->get_id() + 1000,
			'contact_id' => $contact->get_id(),
			'event_type' => \Groundhogg\Event::FUNNEL,
			'status'     => \Groundhogg\Event::WAITING,
		] );

		$this->assertFalse( $timer->enqueue( $contact ) );

		$this->assertSame( 0, \Groundhogg\get_db( 'event_queue' )->count( [ 'step_id' => $timer->get_id(), 'contact_id' => $contact->get_id() ] ) );
		$this->assertSame( 'waiting', ( new \Groundhogg\Event_Queue_Item( $waiting ) )->status, 'not skipped' );

		$failed = \Groundhogg\get_db( 'events' )->query( [ 'step_id' => $timer->get_id(), 'contact_id' => $contact->get_id() ] );

		$this->assertCount( 1, $failed );
		$this->assertSame( 'failed', $failed[0]->status );
		$this->assertSame( 'no_run_time', $failed[0]->error_code );
		$this->assertStringContainsString( '5 years', $failed[0]->error_message );
	}

	/**
	 * An hour from now
	 */
	public function test_enqueue_1_hour_from_now() {

		$funnel_id = $this->factory()->funnels->create();
		$funnel    = new Funnel( $funnel_id );

		$timer = $funnel->add_step( [
			'step_title' => '1 hour from now',
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Action::GROUP,
			'meta'       => [
				'delay_amount' => 1,
				'delay_type'   => 'hours',
				'run_when'     => 'now',
				'run_time'     => '',
			]
		] );


		$this->assertEquals( strtotime( '+1 hours' ), $timer->get_run_time() );
	}

	/**
	 * A day from now
	 */
	public function test_enqueue_1_day_from_now() {

		$funnel_id = $this->factory()->funnels->create();
		$funnel    = new Funnel( $funnel_id );

		$timer = $funnel->add_step( [
			'step_title' => '1 day from now',
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Action::GROUP,
			'meta'       => [
				'delay_amount' => 1,
				'delay_type'   => 'days',
				'run_when'     => 'now',
				'run_time'     => '',
			]
		] );


		$this->assertEquals( strtotime( '+1 days' ), $timer->get_run_time() );
	}

	/**
	 * Next day at 8:00 AM
	 */
	public function test_enqueue_at_8_am() {

		$funnel_id = $this->factory()->funnels->create();
		$funnel    = new Funnel( $funnel_id );

		$timer = $funnel->add_step( [
			'step_title' => 'At 8 am',
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Action::GROUP,
			'meta'       => [
				'delay_amount' => 1,
				'delay_type'   => 'hours',
				'run_when'     => 'later',
				'run_time'     => '8:00:00',
			]
		] );

		if ( time() < strtotime( 'today 8:00:00' ) ){
			$this->assertEquals( strtotime( 'today 8:00:00' ), $timer->get_run_time() );
		} else {
			$this->assertEquals( strtotime( 'tomorrow 8:00:00' ), $timer->get_run_time() );
		}
	}

	/**
	 * Next day at 8:00 AM
	 */
	public function test_enqueue_at_12_01_am() {

		$funnel_id = $this->factory()->funnels->create();
		$funnel    = new Funnel( $funnel_id );

		$timer = $funnel->add_step( [
			'step_title' => 'At 12:01 am',
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Action::GROUP,
			'meta'       => [
				'delay_amount' => 4,
				'delay_type'   => 'minutes',
				'run_when'     => 'later',
				'run_time'     => '00:01:00',
			]
		] );

		if ( time() < strtotime( 'today 00:01:00' ) ){
			$this->assertEquals( strtotime( 'today 00:01:00' ), $timer->get_run_time() );
		} else {
			$this->assertEquals( strtotime( 'tomorrow 00:01:00' ), $timer->get_run_time() );
		}
	}

	/**
	 * Run on the 15th or the last day of the month, 'last' must survive sanitization
	 */
	public function test_run_on_15th_and_last_day_of_month() {

		$funnel_id = $this->factory()->funnels->create();
		$funnel    = new Funnel( $funnel_id );

		$timer = $funnel->add_step( [
			'step_title' => '15th or last day of month',
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Action::GROUP,
			'meta'       => [
				'delay_amount'      => 0,
				'delay_type'        => 'none',
				'run_when'          => 'now',
				'run_on_type'       => 'day_of_month',
				'run_on_month_type' => 'any',
				'run_on_dom'        => [ '15', 'last', '0', '32', 'foo' ], // the editor posts strings
			]
		] );

		$this->assertSame( [ 15, 'last' ], $timer->get_meta( 'run_on_dom' ) );

		$base = function ( $date ) {
			return ( new \DateTime( $date, wp_timezone() ) )->getTimestamp();
		};

		$run_date = function ( $timestamp ) {
			return ( new \DateTime( '@' . $timestamp ) )->setTimezone( wp_timezone() )->format( 'Y-m-d' );
		};

		// before the 15th, runs on the 15th
		$this->assertEquals( '2026-03-15', $run_date( $timer->get_run_time( $base( '2026-03-10 10:00:00' ) ) ) );

		// after the 15th, runs on the last day of the month rather than next month's 15th
		$this->assertEquals( '2026-03-31', $run_date( $timer->get_run_time( $base( '2026-03-20 10:00:00' ) ) ) );
		$this->assertEquals( '2026-02-28', $run_date( $timer->get_run_time( $base( '2026-02-20 10:00:00' ) ) ) );
	}

	protected function add_timer( array $meta ) {
		$funnel = new Funnel( $this->factory()->funnels->create() );

		return $funnel->add_step( [
			'step_title' => 'Delay Timer',
			'step_type'  => Delay_Timer::TYPE,
			'step_group' => Action::GROUP,
			'meta'       => $meta
		] );
	}

	protected function generated_title( array $meta ) {
		$timer = $this->add_timer( $meta );

		return $timer->get_step_element()->generate_step_title( $timer );
	}

	/**
	 * Without a delay_preview from the editor, the title comes from the actual settings, not a hardcoded "Wait 3 days"
	 */
	public function test_title_without_preview_uses_settings() {
		$this->assertEquals( 'Wait at least <b>5 days</b> and then run at any time', $this->generated_title( [
			'delay_amount' => 5,
			'delay_type'   => 'days',
		] ) );

		$this->assertEquals( 'Wait at least <b>1 hour</b> and then run at any time', $this->generated_title( [
			'delay_amount' => 1,
			'delay_type'   => 'hours',
		] ) );
	}

	public function test_title_without_any_settings_uses_defaults() {
		$this->assertEquals( 'Wait at least <b>3 days</b> and then run at any time', $this->generated_title( [] ) );
	}

	public function test_title_uses_editor_preview_when_present() {
		$this->assertEquals( 'Custom preview', $this->generated_title( [
			'delay_amount'  => 5,
			'delay_type'    => 'days',
			'delay_preview' => 'Custom preview',
		] ) );
	}

	public function test_title_run_when_variants() {
		update_option( 'time_format', 'g:i a' );

		$this->assertEquals( 'Run at <b>9:30 am</b>', $this->generated_title( [
			'delay_type' => 'none',
			'run_when'   => 'later',
			'run_time'   => '09:30:00',
		] ) );

		$this->assertEquals( 'Wait at least <b>2 weeks</b> and then run between <b>9:00 am</b> and <b>5:00 pm</b>', $this->generated_title( [
			'delay_amount' => 2,
			'delay_type'   => 'weeks',
			'run_when'     => 'between',
			'run_time'     => '09:00:00',
			'run_time_to'  => '17:00:00',
		] ) );
	}

	public function test_title_run_on_variants() {
		$this->assertEquals( 'Wait at least <b>1 day</b> and then run on <b>a weekday</b> at any time', $this->generated_title( [
			'delay_amount' => 1,
			'delay_type'   => 'days',
			'run_on_type'  => 'weekday',
		] ) );

		$this->assertEquals( 'Run on any <b>Monday</b> or <b>Friday</b> of <b>any month</b> at any time', $this->generated_title( [
			'delay_type'  => 'none',
			'run_on_type' => 'day_of_week',
			'run_on_dow'  => [ 'monday', 'friday' ],
		] ) );

		$this->assertEquals( 'Run on the first <b>Tuesday</b> of <b>March</b> at any time', $this->generated_title( [
			'delay_type'        => 'none',
			'run_on_type'       => 'day_of_week',
			'run_on_dow_type'   => 'first',
			'run_on_dow'        => [ 'tuesday' ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'march' ],
		] ) );

		$this->assertEquals( 'Run on the <b>1st</b> or <b>15th</b> of <b>June</b> or <b>July</b> at any time', $this->generated_title( [
			'delay_type'        => 'none',
			'run_on_type'       => 'day_of_month',
			'run_on_dom'        => [ 1, 15 ],
			'run_on_month_type' => 'specific',
			'run_on_months'     => [ 'june', 'july' ],
		] ) );

		$this->assertEquals( 'Run on the <b>last day</b> of <b>any month</b> at any time', Delay_Timer::delay_preview( [
			'delay_type'        => 'none',
			'run_when'          => 'now',
			'run_on_type'       => 'day_of_month',
			'run_on_dom'        => [ 'last' ],
			'run_on_month_type' => 'any',
		] ) );
	}

	/**
	 * Saving the step (what the editor does) stores the generated title
	 */
	public function test_after_save_stores_title_from_settings() {
		$timer = $this->add_timer( [
			'delay_amount' => 5,
			'delay_type'   => 'days',
		] );

		$timer->get_step_element()->after_save( $timer );

		$this->assertEquals( 'Wait at least <b>5 days</b> and then run at any time', ( new \Groundhogg\Step( $timer->get_id() ) )->get_title_formatted() );
	}

	/**
	 * Delay timers built by groundhogg/create-flow get a title matching their settings
	 */
	public function test_create_flow_ability_title() {

		\Groundhogg\get_db( 'tags' )->add( [ 'tag_name' => 'delay-title-test' ] );

		// Invoke the ability's callback directly, without the constructor registering it again
		$create_flow = ( new ReflectionClass( \Groundhogg\Abilities\Funnels\Create_Flow::class ) )->newInstanceWithoutConstructor();

		$result = $create_flow( [
			'title' => 'Delay title test',
			'steps' => [
				[ 'type' => 'tag_applied', 'settings' => [ 'tags' => [ 'delay-title-test' ] ] ],
				[ 'type' => 'delay_timer', 'settings' => [ 'delay_amount' => 5, 'delay_type' => 'days' ] ],
			],
		] );

		$this->assertNotWPError( $result );

		$timer = new \Groundhogg\Step( $result['steps'][1]['id'] );

		$this->assertEquals( 'Wait at least <b>5 days</b> and then run at any time', $timer->get_step_element()->get_title( $timer ) );
	}

}
