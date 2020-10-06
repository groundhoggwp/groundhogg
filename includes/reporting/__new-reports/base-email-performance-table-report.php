<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\percentage;

abstract class Base_Email_Performance_Table_Report extends Base_Table_Report {

	public function get_label() {
		return [
			__( 'Emails', 'groundhogg' ),
			__( 'Sent', 'groundhogg' ),
			__( 'Open Rate', 'groundhogg' ),
			__( 'Click Thru Rate', 'groundhogg' )
		];
	}

	/**
	 * Get the email IDs of emails sent within the time period...
	 */
	protected function get_email_ids_of_sent_emails() {


		global $wpdb;

		$events_table = get_db( 'events' )->get_table_name();
		$steps_table  = get_db( 'steps' )->get_table_name();

		if ( $this->get_funnel_id() ) {
			$data = $wpdb->get_results( $wpdb->prepare(
				"SELECT DISTINCT e.step_id FROM $events_table e
                    LEFT JOIN $steps_table s ON e.step_id = s.ID
                    WHERE e.status = %s AND s.step_type = %s AND e.funnel_id = %s
                    AND e.time >= %d AND e.time <= %d
                    ORDER BY time DESC"
				, 'complete', 'send_email', $this->get_funnel_id(), $this->start, $this->end )
			);
		} else {
			$data = $wpdb->get_results( $wpdb->prepare(
				"SELECT DISTINCT e.step_id FROM $events_table e
                    LEFT JOIN $steps_table s ON e.step_id = s.ID
                    WHERE e.status = %s AND s.step_type = %s
                    AND e.time >= %d AND e.time <= %d
                    ORDER BY time DESC"
				, 'complete', 'send_email', $this->start, $this->end )
			);
		}

		$step_ids = wp_list_pluck( $data, 'step_id' );

		$emails = [];

		foreach ( $step_ids as $step_id ) {
			$step = new Step( $step_id );

			$emails[] = absint( $step->get_meta( 'email_id' ) );
		}

		return array_unique( $emails );

	}

	/**
	 * Whether this email should be included...
	 *
	 * @param $sent
	 * @param $opened
	 * @param $clicked
	 *
	 * @return mixed
	 */
	abstract protected function should_include( $sent, $opened, $clicked );

	/**
	 * Get the table data
	 *
	 * @return array|mixed
	 */
	protected function get_table_data() {

		$emails = $this->get_email_ids_of_sent_emails();

		$list = [];

		foreach ( $emails as $email ) {

			$email_id = is_object( $email ) ? $email->ID : $email;

			$email = new Email( $email_id );

			if ( ! $email->exists() ) {
				continue;
			}

			$report = $email->get_email_stats( $this->start, $this->end );

			$title = $email->get_title();

			if ( $this->should_include( $report['sent'], $report['opened'], $report ['clicked'] ) ) {
				$list[] = [
					'email'   => $email,
					'sent'    => _nf( $report['sent'] ),
					'opened'  => percentage( $report['sent'], $report['opened'] ),
					'clicked' => percentage( $report['opened'], $report['clicked'] ),
				];

			}

		}

		return $this->normalize_data( $list );
	}

	protected function normalize_data( $data ) {
		$data = parent::normalize_data( $data );

		foreach ( $data as $i => &$datum ) {

			$datum['label'] = html()->e( 'a', [
				'title' => $datum['label'],
				'href'  => $datum['url'],
			], $datum['label'] );

			$datum['opened']  = $datum['opened'] . '%';
			$datum['clicked'] = $datum['clicked'] . '%';

			unset( $datum['url'] );
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
		return [
			'label'   => $item_data['label'],
			'url'     => $item_data['url'],
			'sent'    => $item_data['sent'],
			'opened'  => $item_data['opened'],
			'clicked' => $item_data['clicked'],
		];
	}


	/**
	 * Sort stuff
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return mixed
	 */
	public function sort( $a, $b ) {

		if ( $a['sent'] === $b['sent'] ) {

			if ( $a['opened'] === $b['opened'] ) {
				return $a['clicked'] - $b['clicked'];
			}

			return $a['opened'] - $b['opened'];
		}

		return $a['sent'] - $b['sent'];
	}

}