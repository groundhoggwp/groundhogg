<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
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

	/**
	 * @return array
	 */
	public function get_data() {
		return [
			'type'  => 'table',
			'label' => $this->get_label(),
			'data'  => $this->get_lead_source()
		];
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
	protected function get_lead_source() {

		$this->start = Plugin::instance()->utils->date_time->convert_to_local_time( $this->start );
		$this->end   = Plugin::instance()->utils->date_time->convert_to_local_time( $this->end );

		$query   = new Contact_Query();

		$contacts = $query->query( [
			'date_query' => [
				'after'  => date( 'Y-m-d H:i:s', $this->start ),
				'before' => date( 'Y-m-d H:i:s', $this->end ),
			]
		]  );

		$contacts = wp_parse_id_list( wp_list_pluck( $contacts, 'ID' ) );

		$rows = [];

		if ( count( $contacts ) > 0 ){
			$rows = get_db( 'contactmeta' )->query( [
				'relationship' => 'AND',
				'where' => [
					[ 'col' => 'contact_id', 'compare' => 'IN', 'val' => $contacts ],
					[ 'col' => 'meta_key', 'compare' => '=', 'val' => 'lead_source' ],
					[ 'col' => 'meta_value', 'compare' => '!=', 'val' => '' ],
				],
				'contact_id' => $contacts,
				'meta_key'   => 'lead_source'
			] );
		}

		$values = wp_list_pluck( $rows, 'meta_value' );
		$counts = array_count_values( $values );
		$data = $this->normalize_data( $counts );
		$total = array_sum( wp_list_pluck( $data, 'data' ) );

		foreach ( $data as $i => $datum ) {

			$sub_tal    = $datum['data'];
			$percentage = ' (' . percentage( $total, $sub_tal ) . '%)';

			$datum['data'] = html()->e( 'a', [
				'href'  => $datum['url'],
				'class' => 'number-total',
				'title' => $datum['url'],
			], $datum['data'] . $percentage );

			unset( $datum['url'] );
			$data[ $i ] = $datum;
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
		return [
			'label' => Plugin::$instance->utils->html->wrap( $item_key, 'a', [
				'href'   => $item_key,
				'target' => '_blank'
			] ),
			'data'  => $item_data,
			'url'   => admin_url( 'admin.php?page=gh_contacts&meta_value=lead_source&meta_value=' . urlencode( $item_key ) )
		];
	}


}