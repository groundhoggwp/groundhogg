<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Contact_Query;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use function Groundhogg\contact_filters_link;
use function Groundhogg\format_number_with_percentage;
use function Groundhogg\get_db;
use function Groundhogg\is_good_fair_or_poor;
use function Groundhogg\percentage;
use function Groundhogg\report_link;
use function Groundhogg\Ymd_His;

class Table_Top_Converting_Funnels extends Base_Table_Report {

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

		$conversionStepQuery = new Table_Query( 'steps' );
		$conversionStepQuery->setSelect( 'ID' )->where( 'is_conversion', 1 )->equals( 'step_status', 'active' );

		$conversionQuery = new Table_Query( 'events' );
		$conversionQuery->setSelect( 'funnel_id', [ 'COUNT(DISTINCT(contact_id))', 'conversions' ] )
		                ->setGroupby( 'funnel_id' )
		                ->setOrderby( 'conversions' )
		                ->where( 'event_type', Event::FUNNEL )
		                ->equals( 'status', Event::COMPLETE )
		                ->greaterThanEqualTo( 'time', $this->start )
		                ->lessThanEqualTo( 'time', $this->end )
			// only funnels that have conversion steps
			            ->in( 'step_id', $conversionStepQuery );

//		var_dump( "$conversionQuery" );

		$results = $conversionQuery->get_results();

		$data = [];

		foreach ( $results as $result ) {

			$funnel      = new Funnel( $result->funnel_id );
			$conversions = absint( $result->conversions );

			$active = ( new Table_Query( 'events' ) )
				->setSelect( 'COUNT(DISTINCT(contact_id))' )
				->where( 'funnel_id', $funnel->ID )
				->equals( 'event_type', Event::FUNNEL )
				->equals( 'status', Event::COMPLETE )
				->greaterThanEqualTo( 'time', $this->start )
				->lessThanEqualTo( 'time', $this->end )
				->query->get_var();

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
