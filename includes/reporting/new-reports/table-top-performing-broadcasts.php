<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Broadcast;
use Groundhogg\DB\Query;
use function Groundhogg\admin_page_url;
use function Groundhogg\percentage;

class Table_Top_Performing_Broadcasts extends Base_Email_Performance_Table_Report {

	/**
	 * Get email IDs from broadcasts...
	 *
	 * @return array
	 */
	protected function get_send_email_steps() {

		$query = new Query( 'broadcasts' );
		$query->where( 'status', 'sent' )
		      ->equals( 'object_type', 'email' )
		      ->greaterThanEqualTo( 'send_time', $this->start )
		      ->lessThanEqualTo( 'send_time', $this->end );

	}

	protected function should_include( $sent, $opened, $clicked ) {
		return $sent > 10 && percentage( $sent, $opened ) > 20 && percentage( $opened, $clicked ) > 10;
	}

	protected function get_table_data() {

		$query = new Query( 'broadcasts' );
		$query->where( 'status', 'sent' )
		      ->equals( 'object_type', 'email' )
		      ->greaterThanEqualTo( 'send_time', $this->start )
		      ->lessThanEqualTo( 'send_time', $this->end );

		$broadcasts = $query->get_objects( Broadcast::class );

		$list = [];

		foreach ( $broadcasts as $broadcast ) {

			$report = $broadcast->get_report_data();
			$title  = $broadcast->get_title();

			$list[] = [
				'label'   => $title,
				'url'     => admin_page_url( 'gh_reporting', [
					'tab'       => 'broadcasts',
					'broadcast' => $broadcast->get_id()
				] ),
				'sent'    => $report['sent'],
				'opened'  => percentage( $report['sent'], $report['opened'] ),
				'clicked' => percentage( $report['opened'], $report['clicked'] ),
			];
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
