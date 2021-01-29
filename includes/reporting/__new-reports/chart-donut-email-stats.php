<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Email;

class Chart_Donut_Email_Stats extends Base_Doughnut_Chart_Report {

	protected function get_chart_data() {

		$email  = new Email( $this->get_email_id() );
		$stats  = $email->get_email_stats( $this->start, $this->end );
		$counts = $this->normalize_data( $stats );

		$data  = [];
		$label = [];
		$color = [];

		// normalize data
		foreach ( $counts as $key => $datum ) {

			$label[] = $datum ['label'];
			$data[]  = $datum ['data'];
			$color[] = $datum ['color'];

		}

		return [
			'label' => $label,
			'data'  => $data,
			'color' => $color
		];

	}

	protected function normalize_data( $stats ) {

		if ( empty( $stats ) ) {
			return $stats;
		}

		/*
		* create array  of data ..
		*/
		$dataset = array();

		$dataset[] = array(
			'label' => _x( 'Opened', 'stats', 'groundhogg' ),
			'data'  => $stats['opened'] - $stats['clicked'],
			'url'   => add_query_arg(
				[
					'activity' => [
						'activity_type' => Activity::EMAIL_OPENED,
						'email_id'      => $this->get_email_id(),
						'before'        => $this->end,
						'after'         => $this->start,
					]
				],
				admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
			),
			'color' => $this->get_random_color()
		);

		$dataset[] = array(
			'label' => _x( 'Clicked', 'stats', 'groundhogg' ),
			'data'  => $stats['clicked'],
			'url'   => add_query_arg(
				[
					'activity' => [
						'activity_type' => Activity::EMAIL_CLICKED,
						'email_id'      => $this->get_email_id(),
						'before'        => $this->end,
						'after'         => $this->start,
					]
				],
				admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
			),
			'color' => $this->get_random_color()
		);

		$dataset[] = array(
			'label' => _x( 'Unopened', 'stats', 'groundhogg' ),
			'data'  => $stats['sent'] - $stats['opened'],
			'url'   => '#',
			'color' => $this->get_random_color()
		);

		return $dataset;
	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}
}
