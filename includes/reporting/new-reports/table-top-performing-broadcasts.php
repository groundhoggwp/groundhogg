<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Broadcast;
use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Top_Performing_Broadcasts extends Base_Table_Report {


	function only_show_top_10() {
		return true;
	}

	function column_title() {
		// TODO: Implement column_title() method.
	}

	/**
	 *
	 *
	 *
	 * @return array
	 *
	 */
	public function get_data() {
		return [
			'type' => 'table',
			'label' => $this->get_label(),
			'data' =>
				$this->get_broadcasts()
		];
	}


	public function get_label()
	{
		return [
			__('Emails' , 'groundhogg' ),
			__('Open Rate' , 'groundhogg' ),
			__( 'Click Thorough Rate' , 'groundhogg'  )
		] ;

	}



	protected function get_broadcasts() {


		$where = [
			'relationship' => "AND",
			[ 'col' => 'status', 'val' => 'sent', 'compare' => '=' ],
			[ 'col' => 'object_type', 'val' => 'email', 'compare' => '=' ],
			[ 'col' => 'send_time', 'val' => $this->start, 'compare' => '>=' ],
			[ 'col' => 'send_time', 'val' => $this->end, 'compare' => '<=' ],
		];

		$broadcasts = get_db( 'broadcasts' )->query( [
			'where' => $where,
		] );

		if (empty($broadcasts)){

			return [

			] ;
		}

		$list = [];

		foreach ( $broadcasts as $broadcast ) {

			$broadcast = new Broadcast( $broadcast->ID );

			$report = $broadcast->get_report_data();

			$list[] = [

				'data'   => percentage( $report[ 'sent' ], $report[ 'opened' ] ),
				'opened' => $report [ 'opened' ],
				'label'  => $broadcast->get_title(),
				'click_through_rate' =>  percentage( $report[ 'opened' ], $report[ 'clicked' ] ),
				'url'    => admin_url( sprintf( 'admin.php?page=gh_broadcasts&action=report&broadcast=%s', $broadcast->get_id() ) ),

			];

		}

		$list = $this->normalize_data( $list );

		foreach ( $list as $i => $datum ) {


			$datum[ 'label' ] = html()->wrap(  $datum[ 'label' ] , 'a', [
				'href'  => $datum[ 'url' ],
				'class' => 'number-total'
			] );
			$datum[ 'data' ] =  $datum[ 'data' ] . '%';
			$datum[ 'click_through_rate' ] =  $datum[ 'click_through_rate' ] . '%';

			unset( $datum[ 'url' ] );
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
			'label'  => $item_data [ 'label' ],
			'data'   => $item_data [ 'data' ],
			'click_through_rate' => $item_data [ 'click_through_rate' ],
			'url'    => $item_data [ 'url' ],
		];
	}


}