<?php

namespace Groundhogg\steps\logic;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Queue\Event_Queue;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\_nf;
use function Groundhogg\get_object_ids;
use function Groundhogg\html;
use function Groundhogg\one_of;

class Split_Test extends Split_Path {

	public function get_name() {
		return 'Split (A/B) Test';
	}

	public function get_type() {
		return 'split_test';
	}

	public function get_description() {
		// TODO: Implement get_description() method.
	}

	public function get_sub_group() {
		return 'logic';
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/split-test.svg';
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

		$start_date = $this->get_setting( 'start_date' );
		$start_time = ( new DateTimeHelper( $start_date ) )->getTimestamp();

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

		$start_date = $this->get_setting( 'start_date' );
		$start_time = ( new DateTimeHelper( $start_date ) )->getTimestamp();

		$first_step = $this->get_first_of_branch( $branch );

		if ( ! $first_step ) {

			// this is slower
			$contactCountQuery = new Table_Query( 'events' );
			$contactCountQuery->setGroupby( 'contact_id' )->set_query_params( [
				'after'      => $start_time,
				'funnel_id'  => $funnel->get_id(),
				'status'     => Event::COMPLETE,
				'event_type' => Event::FUNNEL,
			] )->where()
			                  ->contains( 'args', $branch ); // this will be hella slow, but reasonably safe;

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

		$report   = [];

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


	protected function before_settings( \Groundhogg\Step $step ) {

		$report = $this->get_split_test_report();

		wp_enqueue_style( 'groundhogg-admin-reporting' );

		?>
        <div class="display-flex gap-20 align-center">

            <div class="gh-panel full-width">
                <div class="gh-panel-header">
                    <h2>Results</h2>
                </div>
                <table class="groundhogg-report-table">
                    <thead>
                        <th>Branch</th>
                        <th>Contacts</th>
                        <th>Results</th>
                    </thead>
                    <tbody>
                    <?php foreach ( $report as $branch => $cells ): ?>
                        <tr>
                            <td><?php _e( $this->get_branch_name( $branch ) ); ?></td>
                            <td><?php _e( _nf( $cells['contacts'] ) ); ?></td>
                            <td><?php _e( _nf( $cells['outcomes'] ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
		<?php

	}

	public function settings( $step ) {

		echo html()->e( 'p', [], __( 'Describe your test...' ) );

		echo html()->input( [
			'placeholder' => 'A/B Test',
			'value'       => $this->get_setting( 'test_name' ),
			'name'        => $this->setting_name_prefix( 'test_name' ),
		] );

		echo html()->e( 'p', [], __( 'Contacts will be randomly sent down either branch <span class="pill purple">A</span> or branch <span class="pill purple">B</span>.' ) );
		echo html()->e( 'p', [], __( 'How should traffic be split between <span class="pill purple">A</span> / <span class="pill purple">B</span>?' ) );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->dropdown( [
				'selected' => absint( $this->get_setting( 'weight' ) ),
				'name'     => $this->setting_name_prefix( 'weight' ),
				'class'    => 'small',
				'options'  => [
					10 => '10% / 90%',
					20 => '20% / 80%',
					30 => '30% / 70%',
					40 => '40% / 60%',
					50 => '50% / 50%',
					60 => '60% / 40%',
					70 => '70% / 30%',
					80 => '80% / 20%',
					90 => '90% / 10%',
				],
				'readonly' => (bool) $this->get_setting( 'start_date' )
			] ),
		] );

		echo html()->e( 'p', [], __( 'Define the win condition.' ) );

		echo html()->dropdown( [
			'placeholder' => 'Path A weight',
			'selected'    => $this->get_setting( 'win_condition' ),
			'name'        => $this->setting_name_prefix( 'win_condition' ),
			'options'     => [
				'conversions' => 'Funnel conversions',
				'clicks'      => 'Email link clicks',
			],
			'readonly'    => (bool) $this->get_setting( 'start_date' )
		] );

		echo html()->e( 'p', [], __( 'When your branches have been completely configured, start the test!' ) );

		echo html()->button( [
			'class' => 'gh-button primary',
			'text'  => 'Start Test!'
		] );

		?><p></p><?php
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
			// when the test was started
			'start_date'    => [
				'default'  => '',
				'sanitize' => function ( $value ) {
					return ( new DateTimeHelper( $value ) )->ymdhis();
				}
			],
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
	 * Step title
	 *
	 * @param $step
	 *
	 * @return false|string
	 */
	public function generate_step_title( $step ) {
		return $this->get_setting( 'test_name' );
	}

	protected function get_branch_name( $branch ) {
		$branch = strtoupper( explode( '-', $branch )[1] );

		$weight = $this->get_setting( 'weight' );

		if ( $branch !== 'A' ) {
			$weight = 100 - $weight;
		}

		return "$branch ($weight%)";
	}

	/**
	 * Cleverly distribute contacts among the branches based on weighted distribution
	 *
	 * @param Contact $contact
	 *
	 * @return false|\Groundhogg\Step
	 */
	public function get_branch_action( Contact $contact ) {

		$path_a_weight = absint( $this->get_setting( 'weight' ) );
		$path_b_weight = 100 - $path_a_weight;

		$random     = rand( 1, 100 ); // Generate a random number between 1 and 100
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

	protected function after_settings( \Groundhogg\Step $step ) {
	}
}
