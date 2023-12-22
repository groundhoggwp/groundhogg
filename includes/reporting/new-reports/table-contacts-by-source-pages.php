<?php

namespace Groundhogg\Reporting\New_Reports;


use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\html;

class Table_Contacts_By_Source_Pages extends Base_Table_Report {

	function column_title() {
		// TODO: Implement column_title() method.
	}

	public function get_label() {
		return [
			__( 'Signup Page', 'groundhogg' ),
			__( 'Contacts', 'groundhogg' ),
		];
	}

	protected function get_table_data() {

		global $wpdb;
		$contacts_table = get_db( 'contacts' )->table_name;

		$rows = get_db( 'contactmeta' )->query( [
			'contact_id' => $wpdb->prepare( "SELECT ID FROM $contacts_table WHERE date_created BETWEEN %s AND %s", $this->startDate->format( 'Y-m-d H:i:s' ), $this->endDate->format( 'Y-m-d H:i:s' ) ),
			'meta_key'   => 'source_page',
			'select'     => [ 'COUNT(contact_id) as total', 'meta_value as source_page' ],
			'meta_value' => 'NOT_EMPTY',
			'groupby'    => 'source_page',
			'orderby'    => 'total',
			'order'      => 'desc',
		] );

		$totals = [];

		foreach ( $rows as $row ) {

			$path = wp_parse_url( $row->source_page, PHP_URL_PATH );

			if ( ! $path ) {
				$path = $row->source_page;
			}

			if ( ! isset( $totals[ $path ] ) ) {
				$totals[ $path ] = 0;
			}

			$totals[ $path ] += $row->total;
		}

		$totals = array_slice( $totals, 0, 10 );

		$data = [];

		foreach ( $totals as $path => $total ) {

			$data[] = [
				html()->wrap( $path, 'a', [
					'href'   => $path,
					'target' => '_blank',
				] ),
				html()->e( 'a', [
					'class' => 'number-total',
					'href'  => admin_page_url( 'gh_contacts', [
						'filters' => base64_json_encode( [
							[
								[ 'type' => 'date_created', 'date_range' => 'between', 'before' => $this->endDate->format('Y-m-d H:i:s' ), 'after' => $this->startDate->format('Y-m-d H:i:s' ) ],
								[ 'type' => 'meta', 'meta' => 'source_page', 'value' => $path, 'compare' => 'contains' ]
							]
						] )
					] )
				], $total ),
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
