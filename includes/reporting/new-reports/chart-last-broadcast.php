<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Reporting\New_Reports\Traits\Broadcast_Stats;

class Chart_Last_Broadcast extends Base_Doughnut_Chart_Report {

	use Broadcast_Stats;
	protected function get_chart_data() {

		[
			'sent'    => $sent,
			'opened'  => $opened,
			'clicked' => $clicked,
		] = $this->get_broadcast_stats();

		// SMS Stats
		if ( $this->get_broadcast() && $this->get_broadcast()->is_sms() ){

			return [
				'label' => [
					_x( 'Clicked', 'stats', 'groundhogg' ),
					_x( 'Sent', 'stats', 'groundhogg' ),
				],
				'data'  => [
					$clicked,
					$sent - $opened,
				],
				'color' => [
					$this->get_random_color(),
					$this->get_random_color(),
				]
			];

		}

		return [
			'label' => [
				_x( 'Clicked', 'stats', 'groundhogg' ),
				_x( 'Opened', 'stats', 'groundhogg' ),
				_x( 'Unopened', 'stats', 'groundhogg' ),
			],
			'data'  => [
				$clicked,
				$opened - $clicked,
				$sent - $opened,
			],
			'color' => [
				$this->get_random_color(),
				$this->get_random_color(),
				$this->get_random_color()
			]
		];
	}

	protected function normalize_data( $stats ) {

	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}
}
