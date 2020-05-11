<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\DB\DB;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\isset_not_empty;
use function Groundhogg\key_to_words;

class Chart_Last_Broadcast extends Base_Chart_Report {

	protected function get_type() {
		return 'doughnut';
	}

	protected function get_datasets() {

		$data = $this->get_last_broadcast_details();

		return [
			'labels'   => $data['label'],
			'datasets' => [
				[
					'data'            => $data['data'],
					'backgroundColor' => $data['color']
				]
			]
		];
	}

	protected function get_options() {
		return $this->get_pie_chart_options();
	}

	protected function get_broadcast_id() {
		return get_request_var( 'data' )['broadcast_id'];
	}

	protected function get_last_broadcast_details() {


		$broadcast = $this->get_broadcast();

		if ( $broadcast && $broadcast->exists() ) {

			$counts = $this->normalize_data( $broadcast->get_report_data() );


			$data  = [];
			$label = [];
			$color = [];

			// normalize data
			foreach ( $counts as $key => $datum ) {

				$label [] = $datum ['label'];
				$data[]   = $datum ['data'];
				$color[]  = $datum ['color'];

			}

			return [
				'label' => $label,
				'data'  => $data,
				'color' => $color
			];


		}

		return [];

	}

	/**
	 * @return bool|Broadcast
	 */
	public function get_broadcast() {

		if ( $this->get_broadcast_id() ) {
			return new Broadcast( $this->get_broadcast_id() );
		}

		$all_broadcasts = get_db( 'broadcasts' )->query( [
			'status'  => 'sent',
			'orderby' => 'send_time',
			'order'   => 'desc',
			'limit'   => 1
		] );

		if ( empty( $all_broadcasts ) ) {
			return false;
		}

		$last_broadcast    = array_shift( $all_broadcasts );
		$last_broadcast_id = absint( $last_broadcast->ID );

		return new Broadcast( $last_broadcast_id );
	}


	protected function normalize_data( $stats ) {

		if ( empty( $stats ) ) {
			return $stats;
		}

		/*
		* create array  of data ..
		*/
		$dataset = array();

		$dataset[] = array(
			'label' => _x( 'Opened', 'stats', 'groundhogg' ),
			'data'  => $stats['opened'] - $stats['clicked'],
			'url'   => add_query_arg(
				[
					'activity' => [
						'activity_type' => Activity::EMAIL_OPENED,
						'step_id'       => $stats['id'],
						'funnel_id'     => Broadcast::FUNNEL_ID
					]
				],
				admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
			),
			'color' => $this->get_random_color()
		);

		$dataset[] = array(
			'label' => _x( 'Clicked', 'stats', 'groundhogg' ),
			'data'  => $stats['clicked'],
			'url'   => add_query_arg(
				[
					'activity' => [
						'activity_type' => Activity::EMAIL_CLICKED,
						'step_id'       => $stats['id'],
						'funnel_id'     => Broadcast::FUNNEL_ID
					]
				],
				admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
			),
			'color' => $this->get_random_color()
		);

		$dataset[] = array(
			'label' => _x( 'Unopened', 'stats', 'groundhogg' ),
			'data'  => $stats['unopened'],
			'url'   => '#',
			'color' => $this->get_random_color()
		);

		return $dataset;
	}

}