<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\utils;

class Chart_Contacts_By_Country extends Base_Doughnut_Chart_Report {

	protected function get_chart_data() {

		global $wpdb;
		$contacts_table = get_db( 'contacts' )->table_name;

		$rows = get_db( 'contactmeta' )->query( [
			'contact_id' => $wpdb->prepare( "SELECT ID FROM $contacts_table WHERE date_created BETWEEN %s AND %s", $this->startDate->format( 'Y-m-d H:i:s' ), $this->endDate->format( 'Y-m-d H:i:s' ) ),
			'meta_key'   => 'country',
			'select'     => [ 'COUNT(contact_id) as total', 'meta_value as country' ],
			'meta_value' => 'NOT_EMPTY',
			'groupby'    => 'country',
			'orderby'    => 'total',
			'order'      => 'desc',
			'limit'      => 10,
		] );


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
