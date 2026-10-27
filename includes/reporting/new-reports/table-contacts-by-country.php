<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Plugin;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\utils;

class Table_Contacts_By_Country extends Base_Table_Report {

	public function get_label() {
		return [
			__( 'Country', 'groundhogg' ),
			__( 'Contacts', 'groundhogg' ),
		];
	}

	/**
	 * @return array
	 */
	protected function get_table_data() {

		$rows = $this->get_new_contact_meta_totals( 'country', 'country', 10 );

		$data = [];

		foreach ( $rows as $i => $row ) {

			$data[] = [
				utils()->location->get_countries_list( $row->country ),
				html()->e( 'a', [
					'class' => 'number-total',
					'href'  => admin_page_url( 'gh_contacts', [
						'filters' => base64_json_encode( [
							[
								[ 'type' => 'date_created', 'date_range' => 'between', 'before' => $this->endDate->format('Y-m-d H:i:s' ), 'after' => $this->startDate->format('Y-m-d H:i:s' ) ],
								[ 'type' => 'country', 'value' => $row->country, 'compare' => 'equals' ]
							]
						] )
					] )
				], $row->total ),
			];
		}

		return $data;

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

	}


}
