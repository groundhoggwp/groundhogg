<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Broadcast;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_map_to_class;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\is_sms_plugin_active;

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

		$query = [
			'status'  => 'sent',
			'after'   => $this->start,
			'before'  => $this->end,
			'orderby' => 'send_time',
			'order'   => 'desc',
		];

		$campaign_id = $this->get_campaign_id();
		if ( $campaign_id ){
			$query['related'] = [ 'ID' => $campaign_id, 'type' => 'campaign' ];
		}

		// Get list of funnels and plot it conversion rate
		// Only include active funnels
		$broadcasts = get_db( 'broadcasts' )->query( $query );

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

			$title = html()->e( 'a', [
				'href' => admin_page_url( 'gh_reporting', [
					'tab'       => 'broadcasts',
					'broadcast' => $broadcast->ID,
				] )
			], $broadcast->get_title() );

			if ( is_sms_plugin_active() ){
				$title = html()->e( 'span', [ 'class' => 'broadcast-type' ], $broadcast->is_sms() ? 'SMS' : 'EMAIL' ) . $title;
			}

			$data[] = [

				// Title
				'title' => $title,

				// Sent
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

				// Opened
				'opened'    => $broadcast->is_sms() ? 'N/A' : html()->e( 'a', [
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

				// Open rate
				'open_rate' => $broadcast->is_sms() ? 'N/A' : $stats['open_rate'] . '%',

				// Clicked
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

				// Click Thru Rate
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
