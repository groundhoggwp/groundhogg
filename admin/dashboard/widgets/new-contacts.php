<?php

namespace Groundhogg\Admin\Dashboard\Widgets;

use Groundhogg\Contact_Query;
use function Groundhogg\get_db;
use function Groundhogg\percentage;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class New_Contacts extends Time_Graph {

	public function get_id() {
		return 'new_contacts';
	}

	public function get_name() {
		return __( 'New Contacts', 'groundhogg' );
	}

	/**
	 * Any additional information needed for the widget.
	 *
	 * @return void
	 */
	protected function extra_widget_info() {
		$html = Plugin::$instance->utils->html;

		$total_new_contacts = array_sum( wp_list_pluck( $this->dataset[0]['data'], 1 ) );
		$total_contacts     = get_db( 'contacts' )->count( [] );

		$date_query = [
			'date_query' => [
				'before' => date( 'Y-m-d H:i:s', Plugin::$instance->reporting->get_end_time() ),
				'after'  => date( 'Y-m-d H:i:s', Plugin::$instance->reporting->get_start_time() )
			]
		];

		$html->list_table(
			[ 'class' => 'new_contacts' ],
			[
				__( 'Total Contacts', 'groundhogg' ),
				__( 'New Contacts', 'groundhogg' ),
				__( 'Growth (%)', 'groundhogg' ),
			],
			[
				[
					$html->wrap( $total_contacts, 'span', [ 'class' => 'number-total' ] ),
					$html->wrap( $total_new_contacts, 'a', [
						'class' => 'number-total',
						'href'  => add_query_arg( $date_query, admin_url( 'admin.php?page=gh_contacts' ) )
					] ),
					$html->wrap( percentage( $total_contacts, $total_new_contacts ), 'span', [ 'class' => 'number-total' ] ),
				]
			],
			false
		);
	}

	/**
	 * Return several reports used rather than just 1.
	 *
	 * @return string[]
	 */
	protected function get_report_ids() {
		return [
			'new_contacts'
		];
	}

	/**
	 * @param $datum
	 *
	 * @return int
	 */
	public function get_time_from_datum( $datum ) {
		return strtotime( $datum->date_created );
	}
}