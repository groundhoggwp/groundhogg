<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Contacts_By_Lead_Source extends  Base_Report
{

	/**
	 *
	 *
	 *
	 * @return array
	 *
	 */
	public function get_data()
	{
		return [
			'type'=> 'table',
			'data' =>
				$this->get_lead_score()



		];
	}


	protected function get_lead_score() {

		$contacts = get_db( 'contacts' )->query( [
			'date_query' => [
				'after'  => date( 'Y-m-d H:i:s', $this->start ),
				'before' => date( 'Y-m-d H:i:s', $this->end ),
			]
		] );

		$contacts = wp_parse_id_list( wp_list_pluck( $contacts, 'ID' ) );


		$rows = get_db( 'contactmeta' )->query( [
			'contact_id' =>$contacts,
			'meta_key' => 'lead_source'
		], false );



		$values = wp_list_pluck( $rows, 'meta_value'  );

		$counts = array_count_values( $values );

		$data  = $this->normalize_data($counts);

		// normalize data
		foreach ( $counts as $key => $datum ) {
			$data[] = $this->normalize_datum( $key, $datum );

		}


		$total = array_sum( wp_list_pluck( $data, 'data' ) );

		foreach ( $data as $i => $datum )
		{

			$sub_tal = $datum[ 'data' ];
			$percentage = ' (' . percentage( $total, $sub_tal ) . '%)';

			$datum[ 'data' ] = html()->wrap( $datum[ 'data' ] . $percentage, 'a', [ 'href' => $datum[ 'url' ], 'class' => 'number-total' ] );
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
	 * @return array
	 */
	protected function normalize_datum($item_key, $item_data)
	{
		return [
			'label' => Plugin::$instance->utils->html->wrap( $item_key, 'a', [ 'href' => $item_key, 'target' => '_blank' ] ),
			'data' => $item_data,
			'url'  => admin_url( 'admin.php?page=gh_contacts&meta_value=lead_source&meta_value=' . urlencode( $item_key ) )
		];
	}


	/**
	 * Format the data into a chart friendly format.
	 *
	 * @param $data array
	 * @return array
	 */
	protected function normalize_data( $data )
	{
		if ( empty( $data ) ){
			$data = [];
		}

		$dataset = [];

		foreach ( $data as $key => $datum ){
			if ( $key && $datum ){
				$dataset[] = $this->normalize_datum( $key, $datum );
			}
		}

		$dataset = array_values( $dataset );

		usort( $dataset , array( $this, 'sort' ) );

		/* Pair down the results to largest 10 */
		if ( count( $dataset ) > 10 && $this->only_show_top_10() ){

			$other_dataset = [
				'label' => __( 'Other' ),
				'data' => 0,
				'url'  => '#'
			];

			$other = array_slice( $dataset, 10 );
			$dataset = array_slice( $dataset, 0, 10);

			foreach ( $other as $c_data ){
				$other_dataset[ 'data' ] += $c_data[ 'data' ];
			}

			$dataset[] = $other_dataset;

		}

		usort( $dataset , array( $this, 'sort' ) );


		return $dataset;
	}

	/**
	 * Sort stuff
	 *
	 * @param $a
	 * @param $b
	 * @return mixed
	 */
	public function sort( $a, $b )
	{
		return $b[ 'data' ] - $a[ 'data' ];
	}

	protected function only_show_top_10()
	{
		return false;
	}

}