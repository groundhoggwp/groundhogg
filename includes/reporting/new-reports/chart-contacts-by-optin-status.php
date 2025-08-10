<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Preferences;

class Chart_Contacts_By_Optin_Status extends Base_Doughnut_Chart_Report {

	protected function get_chart_data() {

		$query = new Table_Query( 'contacts' );
		$query->setSelect( 'optin_status', [ 'COUNT(ID)', 'total' ] );
		$query->where()->greaterThanEqualTo( 'date_created', $this->startDate->ymdhis() );
		$query->where()->lessThanEqualTo( 'date_created', $this->endDate->ymdhis() );
		$query->setGroupby( 'optin_status' );
		$query->setOrderby( 'total' );

		$results = $query->get_results();

		$data  = [];
		$label = [];
		$color = [];

		// normalize data
		foreach ( $results as $result ) {
			$normalized = $this->normalize_datum( absint( $result->optin_status ), absint( $result->total ) );
			$label[]    = $normalized['label'];
			$data[]     = $normalized['data'];
			$color[]    = $normalized['color'];
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
		$label = esc_html( Preferences::get_preference_pretty_name( $item_key ) );
		return [
			'label' => $label,
			'data'  => $item_data,
//			'url'  => admin_url( 'admin.php?page=gh_contacts&optin_status=' . $item_key ),
			'color' => $this->get_random_color()
		];
	}
}
