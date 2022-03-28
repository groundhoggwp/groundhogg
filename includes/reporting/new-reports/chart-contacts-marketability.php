<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\Ymd_His;

class Chart_Contacts_Marketability extends Base_Doughnut_Chart_Report {

	protected function get_chart_data() {

		$this->start = Plugin::instance()->utils->date_time->convert_to_local_time( $this->start );
		$this->end   = Plugin::instance()->utils->date_time->convert_to_local_time( $this->end );

		$query = new Contact_Query();

		$confirmed_count = $query->count( [
			'optin_status' => Preferences::CONFIRMED,
			'date_query'   => [
				'after'  => date( 'Y-m-d H:i:s', $this->start ),
				'before' => date( 'Y-m-d H:i:s', $this->end ),
			]
		] );

		$unconfirmed_marketable_count = $query->count( [
			'optin_status' => Preferences::UNCONFIRMED,
			'date_query'   => [
				'after'  => Ymd_His( strtotime( Plugin::instance()->preferences->get_grace_period() . ' days ago' ) ),
				'before' => date( 'Y-m-d H:i:s', $this->end ),
			]
		] );

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