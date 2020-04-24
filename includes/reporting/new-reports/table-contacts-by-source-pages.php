<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Contacts_By_Source_Pages extends Base_Table_Report {

	function column_title() {
		// TODO: Implement column_title() method.
	}

	public function get_label() {
		return [
			__( 'Source Page', 'groundhogg' ),
			__( 'Contacts', 'groundhogg' ),
		];
	}

	protected function get_chart_data() {

		$ids  = $this->get_new_contact_ids_in_time_period();
		$rows = get_db( 'contactmeta' )->query( [
			'relationship' => 'AND',
			'where'        => [
				[ 'col' => 'contact_id', 'compare' => 'IN', 'val' => $ids ],
				[ 'col' => 'meta_key', 'compare' => '=', 'val' => 'source_page' ],
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
			'label' => Plugin::$instance->utils->html->wrap( $item_key, 'a', [
				'href'   => $item_key,
				'target' => '_blank'
			] ),
			'data'  => $item_data,
			'url'   => admin_url( 'admin.php?page=gh_contacts&meta_value=source_page&meta_value=' . urlencode( $item_key ) )
		];
	}
}