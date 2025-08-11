<?php

namespace Groundhogg\Steps\Premium\Logic;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Queue\Event_Queue;
use Groundhogg\Steps\Premium\Trait_Premium_Step;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\get_object_ids;
use function Groundhogg\one_of;

class Split_Test extends Split_Path {

	use Trait_Premium_Step;

	public function get_name() {
		return 'Split (A/B) Test';
	}

	public function get_type() {
		return 'split_test';
	}

	public function get_description() {
		return 'Test how different steps affect conversions and other funnel metrics.';
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/logic/split-test.svg';
	}

	/**
	 * Get the data for the split test
	 *
	 * @throws \Groundhogg\DB\Query\FilterException
	 * @return int
	 */
	public function get_branch_outcomes( $branch ) {

		$current_step = $this->get_current_step();
		$funnel       = $current_step->get_funnel();

		$start_time = ( new DateTimeHelper( $current_step->date_activated ) )->getTimestamp();

		switch ( $this->get_setting( 'win_condition' ) ) {
			default:
			case 'conversions':
				$conversion_steps = $funnel->get_conversion_steps();
				$conversion_steps = array_filter( $conversion_steps, function ( $step ) use ( $current_step ) {
					return $step->is_after( $current_step );
				} );

				if ( empty( $conversion_steps ) ) {
					return 0;
				}

				$conversionQuery = new Table_Query( 'events' );
				$conversionQuery->set_query_params( [
					'after'      => $start_time,
					'step_id'    => get_object_ids( $conversion_steps ),
					'funnel_id'  => $funnel->get_id(),
					'status'     => Event::COMPLETE,
					'event_type' => Event::FUNNEL,
				] )->where()
				                ->contains( 'args', $branch ); // this will be hella slow, but reasonably safe;

				return $conversionQuery->count();
			case 'clicks':

				$branch_steps = $funnel->get_step_ids( [
					'branch' => $branch
				] );

				$clickQuery = new Table_Query( 'activity' );
				$clickQuery->set_query_params( [
					'after'         => $start_time,
					'step_id'       => $branch_steps,
					'funnel_id'     => $funnel->get_id(),
					'activity_type' => Activity::EMAIL_CLICKED,
				] );

				return $clickQuery->count();
		}
	}

	/**
	 * Get the number of contacts that went through the branch
	 *
	 * @param $branch
	 *
	 * @return int
	 */
	public function count_contacts_through_branch( $branch ) {

		$current_step = $this->get_current_step();
		$funnel       = $current_step->get_funnel();

		$start_time = ( new DateTimeHelper( $current_step->date_activated ) )->getTimestamp();

		$first_step = $this->get_first_of_branch( $branch );

		if ( ! $first_step ) {

			// this is slower
			$contactCountQuery = new Table_Query( 'events' );
			$contactCountQuery->setGroupby( 'contact_id' )->set_query_params( [
				'after'      => $start_time,
				'funnel_id'  => $funnel->get_id(),
				'status'     => Event::COMPLETE,
				'event_type' => Event::FUNNEL,
			] )->where()->contains( 'args', $branch ); // this will be hella slow, but reasonably safe;

			return $contactCountQuery->count();

		}

		$contactCountQuery = new Table_Query( 'events' );
		$contactCountQuery->setGroupby( 'contact_id' )->set_query_params( [
			'after'      => $start_time,
			'step_id'    => $first_step->ID,
			'funnel_id'  => $funnel->get_id(),
			'status'     => Event::COMPLETE,
			'event_type' => Event::FUNNEL,
		] );

		return $contactCountQuery->count();

	}

	/**
	 * Get the data for the split test
	 *
	 * @throws \Groundhogg\DB\Query\FilterException
	 * @return array the stats
	 */
	public function get_split_test_report() {

		$branches = $this->get_branches();

		$report = [];

		foreach ( $branches as $branch ) {

			$report[ $branch ] = [
				'contacts' => $this->count_contacts_through_branch( $branch ),
				'outcomes' => $this->get_branch_outcomes( $branch ),
			];

		}

		// sort by outcome
		uasort( $report, function ( $a, $b ) {
			return $b['outcomes'] - $a['outcomes'];
		} );

		return $report;
	}

	public function is_test_active() {
		$winner = $this->get_setting( 'winner' );

		// for the test to be active the step must be active with no winner declared
		return $this->get_current_step()->is_active() && ! $winner;
	}

	public function get_settings_schema() {
		return [
			// weight to send to the A path
			'weight'        => [
				'default'  => 50,
				'sanitize' => function ( $value ) {
					$value = absint( $value );

					return one_of( $value, [ 10, 20, 30, 40, 50, 60, 70, 80, 90 ] );
				}
			],
			// metric to decide a winner
			'win_condition' => [
				'default'  => 'conversions',
				'sanitize' => function ( $value ) {
					return one_of( $value, [ 'conversions', 'clicks' ] );
				}
			],
			// threshold to declare a winner
			'win_threshold' => [],
			// when the test should automatically end
			'end_date'      => [
				'default'  => '',
				'sanitize' => function ( $value ) {
					return ( new DateTimeHelper( $value ) )->ymdhis();
				}
			],
			'test_name'     => [
				'default'  => '',
				'sanitize' => 'sanitize_text_field',
			],
			'winner'        => [
				'sanitize' => function ( $branch ) {
					if ( empty( $branch ) ) {
						return '';
					}

					return one_of( $branch, $this->get_branches() );
				},
				'default'  => ''
			]
		];
	}

	/**
	 * Yes/No only has yes, no branches
	 *
	 * @return string[]
	 */
	public function get_branches() {
		return [
			$this->maybe_prefix_branch( 'a' ),
			$this->maybe_prefix_branch( 'b' ),
		];
	}

	/**
	 * Get the name of a branch
	 *
	 * @param $branch string
	 *
	 * @return string
	 */
	protected function get_branch_name( $branch ) {

		$winner = $this->get_setting( 'winner' );

		if ( $winner ){
			return $branch === $winner ? 'Winner (100%)' : 'Loser (0%)';
		}

		$branch = strtoupper( explode( '-', $branch )[1] );

		$weight = $this->get_setting( 'weight' );

		if ( $branch !== 'A' ) {
			$weight = 100 - $weight;
		}

		return "$branch ($weight%)";
	}

	/**
	 * Show branch as winner
	 *
	 * @param $branch_id
	 *
	 * @return string
	 */
	protected function get_branch_classes( $branch_id ): string {
		$winner = $this->get_setting( 'winner' );

		if ( $winner ){
			return $branch_id === $winner ? 'green' : 'red';
		}

		return '';
	}

	/**
	 * Take the winner into account
	 *
	 * @param string  $branch
	 * @param Contact $contact
	 *
	 * @return bool
	 */
	public function matches_branch_conditions( string $branch, Contact $contact ) {

		$winner = $this->get_setting( 'winner' );

		// can't travel to steps in the losing branch
		if ( $winner && $branch !== $winner ) {
			return false;
		}

		return true;
	}

	/**
	 * Cleverly distribute contacts among the branches based on weighted distribution
	 *
	 * @param Contact $contact
	 *
	 * @return false|\Groundhogg\Step
	 */
	public function get_logic_action( Contact $contact ) {

		$winner = $this->get_setting( 'winner' );

		// only use winning branch
		if ( $winner ){
			return $this->get_first_of_branch( $winner );
		}

		$path_a_weight = absint( $this->get_setting( 'weight' ) );
		$path_b_weight = 100 - $path_a_weight;

		$random     = wp_rand( 1, 100 ); // Generate a random number between 1 and 100
		$cumulative = 0;

		$branches = [
			'a' => $path_a_weight,
			'b' => $path_b_weight
		];

		foreach ( $branches as $branch_key => $weight ) {

			$cumulative += $weight;

			if ( $random <= $cumulative ) {

				if ( Event_Queue::is_processing() ) {
					\Groundhogg\event_queue()->get_current_event()->set_args( [
						$branch_key => 1
					] );
				}

				return $this->get_first_of_branch( $branch_key ); // Return the chosen path
			}
		}

		return false;
	}
}
