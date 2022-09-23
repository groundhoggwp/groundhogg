<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Broadcast;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_map_to_class;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\html;

class Table_All_Broadcasts_Performance extends Base_Table_Report {

	public function get_label() {
		return [
			__( 'Broadcast', 'groundhogg' ),
			__( 'Sent', 'groundhogg' ),
			__( 'Opens', 'groundhogg' ),
			__( 'Open Rate', 'groundhogg' ),
			__( 'Clicks', 'groundhogg' ),
			__( 'Click Thru Rate', 'groundhogg' ),
		];
	}

	protected function get_table_data() {

		// Get list of funnels and plot it conversion rate
		// Only include active funnels
		$broadcasts = get_db( 'broadcasts' )->query( [
			'status'  => 'sent',
			'after'   => $this->start,
			'before'  => $this->end,
			'orderby' => 'send_time',
			'order'   => 'desc',
		] );

		array_map_to_class( $broadcasts, Broadcast::class );

		if ( empty( $broadcasts ) ) {
			return [];
		}

		$data = [];

		/**
		 * @var $broadcasts Broadcast[]
		 */

		foreach ( $broadcasts as $broadcast ) {

			$stats = $broadcast->get_report_data();

			$data[] = [
				'title' => html()->e( 'a', [
					'href' => admin_page_url( 'gh_reporting', [
						'tab'       => 'broadcasts',
						'broadcast' => $broadcast->ID,
					] )
				], $broadcast->get_title() ),

				'sent'      => html()->e( 'a', [
					'href'   => admin_page_url( 'gh_contacts', [
						'filters' => base64_json_encode( [
							[
								[
									'type'         => 'broadcast_received',
									'broadcast_id' => $broadcast->ID,
									'status'       => 'complete',
								]
							]
						] )
					] ),
					'target' => '_blank'
				], $stats['sent'], false ),
				'opened'    => html()->e( 'a', [
					'href'   => admin_page_url( 'gh_contacts', [
						'filters' => base64_json_encode( [
							[
								[
									'type'         => 'broadcast_opened',
									'broadcast_id' => $broadcast->ID,
								]
							]
						] )
					] ),
					'target' => '_blank'
				], $stats['opened'], false ),
				'open_rate' => $stats['open_rate'] . '%',
				'clicked'   => html()->e( 'a', [
					'href'   => admin_page_url( 'gh_contacts', [
						'filters' => base64_json_encode( [
							[
								[
									'type'         => 'broadcast_link_clicked',
									'broadcast_id' => $broadcast->ID,
								]
							]
						] )
					] ),
					'target' => '_blank'
				], $stats['clicked'], false ),
				'ctr'       => $stats['click_through_rate'] . '%',
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
		return $item_data;
	}


}
