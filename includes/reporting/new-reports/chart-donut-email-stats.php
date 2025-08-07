<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Reporting\New_Reports\Traits\Funnel_Email_Stats;
use function Groundhogg\get_array_var;

/**
 * This report is only used in the step single report
 */
class Chart_Donut_Email_Stats extends Base_Doughnut_Chart_Report {

	use Funnel_Email_Stats;

	protected function get_chart_data() {

		[
			'sent' => $sent,
			'opened' => $opened,
			'clicked' => $clicked,
		] = $this->get_funnel_email_stats();

		return [
			'label' => [
				esc_html_x( 'Clicked', 'stats', 'groundhogg' ),
				esc_html_x( 'Opened', 'stats', 'groundhogg' ),
				esc_html_x( 'Unopened', 'stats', 'groundhogg' ),
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
