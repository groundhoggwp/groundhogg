<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\DB\DB;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\isset_not_empty;

class Chart_Contacts_By_Region extends Base_Doughnut_Chart_Report {

	/**
	 * Get the country code for the request
	 *
	 * @return mixed|string
	 */
	protected function get_country_code() {
		$country_code = get_array_var( get_request_var( 'data', [] ), 'country' );
		$country_code = strtoupper( substr( $country_code, 0, 2 ) );

		return $country_code;
	}

	/**
	 * Ge
	 *
	 * @return array
	 */
	protected function get_chart_data() {

		$country_meta = get_db( 'contactmeta' )->query( [
			'meta_key'   => 'country',
			'meta_value' => $this->get_country_code()
		] );

		$contacts_in_country = wp_parse_id_list( wp_list_pluck( $country_meta, 'contact_id' ) );
		$contacts            = array_intersect( $this->get_new_contact_ids_in_time_period(), $contacts_in_country );

		if ( empty( $contacts ) ) {
			return [
				'label' => [],
				'data'  => [],
				'color' => []
			];
		}

		$rows = get_db( 'contactmeta' )->query( [
			'contact_id' => $contacts,
			'meta_key'   => 'region',
		], false );

		return $this->normalize_data( $rows );

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
		$label = ! empty( $item_key ) ? $item_key : __( 'Unknown' );
		$data  = $item_data;
		$url   = ! empty( $item_key ) ? admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=region&meta_value=%s', $item_key ) ) : '#';

		return [
			'label' => $label,
			'data'  => $data,
			'url'   => $url,
			'color' => $this->get_random_color()
		];
	}

}