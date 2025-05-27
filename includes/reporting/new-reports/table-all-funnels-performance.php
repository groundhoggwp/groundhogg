<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Reporting\New_Reports\Traits\Funnel_Conversion_Stats;
use function Groundhogg\_nf;
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

class Table_All_Funnels_Performance extends Base_Table_Report {

	use Funnel_Conversion_Stats;

	protected $per_page = 20;
	protected $orderby = 2;

	public function get_label() {
		return [
			__( 'Flow', 'groundhogg' ),
			__( 'Added', 'groundhogg' ),
			__( 'Active', 'groundhogg' ),
			__( 'Conversions', 'groundhogg' ),
			__( 'Emails Sent', 'groundhogg' ),
			__( 'Opens', 'groundhogg' ),
			__( 'Clicks', 'groundhogg' ),
			__( 'Unsubs', 'groundhogg' ),
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

			$has_email_steps = count( $funnel->get_email_steps() ) > 0;

			$sent         = 0;
			$opens        = 0;
			$clicks       = 0;
			$unsubscribed = 0;

			if ( $has_email_steps ) {
				// No sense in query if there are no email steps...
				$sent = $this->count_emails_sent( $funnel );

				$activityQuery = new Table_Query( 'activity' );
				$activityQuery->setSelect( 'activity_type', [ 'COUNT(ID)', 'total' ] )
				              ->setGroupby( 'activity_type' )
				              ->where()
				              ->equals( 'funnel_id', $funnel->get_id() )
				              ->notEquals( 'email_id', 0 )
							  ->lessThanEqualTo( 'timestamp', $this->end )
				              ->greaterThanEqualTo( 'timestamp', $this->start );

				$results = $activityQuery->get_results();

				$opens        = get_array_var( find_object( $results, [ 'activity_type' => Activity::EMAIL_OPENED ] ), 'total', 0 );
				$clicks       = get_array_var( find_object( $results, [ 'activity_type' => Activity::EMAIL_CLICKED ] ), 'total', 0 );
				$unsubscribed = get_array_var( find_object( $results, [ 'activity_type' => Activity::UNSUBSCRIBED ] ), 'total', 0 );
			}

			$conversion_ids = $funnel->get_conversion_step_ids();
			$conversions    = $this->count_conversions( $funnel );
			$active         = $this->count_active_contacts( $funnel );
			$added          = $this->count_added_contacts( $funnel );

			$this->swap_range_with_compare_dates();

			$conversions_comp = percentage_change( $this->count_conversions( $funnel ), $conversions );
			$active_comp      = percentage_change( $this->count_active_contacts( $funnel ), $active );
			$added_comp       = percentage_change( $this->count_added_contacts( $funnel ), $added );

			$this->swap_range_with_compare_dates();

			$data[] = [
				'active'       => $active,
				'title'        => report_link( $funnel->title, [
					'tab'    => 'funnels',
					'funnel' => $funnel->ID
				] ),
				'added'        => contact_filters_link( _nf( $added ) . ' ' . html()->percentage_change( $added_comp ), array_map( function ( $step_id ) use ( $funnel ) {
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
				'contacts'     => contact_filters_link( _nf( $active ) . ' ' . html()->percentage_change( $active_comp ), [
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
				'conversions'  => empty( $conversion_ids ) ? 'N/A' : contact_filters_link( format_number_with_percentage( $conversions, $active ) . ' ' . html()->percentage_change( $conversions_comp ), array_map( function ( $step_id ) use ( $funnel ) {
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
				'sent'         => ! $has_email_steps ? 'N/A' : contact_filters_link( _nf( $sent ), [
					[
						[
							'type'          => 'email_received',
							'funnel_id'     => $funnel->ID,
							'date_range'    => 'between',
							'before'        => $this->endDate->ymd(),
							'after'         => $this->startDate->ymd(),
							'count'         => 1,
							'count_compare' => 'greater_than_or_equal_to',
						]
					]
				], $sent ),
				'opened'       => ! $has_email_steps ? 'N/A' : contact_filters_link( format_number_with_percentage( $opens, $sent ), [
					[
						[
							'type'          => 'email_opened',
							'funnel_id'     => $funnel->ID,
							'date_range'    => 'between',
							'before'        => $this->endDate->ymd(),
							'after'         => $this->startDate->ymd(),
							'count'         => 1,
							'count_compare' => 'greater_than_or_equal_to',
						]
					]
				], $opens ),
				'clicked'      => ! $has_email_steps ? 'N/A' : contact_filters_link( format_number_with_percentage( $clicks, $opens ), [
					[
						[
							'type'          => 'email_link_clicked',
							'funnel_id'     => $funnel->ID,
							'date_range'    => 'between',
							'before'        => $this->endDate->ymd(),
							'after'         => $this->startDate->ymd(),
							'count'         => 1,
							'count_compare' => 'greater_than_or_equal_to',
						]
					]
				], $clicks ),
				'unsubscribes' => ! $has_email_steps ? 'N/A' : contact_filters_link( format_number_with_percentage( $unsubscribed, $sent ), [
					[
						[
							'type'       => 'unsubscribed',
							'funnel_id'  => $funnel->ID,
							'date_range' => 'between',
							'before'     => $this->endDate->ymd(),
							'after'      => $this->startDate->ymd()
						]
					]
				], $unsubscribed ),
				'orderby'      => [
					// The value to compare, and the secondary column to compare
					$funnel->ID,
					$added, // added
					$active, // active,
					$conversions, // conversions
					$sent,
					$opens,
					$clicks,
					$unsubscribed,
				],
				'cellClasses'  => [
					// One of Good/Fair/Poor
					'', // Funnel
					'', // Added
					'', // Active
					empty( $conversion_ids ) || ! $active ? '' : is_good_fair_or_poor( percentage( $active, $conversions ), 40, 30, 20, 10 ), // Conversions
					'', // Sent
					! $has_email_steps || ! $sent ? '' : is_good_fair_or_poor( percentage( $sent, $opens ), 40, 30, 20, 10 ), // Sent
					! $has_email_steps || ! $sent ? '' : is_good_fair_or_poor( percentage( $opens, $clicks ), 30, 20, 10, 5 ), // clicks
					! $has_email_steps || ! $sent ? '' : is_good_fair_or_poor( 100 - percentage( $sent, $unsubscribed ), 99, 98, 97, 95 ), // unsubscribed
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

	/**
	 * Count the number of emails sent by this funnel
	 *
	 * @param $funnel Funnel
	 *
	 * @return int
	 */
	protected function count_emails_sent( $funnel ) {

		$eventQuery = new Table_Query( 'events' );
		$eventQuery->where()->equals( 'funnel_id', $funnel->ID )
		           ->equals( 'event_type', Event::FUNNEL )
		           ->equals( 'status', Event::COMPLETE )
		           ->notEquals( 'email_id', 0 )
		           ->greaterThanEqualTo( 'time', $this->start )
		           ->lessThanEqualTo( 'time', $this->end );

		return $eventQuery->count();
	}

	/**
	 * Count the number of  contacts that entered the funnel at one of the entry steps
	 *
	 * @param $funnel Funnel
	 *
	 * @return int
	 */
	protected function count_added_contacts( $funnel ) {

		$entry_steps = $funnel->get_entry_step_ids();

		if ( empty( $entry_steps ) ) {
			return 0;
		}

		$eventQuery = new Table_Query( 'events' );
		$eventQuery->setSelect( 'COUNT(DISTINCT(contact_id))' )
		           ->where()
		           ->lessThanEqualTo( 'time', $this->end )
		           ->greaterThanEqualTo( 'time', $this->start )
		           ->equals( 'status', Event::COMPLETE )
		           ->equals( 'event_type', Event::FUNNEL )
		           ->equals( 'funnel_id', $funnel->ID )
		           ->in( 'step_id', $entry_steps );

		return $eventQuery->get_var();
	}

	/**
	 * Count the number of  contacts that entered the funnel at one of the entry steps
	 *
	 * @param $funnel Funnel
	 *
	 * @return int
	 */
	protected function count_active_contacts( $funnel ) {

		$eventQuery = new Table_Query( 'events' );
		$eventQuery->setSelect( 'COUNT(DISTINCT(contact_id))' )
		           ->where()
		           ->lessThanEqualTo( 'time', $this->end )
		           ->greaterThanEqualTo( 'time', $this->start )
		           ->equals( 'status', Event::COMPLETE )
		           ->equals( 'event_type', Event::FUNNEL )
		           ->equals( 'funnel_id', $funnel->ID );

		return $eventQuery->get_var();
	}

	/**
	 * Count the number of conversions
	 *
	 * @param $funnel Funnel
	 *
	 * @return int
	 */
	protected function count_conversions( $funnel ) {
		return $this->get_funnel_conversions( $funnel, $this->start, $this->end );
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
		return $item_data;
	}


}
