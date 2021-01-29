<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Contacts_By_Lead_Source extends Base_Table_Report {
	function only_show_top_10() {
		return true;
	}

	function column_title() {
		// TODO: Implement column_title() method.
	}

	public function get_label() {
		return [
			__( 'Lead Source', 'groundhogg' ),
			__( 'Number of Contacts', 'groundhogg' ),
			__( 'Percentage', 'groundhogg' ),
		];
	}

	/**
	 * Lead source
	 *
	 * @return array
	 */
	protected function get_table_data() {

		$ids  = $this->get_new_contact_ids_in_time_period();
		$rows = get_db( 'contactmeta' )->query( [
			'relationship' => 'AND',
			'where'        => [
				[ 'col' => 'contact_id', 'compare' => 'IN', 'val' => $ids ],
				[ 'col' => 'meta_key', 'compare' => '=', 'val' => 'lead_source' ],
				[ 'col' => 'meta_value', 'compare' => '!=', 'val' => '' ],
			],
		] );

		return $this->parse_meta_records( $rows );
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
			'label' => $item_key,
			'data'  => $item_data,
		];
	}


}