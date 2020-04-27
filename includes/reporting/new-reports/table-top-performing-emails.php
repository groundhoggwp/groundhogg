<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\Email;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Top_Performing_Emails extends Base_Table_Report {

	public function get_label() {
		return [
			__( 'Emails', 'groundhogg' ),
			__( 'Sent', 'groundhogg' ),
			__( 'Open Rate', 'groundhogg' ),
			__( 'Click Thru Rate', 'groundhogg' )
		];
	}

	protected function get_funnel_id() {
		return get_request_var( 'data' )['funnel_id'];
	}

	protected function get_table_data() {

		$funnel_id = absint( $this->get_funnel_id() );

		if ( $funnel_id ) {

			$steps = get_db( 'steps' )->query( [
				'funnel_id' => $funnel_id,
				'step_type' => 'send_email'
			] );

			if ( empty( $steps ) ) {
				return [];
			}

			$email_ids = [];

			foreach ( $steps as $step ) {
				$email_ids[] = absint( get_db( 'stepmeta' )->get_meta( $step->ID, 'email_id', true ) );
			}

			$emails = get_db( 'emails' )->query( [
				'status' => 'ready',
				'ID'     => $email_ids
			] );


		} else {
			$emails = get_db( 'emails' )->query( [
				'status' => 'ready'
			] );
		}

		$list = [];

		foreach ( $emails as $email ) {

			$email  = new Email( $email->ID );
			$report = $email->get_email_stats( $this->start, $this->end );

			$title = $email->get_title();
			if ( $report[ 'sent' ] > 10 && $report[ 'opened' ] > 0 && $report[ 'clicked' ] > 0 ) {
				$list[] = [
					'label'   => $title,
					'url'     => admin_url( sprintf( 'admin.php?page=gh_emails&action=edit&email=%s', $email->ID ) ),
					'sent'    => $report['sent'],
					'data'    => percentage( $report['sent'], $report['opened'] ),
					'clicked' => percentage( $report['opened'], $report['clicked'] )
				];
			}

		}

		$list = $this->normalize_data( $list );

		foreach ( $list as $i => $datum ) {

			$datum['label']   = html()->wrap( $datum['label'], 'a', [
				'href'  => $datum['url'],
				'class' => 'number-total',
				'title' => $datum['label'],
			] );
			$datum['data']    = $datum['data'] . '%';
			$datum['clicked'] = $datum['clicked'] . '%';

			unset( $datum['url'] );
			$data[ $i ] = $datum;
		}

		return $data;

	}

	/**
	 * Sort by multiple args
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return mixed
	 */
	public function sort( $a, $b ) {
		if ( $a['sent'] === $b['sent'] ) {

			if ( $a['data'] === $b['data'] ) {
				return $b['clicked'] - $a['clicked'];
			}

			return $b['data'] - $a['data'];
		}

		return $b['sent'] - $a['sent'];
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

		return [
			'label'   => $item_data['label'],
			'url'     => $item_data['url'],
			'sent'    => $item_data['sent'],
			'data'    => $item_data['data'],
			'clicked' => $item_data['clicked']

		];
	}

}