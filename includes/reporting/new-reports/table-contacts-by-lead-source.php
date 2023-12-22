<?php

namespace Groundhogg\Reporting\New_Reports;


use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\html;

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
			__( 'Contacts', 'groundhogg' ),
		];
	}

	/**
	 * Lead source
	 *
	 * @return array
	 */
	protected function get_table_data() {

		global $wpdb;
		$contacts_table = get_db( 'contacts' )->table_name;

		$rows = get_db( 'contactmeta' )->query( [
			'contact_id' => $wpdb->prepare( "SELECT ID FROM $contacts_table WHERE date_created BETWEEN %s AND %s", $this->startDate->format( 'Y-m-d H:i:s' ), $this->endDate->format( 'Y-m-d H:i:s' ) ),
			'meta_key'   => 'lead_source',
			'select'     => [ 'COUNT(contact_id) as total', 'meta_value as lead_source' ],
			'meta_value' => 'NOT_EMPTY',
			'groupby'    => 'lead_source',
			'orderby'    => 'total',
			'order'      => 'desc',
		] );

		$totals = [];

		foreach ( $rows as $row ) {

			$domain = wp_parse_url( $row->lead_source, PHP_URL_HOST );

			if ( ! $domain ) {
				$domain = $row->lead_source;
			}

			if ( ! isset( $totals[ $domain ] ) ) {
				$totals[ $domain ] = 0;
			}

			$totals[ $domain ] += $row->total;
		}

		$totals = array_slice( $totals, 0, 10 );

		$data = [];

		foreach ( $totals as $domain => $total ) {

			$data[] = [
				html()->wrap( $domain, 'a', [
					'href'   => $domain,
					'target' => '_blank',
				] ),
				html()->e( 'a', [
					'class' => 'number-total',
					'href'  => admin_page_url( 'gh_contacts', [
						'filters' => base64_json_encode( [
							[
								[ 'type' => 'date_created', 'date_range' => 'between', 'before' => $this->endDate->format('Y-m-d H:i:s' ), 'after' => $this->startDate->format('Y-m-d H:i:s' ) ],
								[ 'type' => 'meta', 'meta' => 'lead_source', 'value' => $domain, 'compare' => 'contains' ]
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
