<?php


namespace Groundhogg\Reporting\New_Reports;

use function Groundhogg\html;
use function Groundhogg\percentage;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
abstract class Base_Table_Report extends Base_Report {

	/**
	 * @return array
	 */
	public function get_data() {
		return [
			'type'  => 'table',
			'label' => $this->get_label(),
			'data'  => $this->get_table_data()
		];
	}

	/**
	 * @return bool
	 * @deprecated
	 */
	function only_show_top_10() {
		return false;
	}

	/**
	 * @return int
	 */
	function get_num_results() {
		return 10;
	}

	/**
	 * @return mixed
	 */
	abstract function get_label();

	/**
	 * @return mixed
	 */
	abstract protected function get_table_data();

	/**
	 * Normalize a datum
	 *
	 * @param $item_key
	 * @param $item_data
	 *
	 * @return array
	 */
	abstract protected function normalize_datum( $item_key, $item_data );

	/**
	 * Format the data into a chart friendly format.
	 *
	 * @param $data array
	 *
	 * @return array
	 */
	protected function normalize_data( $data ) {
		if ( empty( $data ) ) {
			$data = [];
		}

		$dataset = [];

		foreach ( $data as $key => $datum ) {
			$dataset[] = $this->normalize_datum( $key, $datum );
		}

		$dataset = array_values( $dataset );

		usort( $dataset, array( $this, 'sort' ) );

		/* Pair down the results to largest 10 */
		if ( count( $dataset ) > $this->get_num_results() ) {
			$dataset = array_slice( $dataset, 0, $this->get_num_results() );
		}

		usort( $dataset, array( $this, 'sort' ) );

		return $dataset;
	}

	/**
	 * Parse meta rows...
	 *
	 * @param $rows
	 *
	 * @return array
	 */
	protected function parse_meta_records( $rows ) {
		$values = wp_list_pluck( $rows, 'meta_value' );
		return $this->parse_table_data( $values );
	}

	/**
	 * Build table data
	 *
	 * @param $values
	 *
	 * @return array
	 */
	protected function parse_table_data( $values ){
		$counts = array_count_values( $values );
		$data   = $this->normalize_data( $counts );
		$total  = array_sum( wp_list_pluck( $data, 'data' ) );

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
	 * Sort stuff
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return mixed
	 */
	public function sort( $a, $b ) {
		return $b['data'] - $a['data'];
	}
}