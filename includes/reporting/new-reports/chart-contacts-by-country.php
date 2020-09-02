<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\DB\DB;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\isset_not_empty;

class Chart_Contacts_By_Country extends Base_Doughnut_Chart_Report {

	protected function get_chart_data() {

		
		$rows = get_db( 'contactmeta' )->query( [
			'contact_id' => $this->get_new_contact_ids_in_time_period(),
			'meta_key'   => 'country'
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
		$label = ! empty( $item_key ) ? Plugin::$instance->utils->location->get_countries_list( $item_key ) : __( 'Unknown' );
		$data  = $item_data;
		$url   = ! empty( $item_key ) ? admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=country&meta_value=%s', $item_key ) ) : '#';


		return [
			'label' => $label,
			'data'  => $data,
//			'url'  =>  $url
			'color' => $this->get_random_color()
		];
	}

}