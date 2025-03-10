<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Step;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_map_to_method;
use function Groundhogg\contact_filters_link;
use function Groundhogg\get_object_ids;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Chart_Funnel_Breakdown extends Base_Report {

	/**
	 * @param Step[] $steps
	 *
	 * @return array
	 */
	function group_adjacent_or_same_parent( array $steps ): array {
		// Sort objects by get_order()
		usort( $steps, fn( $a, $b ) => $a->get_order() <=> $b->get_order() );

		$groups        = [];
		$current_group = [];

		foreach ( $steps as $step ) {

			if ( empty( $current_group ) ) {
				$current_group[] = $step;
				continue;
			}

			/**
			 * @var $prev_step Step
			 */

			$prev_step = end( $current_group );

			// Check if adjacent or have the same parent
			if ( $step->is_adjacent_sibling( $prev_step ) ||
			     ( $step->get_parent_step() !== false && $step->is_same_parent( $prev_step ) ) ) {
				$current_group[] = $step;
			} else {
				$groups[]      = $current_group;
				$current_group = [ $step ];
			}
		}

		if ( ! empty( $current_group ) ) {
			$groups[] = $current_group;
		}

		return $groups;
	}

	public function get_data() {

		$funnel = $this->get_funnel();

		// only get benchmarks
		$benchmarks = $this->get_funnel()->get_steps( [
			'step_group' => Step::BENCHMARK
		] );

		$groups = $this->group_adjacent_or_same_parent( $benchmarks );

		$report         = [];
		$max            = 0;
		$prev_completed = 0;

		foreach ( $groups as $i => $group ) {

			$step_ids = get_object_ids( $group );

			$query = new Table_Query( 'events' );

			$query->where( 'funnel_id', $funnel->get_id() )
			      ->in( 'step_id', $step_ids )
			      ->equals( 'status', Event::COMPLETE )
			      ->equals( 'event_type', Event::FUNNEL )
			      ->greaterThanEqualTo( 'time', $this->start )
			      ->lessThanEqualTo( 'time', $this->end );

			$count_completed = $query->count();
			$completed_link  = contact_filters_link( _nf( $count_completed ),
				array_map( function ( Step $step ) {
					return  [
						[
							'type'       => 'funnel_history',
							'funnel_id'  => $step->get_funnel_id(),
							'step_id'    => $step->ID,
							'date_range' => 'between',
							'before'     => $this->endDate->ymd(),
							'after'      => $this->startDate->ymd(),
						]
					];
				}, $group )
			, $count_completed );

			$stage = [
				'labels'    => array_map( function (Step $step) {
					return html()->a( $step->admin_link(), $step->get_title() );
				}, $group ),
				'link'      => $completed_link,
				'completed' => $count_completed
			];

			if ( $i > 0 ) {
				$stage['percentage'] = percentage( $prev_completed, $count_completed, 2 );
			}

			$report[]       = $stage;
			$prev_completed = $count_completed;

			if ( $count_completed > $max ) {
				$max = $count_completed;
			}
		}

		foreach ( $report as &$stage ) {
			$stage['width'] = percentage( $max, $stage['completed'], 2 );
		}

		// Re-index the array after unsetting items
//		$report = array_values( $report );

		return [
			'type'   => 'funnel_breakdown',
			'report' => $report
		];
	}
}
