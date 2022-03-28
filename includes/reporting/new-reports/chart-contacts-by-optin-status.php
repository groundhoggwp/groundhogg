<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\DB\DB;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Preferences;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\isset_not_empty;

class Chart_Contacts_By_Optin_Status extends Base_Doughnut_Chart_Report {

	protected function get_chart_data() {

		$values = wp_list_pluck( $this->get_new_contacts_in_time_period(), 'optin_status' );
		$counts = array_count_values( $values );

		$data  = [];
		$label = [];
		$color = [];

		// normalize data
		foreach ( $counts as $key => $datum ) {
			$normalized = $this->normalize_datum( $key, $datum );
			$label []   = $normalized ['label'];
			$data[]     = $normalized ['data'];
			$color[]    = $normalized ['color'];
		}

		return [
			'label' => $label,
			'data'  => $data,
			'color' => $color
		];

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
			'label' => Preferences::get_preference_pretty_name( Preferences::sanitize( $item_key ) ),
			'data'  => $item_data,
			'color' => $this->get_random_color()
		];
	}
}