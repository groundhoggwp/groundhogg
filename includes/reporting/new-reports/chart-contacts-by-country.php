<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\utils;

class Chart_Contacts_By_Country extends Base_Doughnut_Chart_Report {

	protected function get_chart_data() {

		$rows = $this->get_new_contact_meta_totals( 'country', 'country', 10 );


		$data  = [];
		$label = [];
		$color = [];

		// normalize data
		foreach ( $rows as $row ) {
			$label[] = utils()->location->get_countries_list( $row->country );
			$data[]  = $row->total;
			$color[] = $this->get_random_color();

		}

		return [
			'label' => $label,
			'data'  => $data,
			'color' => $color
		];
	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}
}
