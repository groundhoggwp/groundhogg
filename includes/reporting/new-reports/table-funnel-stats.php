<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\percentage;

class Table_Funnel_Stats extends Base_Table_Report {


	public function get_label() {
		return [
			__( 'Steps', 'groundhogg' ),
			__( 'Completed Contacts', 'groundhogg' ),
			__( 'Waiting Contacts', 'groundhogg' ),
		];
	}

	/**
	 * @return array|mixed
	 */
	protected function get_table_data() {
		//get list of benchmark
		$funnel = new Funnel( absint( $this->get_funnel_id()) );
		$steps  = $funnel->get_steps();

		$data = [];

		foreach ( $steps as $i => $step ) {

			$query = new Contact_Query();

			$args = array(
				'report' => array(
					'funnel' => $funnel->get_id(),
					'step'   => $step->get_id(),
					'status' => 'complete',
					'start'  => $this->start,
					'end'    => $this->end,
				)
			);

			$count_completed = count( $query->query( $args ) );

			$url_completed = add_query_arg( $args, admin_url( 'admin.php?page=gh_contacts' ) );


			$args = array(
				'report' => array(
					'funnel' => intval( $_REQUEST['funnel'] ),
					'step'   => $step->ID,
					'status' => 'waiting',
					'start'  => $this->start,
					'end'    => $this->end,
				)
			);

			$count_waiting = count( $query->query( $args ) );

			$url_waiting  = add_query_arg( $args, admin_url( 'admin.php?page=gh_contacts' ) );

			$title = ($i + 1 ) . '. ' . $step->get_title();


			$data[] =[
				'step' => $title,
				'completed' =>  html()->wrap( $count_completed, 'a', [
					'href'  => $url_completed,
					'class' => 'number-total'
				] ),
				'waiting' => html()->wrap( $count_waiting, 'a', [
					'href'  => $url_waiting,
					'class' => 'number-total'
				] )
			];

		}


		return  $data;


		}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}

}