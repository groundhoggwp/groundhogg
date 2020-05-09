<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Broadcast;
use Groundhogg\Email;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\percentage;

class Table_Top_Performing_Broadcasts extends Base_Email_Performance_Table_Report {

	/**
	 * Get email IDs from broadcasts...
	 *
	 * @return array
	 */
	protected function get_email_ids_of_sent_emails() {

		$broadcasts = get_db( 'broadcasts' )->query( [
			'where' => [
				'relationship' => "AND",
				[ 'col' => 'status', 'val' => 'sent', 'compare' => '=' ],
				[ 'col' => 'object_type', 'val' => 'email', 'compare' => '=' ],
				[ 'col' => 'send_time', 'val' => $this->start, 'compare' => '>=' ],
				[ 'col' => 'send_time', 'val' => $this->end, 'compare' => '<=' ],
			],
		] );

		return wp_parse_id_list( wp_list_pluck( $broadcasts, 'ID' ) );

	}

	protected function should_include( $sent, $opened, $clicked ) {
		return $sent > 10 && percentage( $sent, $opened ) > 20 && percentage( $opened, $clicked ) > 10;
	}

	protected function get_table_data() {
		$emails = $this->get_email_ids_of_sent_emails();

		$list = [];

		foreach ( $emails as $email ) {

			$email_id = is_object( $email ) ? $email->ID : $email;

			$email  = new Broadcast( $email_id );
			$report = $email->get_report_data();

			$title = $email->get_title();

			if ( $this->should_include( $report['sent'], $report['opened'], $report ['clicked'] ) ) {
				$list[] = [
					'label'   => $title,
					'url'     => admin_page_url( 'gh_reporting', [
						'tab' => 'broadcasts',
						'broadcast'  => $email->get_id()
					] ),
					'sent'    => $report['sent'],
					'opened'  => percentage( $report['sent'], $report['opened'] ),
					'clicked' => percentage( $report['opened'], $report['clicked'] ),
				];

			}

		}

		return $this->normalize_data( $list );
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

			if ( $a['opened'] === $b['opened'] ) {
				return $b['clicked'] - $a['clicked'];
			}

			return $b['opened'] - $a['opened'];
		}

		return $b['sent'] - $a['sent'];
	}
}