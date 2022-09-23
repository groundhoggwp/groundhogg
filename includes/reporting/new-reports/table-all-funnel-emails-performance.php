<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Email;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\html;
use function Groundhogg\Ymd_His;

class Table_All_Funnel_Emails_Performance extends Base_Table_Report {

	public function get_label() {
		return [
			__( 'Email', 'groundhogg' ),
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
		$email_steps = $this->get_funnel()->get_steps( [
			'step_type' => 'send_email'
		] );


		if ( empty( $email_steps ) ) {
			return [];
		}

		$data = [];

		foreach ( $email_steps as $email_step ) {

			$email = new Email( $email_step->get_meta( 'email_id' ) );

			if ( ! $email->exists() ) {
				continue;
			}

			$stats = $email->get_email_stats( $this->start, $this->end, $email_step->get_id() );

			$data[] = [
				'title' => html()->e( 'a', [
					'href' => admin_page_url( 'gh_reporting', [
						'tab'  => 'funnels',
						'step' => $email_step->get_id(),
					] )
				], $email->get_title() ),

				'sent'      => html()->e( 'a', [
					'href'   => admin_page_url( 'gh_contacts', [
						'filters' => base64_json_encode( [
							[
								[
									'type'       => 'funnel_history',
									'funnel_id'  => $email_step->get_funnel_id(),
									'step_id'    => $email_step->get_id(),
									'status'     => 'complete',
									'date_range' => 'between',
									'before'     => Ymd_His( $this->end ),
									'after'      => Ymd_His( $this->start )
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
									'type'       => 'email_opened',
									'email_id'   => $email->ID,
									'date_range' => 'between',
									'before'     => Ymd_His( $this->end ),
									'after'      => Ymd_His( $this->start )
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
									'type'       => 'email_link_clicked',
									'email_id'   => $email->ID,
									'date_range' => 'between',
									'before'     => Ymd_His( $this->end ),
									'after'      => Ymd_His( $this->start )
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
