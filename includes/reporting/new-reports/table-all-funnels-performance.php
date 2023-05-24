<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_map_to_class;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\get_object_ids;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\percentage;
use function Groundhogg\Ymd_His;

class Table_All_Funnels_Performance extends Base_Table_Report {

	public function get_label() {
		return [
			__( 'Funnel', 'groundhogg' ),
			__( 'Active Contacts', 'groundhogg' ),
			__( 'Conversions', 'groundhogg' ),
			__( 'Conversion Rate', 'groundhogg' ),
			__( 'Emails Sent', 'groundhogg' ),
			__( 'Open Rate', 'groundhogg' ),
			__( 'Click Thru Rate', 'groundhogg' ),
		];
	}

	protected function get_table_data() {

		// Get list of funnels and plot it conversion rate
		// Only include active funnels
		$funnels = get_db( 'funnels' )->query( [
			'status' => 'active'
		] );

		array_map_to_class( $funnels, Funnel::class );

		if ( empty( $funnels ) ) {
			return [];
		}

		$data = [];

		foreach ( $funnels as $funnel ) {


			$sent   = $this->count_emails_sent( $funnel );
			$opens  = $this->count_email_opens( $funnel );
			$clicks = $this->count_email_clicks( $funnel );

			$conversion_ids = $funnel->get_conversion_step_ids();
			$conversions    = $this->count_conversions( $funnel );
			$active         = $this->count_active_contacts( $funnel );

			$data[] = [
				'active' => $active,
				'title'  => html()->e( 'a', [
					'href' => admin_page_url( 'gh_reporting', [
						'tab'    => 'funnels',
						'funnel' => $funnel->ID,
					] )
				], $funnel->title ),

				'contacts'    => html()->e( 'a', [
					'href'   => admin_page_url( 'gh_contacts', [
						'filters' => base64_json_encode( [
							[
								[
									'type'       => 'funnel_history',
									'funnel_id'  => $funnel->ID,
									'status'     => 'complete',
									'date_range' => 'between',
									'before'     => Ymd_His( $this->end ),
									'after'      => Ymd_His( $this->start )
								]
							]
						] )
					] ),
					'target' => '_blank'
				], $active, false ),
				'conversions' => ! empty( $conversion_ids ) ? html()->e( 'a', [
					'href'   => admin_page_url( 'gh_contacts', [
						'filters' => base64_json_encode( array_values( array_map( function ( $step_id ) use ( $funnel ) {
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
						}, $conversion_ids ) ) )
					] ),
					'target' => '_blank'
				], $conversions, false ) : 'N/A',
				'cvr'         => ! empty( $conversion_ids ) ? percentage( $active, $conversions ) . '%' : 'N/A',
				'sent'        => $sent,
				'open'        => percentage( $sent, $opens ) . '%',
				'ctr'         => percentage( $opens, $clicks ) . '%',
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
	 * Count the number of  contacts that entered the funnel at one of the entry steps
	 *
	 * @param $funnel Funnel
	 *
	 * @return int
	 */
	protected function count_emails_sent( $funnel ) {

		$email_steps = get_object_ids( $funnel->get_steps( [ 'step_type' => 'send_email' ] ) );

		if ( empty( $email_steps ) ) {
			return 0;
		}

		$where_events = [
			'relationship' => "AND",
			[ 'col' => 'funnel_id', 'val' => $funnel->get_id(), 'compare' => '=' ],
			[ 'col' => 'event_type', 'val' => Event::FUNNEL, 'compare' => '=' ],
			[ 'col' => 'step_id', 'val' => $email_steps, 'compare' => 'IN' ],
			[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
			[ 'col' => 'time', 'val' => $this->start, 'compare' => '>=' ],
			[ 'col' => 'time', 'val' => $this->end, 'compare' => '<=' ],
		];

		return get_db( 'events' )->count( [
			'where'  => $where_events,
			'select' => 'contact_id'
		] );
	}

	/**
	 * Count the number of  contacts that entered the funnel at one of the entry steps
	 *
	 * @param $funnel Funnel
	 *
	 * @return int
	 */
	protected function count_email_opens( $funnel ) {

		$where = [
			'relationship' => "AND",
			[ 'col' => 'funnel_id', 'val' => $funnel->get_id(), 'compare' => '=' ],
			[ 'col' => 'activity_type', 'val' => Activity::EMAIL_OPENED, 'compare' => '=' ],
			[ 'col' => 'timestamp', 'val' => $this->start, 'compare' => '>=' ],
			[ 'col' => 'timestamp', 'val' => $this->end, 'compare' => '<=' ],
		];

		return get_db( 'activity' )->count( [
			'where'  => $where,
			'select' => 'contact_id'
		] );
	}

	/**
	 * Count the number of  contacts that entered the funnel at one of the entry steps
	 *
	 * @param $funnel Funnel
	 *
	 * @return int
	 */
	protected function count_email_clicks( $funnel ) {

		$where = [
			'relationship' => "AND",
			[ 'col' => 'funnel_id', 'val' => $funnel->get_id(), 'compare' => '=' ],
			[ 'col' => 'activity_type', 'val' => Activity::EMAIL_CLICKED, 'compare' => '=' ],
			[ 'col' => 'timestamp', 'val' => $this->start, 'compare' => '>=' ],
			[ 'col' => 'timestamp', 'val' => $this->end, 'compare' => '<=' ],
		];

		return get_db( 'activity' )->count( [
			'where'  => $where,
			'select' => 'contact_id'
		] );
	}

	/**
	 * Count the number of  contacts that entered the funnel at one of the entry steps
	 *
	 * @param $funnel Funnel
	 *
	 * @return int
	 */
	protected function count_active_contacts( $funnel ) {

		$where_events = [
			'relationship' => "AND",
			[ 'col' => 'funnel_id', 'val' => $funnel->get_id(), 'compare' => '=' ],
			[ 'col' => 'event_type', 'val' => Event::FUNNEL, 'compare' => '=' ],
			[ 'col' => 'step_id', 'val' => $funnel->get_entry_step_ids(), 'compare' => 'IN' ],
			[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
			[ 'col' => 'time', 'val' => $this->start, 'compare' => '>=' ],
			[ 'col' => 'time', 'val' => $this->end, 'compare' => '<=' ],
		];

		return get_db( 'events' )->count( [
			'where'  => $where_events,
			'select' => 'DISTINCT contact_id'
		] );
	}

	/**
	 * Count the number of conversions
	 *
	 * @param $funnel Funnel
	 *
	 * @return int
	 */
	protected function count_conversions( $funnel ) {

		$ids = $funnel->get_conversion_step_ids();

		if ( empty( $ids ) ) {
			return 0;
		}

		$where_events = [
			'relationship' => "AND",
			[ 'col' => 'funnel_id', 'val' => $funnel->get_id(), 'compare' => '=' ],
			[ 'col' => 'event_type', 'val' => Event::FUNNEL, 'compare' => '=' ],
			[ 'col' => 'step_id', 'val' => $ids, 'compare' => 'IN' ],
			[ 'col' => 'status', 'val' => 'complete', 'compare' => '=' ],
			[ 'col' => 'time', 'val' => $this->start, 'compare' => '>=' ],
			[ 'col' => 'time', 'val' => $this->end, 'compare' => '<=' ],
		];

		return get_db( 'events' )->count( [
			'where'  => $where_events,
			'select' => 'DISTINCT contact_id'
		] );
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
