<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\percentage;

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

		$rows = get_db( 'contactmeta' )->query( [
			'contact_id' => $this->get_new_contact_ids_in_time_period(),
			'meta_key'   => 'country'
		], false );

		$values = wp_list_pluck( $rows, 'meta_value' );
		$counts = array_count_values( $values );
		$data   = $this->normalize_data( $counts );
		$total  = array_sum( wp_list_pluck( $data, 'data' ) );

		foreach ( $data as $i => $datum ) {
			$sub_tal    = $datum['data'];
			$percentage = ' (' . percentage( $total, $sub_tal ) . '%)';

			$datum['data'] = html()->wrap( $datum['data'], 'a', [
				'href'  => $datum['url'],
				'class' => 'number-total'
			] );
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

		$label = ! empty( $item_key ) ? Plugin::$instance->utils->location->get_countries_list( $item_key ) : __( 'Unknown' );
		$data  = $item_data;
		$url   = ! empty( $item_key ) ? admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=country&meta_value=%s', $item_key ) ) : '#';

		return [
			'label' => $label,
			'data'  => $data,
			'url'   => $url
		];
	}


}