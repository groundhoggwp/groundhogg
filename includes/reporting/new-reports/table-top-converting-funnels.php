<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Reporting\New_Reports\Traits\Funnel_Conversion_Stats;
use function Groundhogg\contact_filters_link;
use function Groundhogg\format_number_with_percentage;
use function Groundhogg\is_good_fair_or_poor;
use function Groundhogg\percentage;
use function Groundhogg\report_link;
use function Groundhogg\Ymd_His;

class Table_Top_Converting_Funnels extends Base_Table_Report {

	use Funnel_Conversion_Stats;

	protected $orderby = 1;

	public function get_label() {
		return [
			__( 'Funnel', 'groundhogg' ),
			__( 'Conversions', 'groundhogg' )
		];
	}

	protected function get_table_data() {

		// Get list of funnels and plot it conversion rate
		// Only include active funnels

		$query = new Table_Query( 'events' );
		$query->setSelect( [ 'COUNT(DISTINCT(contact_id))', 'active_contacts' ], 'funnel_id' )
		      ->setGroupby( 'funnel_id' )
		      ->where()
		      ->equals( 'event_type', Event::FUNNEL )
		      ->equals( 'status', Event::COMPLETE )
		      ->greaterThanEqualTo( 'time', $this->start )
		      ->lessThanEqualTo( 'time', $this->end );

		$results = $query->get_results();

		$data = [];

		foreach ( $results as $result ) {

			$funnel      = new Funnel( $result->funnel_id );
			$active      = absint( $result->active_contacts );
			$conversions = $this->get_funnel_conversions( $funnel, $this->start, $this->end );

			$data[] = [

				'funnel' => report_link( $funnel->title, [
					'tab'    => 'funnels',
					'funnel' => $funnel->ID
				] ),

				'conversions' => contact_filters_link( format_number_with_percentage( $conversions, $active ), array_map( function ( $step_id ) use ( $funnel ) {
					return [
						[
							'type'       => 'funnel_history',
							'funnel_id'  => $funnel->ID,
							'step_id'    => $step_id,
							'status'     => 'complete',
							'date_range' => 'between',
							'before'     => Ymd_His( $this->end ),
							'after'      => Ymd_His( $this->start )
						]
					];
				}, $funnel->get_conversion_step_ids() ), $conversions ),
				'orderby'     => [
					$funnel->ID,
					$conversions
				],
				'cellClasses' => [
					'',
					is_good_fair_or_poor( percentage( $active, $conversions ), 40, 30, 20, 10 )
				]
			];
		}

		return $data;

	}


	/**
	 * Normalize a datum
	 *
	 * @param $item_key
	 * @param $item_data
	 *
	 * @return array
	 */
	protected function normalize_datum( $item_key, $item_data ) {
	}

}
