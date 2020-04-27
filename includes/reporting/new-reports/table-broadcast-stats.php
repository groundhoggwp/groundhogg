<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Event;
use Groundhogg\Plugin;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\key_to_words;
use function Groundhogg\percentage;

class Table_Broadcast_Stats extends Base_Table_Report {

	/**
	 * @return mixed
	 */
	protected function get_broadcast_id() {
		$id = absint( get_array_var( get_request_var( 'data', [] ), 'broadcast_id' ) );

		if ( ! $id ){

			$broadcasts = get_db( 'broadcasts' )->query( [
				'status'  => 'sent',
				'orderby' => 'send_time',
				'order'   => 'desc',
				'limit'   => 1
			] );

			if ( ! empty( $broadcasts) ){
				$id = absint( array_shift( $broadcasts )->ID );
			}
		}

		return $id;
	}

	protected function get_table_data() {

		$broadcast = new Broadcast( $this->get_broadcast_id() );
		$stats     = $broadcast->get_report_data();

		$title = $broadcast->is_email() ? $broadcast->get_object()->get_subject_line() : $broadcast->get_title();

		return [
			[
				'label' => __( 'Subject', 'groundhogg' ),
				'data'  => html()->wrap( $title, 'a', [
					'href'  => admin_page_url( 'gh_reporting', [ 'tab' => 'broadcasts', 'broadcast' => $broadcast->get_id() ] ),
					'title' => $title,
					'class' => 'number-total'
				] )
			],
			[
				'label' => __( 'Total Delivered', 'groundhogg' ),
				'data'  => html()->wrap( $stats['sent'], 'a', [
					'href'  => add_query_arg(
						[
							'report' => [
								'type'   => Event::BROADCAST,
								'step'   => $broadcast->get_id(),
								'status' => Event::COMPLETE
							]
						],
						admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
					),
					'class' => 'number-total'
				] )
			],
			[
				'label' => __( 'Opens', 'groundhogg' ),
				'data'  => html()->wrap( $stats['opened'] . ' (' . percentage( $stats['sent'], $stats['opened'] ) . '%)', 'a', [
					'href'  => add_query_arg(
						[
							'activity' => [
								'activity_type' => Activity::EMAIL_OPENED,
								'step_id'       => $broadcast->get_id(),
								'funnel_id'     => $broadcast->get_funnel_id()
							]
						],
						admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
					),
					'class' => 'number-total'
				] )
			],
			[
				'label' => __( 'Clicks', 'groundhogg' ),
				'data'  => html()->wrap( $stats['clicked'] . ' (' . percentage( $stats['sent'], $stats['clicked'] ) . '%)', 'a', [
					'href'  => add_query_arg(
						[
							'activity' => [
								'activity_type' => Activity::EMAIL_CLICKED,
								'step_id'       => $broadcast->get_id(),
								'funnel_id'     => $broadcast->get_funnel_id()
							]
						],
						admin_url( sprintf( 'admin.php?page=gh_contacts' ) )
					),
					'class' => 'number-total'
				] )
			],
			[
				'label' => __( 'Click Thru Rate', 'groundhogg' ),
				'data'  => percentage( $stats['opened'], $stats['clicked'] ) . '%'
			],
			[
				'label' => __( 'Unopened', 'groundhogg' ),
				'data'  => $stats['unopened'] . ' (' . percentage( $stats['sent'], $stats['unopened'] ) . '%)'
			],

		];

	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}

	function get_label() {
		return [];
	}
}