<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Contacts_By_Country extends  Base_Table_Report
{
	function only_show_top_10() {
		return true ;
	}

	function column_title() {
		// TODO: Implement column_title() method.
	}

	/**
	 * @return array
	 */
	public function get_data()
	{
		return [
			'type'=> 'table',
			'label'=> $this->get_label(),
			'data' =>
				$this->get_by_country()
		];
	}

	public function get_label() {
		return [
			__( 'Country', 'groundhogg' ),
			__( 'Contacts', 'groundhogg' ),
		];

	}



	protected function get_by_country() {

		$contacts = get_db( 'contacts' )->query( [
			'date_query' => [
				'after'  => date( 'Y-m-d H:i:s', $this->start ),
				'before' => date( 'Y-m-d H:i:s', $this->end ),
			]
		] );

		$contacts = wp_parse_id_list( wp_list_pluck( $contacts, 'ID' ) );


		$rows = get_db( 'contactmeta' )->query( [
			'contact_id' =>$contacts,
			'meta_key' => 'country'
		], false );



		$values = wp_list_pluck( $rows, 'meta_value'  );

		$counts = array_count_values( $values );

		$data  = $this->normalize_data($counts );


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

		$label = ! empty( $item_key ) ? Plugin::$instance->utils->location->get_countries_list( $item_key ): __( 'Unknown' );
		$data  = $item_data;
		$url   = ! empty( $item_key ) ? admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=country&meta_value=%s', $item_key ) ) : '#';




		return [
			'label' => $label,
			'data' => $data,
			'url'  => $url
		];
	}



}