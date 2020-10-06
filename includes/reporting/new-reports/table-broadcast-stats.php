<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Event;
use Groundhogg\Plugin;
use function Groundhogg\_nf;
use function Groundhogg\admin_page_url;
use function Groundhogg\convert_to_local_time;
use function Groundhogg\get_array_var;
use function Groundhogg\get_date_time_format;
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

		if ( ! $id ) {

			$broadcasts = get_db( 'broadcasts' )->query( [
				'status'  => 'sent',
				'orderby' => 'send_time',
				'order'   => 'desc',
				'limit'   => 1
			] );

			if ( ! empty( $broadcasts ) ) {
				$id = absint( array_shift( $broadcasts )->ID );
			}
		}

		if ( get_array_var( $this->request_data, 'ddl_broadcasts' ) ) {
			$id = absint( get_array_var( $this->request_data, 'ddl_broadcasts' ) );
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
				'data'  => $title
			],
			[
				'label' => __( 'Sent', 'groundhogg' ),
				'data'  => date_i18n( get_date_time_format(), convert_to_local_time( $broadcast->get_send_time() ) ),
			],
			[
				'label' => __( 'Total Delivered', 'groundhogg' ),
				'data'  => _nf( $stats[ 'sent' ] )
			],
			[
				'label'      => __( 'Opens', 'groundhogg' ),
				'data'       => $stats[ 'opened' ],
				'percentage' => percentage( $stats[ 'sent' ], $stats[ 'opened' ] ) . '%'
			],
			[
				'label' => __( 'Total Clicks', 'groundhogg' ),
				'data'  => $stats[ 'all_clicks' ]
			],
			[
				'label'      => __( 'Unique Clicks', 'groundhogg' ),
				'data'       => _nf( $stats[ 'clicked' ] ),
				'percentage' => percentage( $stats[ 'sent' ], $stats[ 'clicked' ] ) . '%'
			],
			[
				'label' => __( 'Click Thru Rate', 'groundhogg' ),
				'data'  => percentage( $stats[ 'opened' ], $stats[ 'clicked' ] ) . '%'
			],
			[
				'label' => __( 'Unopened', 'groundhogg' ),
				'data'  => _nf( $stats[ 'unopened' ] ) ,
				'percentage' =>  percentage( $stats[ 'sent' ], $stats[ 'unopened' ] ) . '%'
			],
			[
				'label' => __( 'Unsubscribed', 'groundhogg' ),
				'data'  => _nf( $stats[ 'unsubscribed' ] ) ,
				'percentage' =>   percentage( $stats[ 'sent' ], $stats[ 'unsubscribed' ] ) . '%'
			],
			[
				'object'  => $broadcast
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