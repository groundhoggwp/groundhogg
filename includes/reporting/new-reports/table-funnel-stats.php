<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\key_to_words;
use function Groundhogg\percentage;

class Table_Funnel_Stats extends Base_Table_Report {


	public function get_label() {
		return [
			__( 'Steps', 'groundhogg' ),
			__( 'Completed', 'groundhogg' ),
			__( 'Waiting', 'groundhogg' ),
		];
	}

	/**
	 * @return array|mixed
	 */
	protected function get_table_data() {
		//get list of benchmark
		$funnel = $this->get_funnel();

		$steps  = $funnel->get_steps();

		$data = [];

		foreach ( $steps as $i => $step ) {

			$query = new Contact_Query();

			$args = array(
				'report' => array(
					'funnel' => $funnel->get_id(),
					'step'   => $step->get_id(),
					'status' => Event::COMPLETE,
					'start'  => $this->start,
					'end'    => $this->end,
				)
			);

			$count_completed = count( $query->query( $args ) );

			$url_completed = admin_page_url( 'gh_contacts', $args );

			$args = [
				'report' => [
					'funnel' => $funnel->get_id(),
					'step'   => $step->get_id(),
					'status' => Event::WAITING,
				]
			];

			$count_waiting = count( $query->query( $args ) );

			$url_waiting = admin_page_url( 'gh_contacts', $args );

			$img = html()->e( 'img', [
				'src'   => $step->icon(),
				'class' => implode( ' ', [
					'step-icon',
					$step->get_group()
				] )
			] );

			$edit = html()->e( 'a', [
				'class'  => 'step-title',
				'href'   => admin_page_url( 'gh_funnels', [
					'action' => 'edit',
					'funnel' => $step->get_funnel_id()
 				], $step->ID ),
				'target' => '_blank'
			], $step->get_title() );

			$title = sprintf( '%s%s<br/><span class="step-type pill %s">%s</span>', $img, $edit, $step->get_group(), $step->get_type_name() );

			$data[] = [
				'step'      => $title,
				'completed' => html()->wrap( _nf( $count_completed ), 'a', [
					'href'  => $url_completed,
					'class' => 'number-total'
				] ),
				'waiting'   => html()->wrap( _nf( $count_waiting ), 'a', [
					'href'  => $url_waiting,
					'class' => 'number-total'
				] )
			];

		}


		return $data;

	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}

}
