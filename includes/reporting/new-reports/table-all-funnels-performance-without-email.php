<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use function Groundhogg\_nf;
use function Groundhogg\array_find;
use function Groundhogg\array_map_to_class;
use function Groundhogg\contact_filters_link;
use function Groundhogg\find_object;
use function Groundhogg\format_number_with_percentage;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\is_good_fair_or_poor;
use function Groundhogg\percentage;
use function Groundhogg\percentage_change;
use function Groundhogg\report_link;
use function Groundhogg\Ymd_His;

class Table_All_Funnels_Performance_Without_Email extends Table_All_Funnels_Performance {

	protected $per_page = 20;
	protected $orderby = 2;

	public function get_label() {
		return [
			__( 'Flow', 'groundhogg' ),
			__( 'Added', 'groundhogg' ),
			__( 'Active', 'groundhogg' ),
			__( 'Conversions', 'groundhogg' ),
		];
	}

	protected function get_table_data() {

		$query = [
			'status' => 'active',
		];

		$campaign_id = $this->get_campaign_id();

		if ( $campaign_id ) {
			$query['related'] = [ 'ID' => $campaign_id, 'type' => 'campaign' ];
		}

		// Get list of funnels and plot it conversion rate
		// Only include active funnels
		$funnels = get_db( 'funnels' )->query( $query );

		array_map_to_class( $funnels, Funnel::class );

		if ( empty( $funnels ) ) {
			return [];
		}

		$data = [];

		foreach ( $funnels as $funnel ) {

			$conversion_ids = $funnel->get_conversion_step_ids();
			$conversions    = $this->count_conversions( $funnel );
			$active         = $this->count_active_contacts( $funnel );
			$added          = $this->count_added_contacts( $funnel );

			// Swap the dates so we don't have to write more code
			$this->swap_range_with_compare_dates();

			$conversions_comp = percentage_change( $this->count_conversions( $funnel ), $conversions );
			$active_comp      = percentage_change( $this->count_active_contacts( $funnel ), $active );
			$added_comp       = percentage_change( $this->count_added_contacts( $funnel ), $added );

			// Swap em back
			$this->swap_range_with_compare_dates();

			$data[] = [
				'active'      => $active,
				'title'       => report_link( $funnel->title, [
					'tab'    => 'funnels',
					'funnel' => $funnel->ID,
					// Include dates so that the report will load correctly when linked from an email
					'start'  => $this->startDate->ymd(),
					'end'    => $this->endDate->ymd()
				] ),
				'added'       => contact_filters_link( _nf( $added ) . ' ' . html()->percentage_change( $added_comp ), array_map( function ( $step_id ) use ( $funnel ) {
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
				}, $funnel->get_entry_step_ids() ), $added ),
				'contacts'    => contact_filters_link( _nf( $active ) . ' ' . html()->percentage_change( $active_comp ), [
					[
						[
							'type'       => 'funnel_history',
							'funnel_id'  => $funnel->ID,
							'date_range' => 'between',
							'before'     => $this->endDate->ymd(),
							'after'      => $this->startDate->ymd()
						]
					]
				], $active ),
				'conversions' => empty( $conversion_ids ) ? 'N/A' : contact_filters_link( format_number_with_percentage( $conversions, $active ) . ' ' . html()->percentage_change( $conversions_comp ), array_map( function ( $step_id ) use ( $funnel ) {
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
				}, $conversion_ids ), $conversions ),
				'orderby'     => [
					// The value to compare, and the secondary column to compare
					$funnel->ID,
					$added, // added
					$active, // active,
					$conversions, // conversions
				],
				'cellClasses' => [
					// One of Good/Fair/Poor
					'', // Funnel
					'', // Added
					'', // Active
					empty( $conversion_ids ) || ! $active ? '' : is_good_fair_or_poor( percentage( $active, $conversions ), 40, 30, 20, 10 ), // Conversions
				]
			];
		}

		usort( $data, function ( $a, $b ) {
			return absint( $b['active'] ) - absint( $a['active'] );
		} );

		foreach ( $data as &$datum ) {
			unset( $datum['active'] );
		}

		return $data;

	}
}
