<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\DB\DB;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Preferences;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\isset_not_empty;

class Chart_Contacts_By_Optin_Status extends Base_Chart_Report {

	protected function get_type() {
		return 'doughnut';
	}

	protected function get_datasets() {

		$data = $this->get_optin_status();

		return [
			'labels'   => $data[ 'label' ],
			'datasets' => [
				[
					'data'            => $data[ 'data' ],
					'backgroundColor' => $data[ 'color' ]
				]
			]
		];

	}

	protected function get_options() {
		return [
			'responsive' => true,
			'tooltips'   => [
				'backgroundColor' => '#FFF',
				'bodyFontColor'   => '#000',
				'borderColor'     => '#727272',
				'borderWidth'     => 2,
				'titleFontColor'  => '#000'
			]
		];
	}


	protected function get_optin_status() {

		$rows = get_db( 'contacts' )->query( [
			'date_query' => [
				'after'  => date( 'Y-m-d H:i:s', $this->start ),
				'before' => date( 'Y-m-d H:i:s', $this->end ),
			],

		], false );

		$values = wp_list_pluck( $rows, 'optin_status' );
		$counts = array_count_values( $values );

		$data  = [];
		$label = [];
		$color = [];

		// normalize data
		foreach ( $counts as $key => $datum ) {
			$normalized = $this->normalize_datum( $key, $datum );
			$label []    = $normalized [ 'label' ];
			$data[]    = $normalized [ 'data' ];
			$color[]    = $normalized [ 'color' ];

		}

		return [
			'label' => $label,
			'data'  => $data,
			'color' => $color
		];

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
		switch ( $item_key ) {
			default:
			case Preferences::UNCONFIRMED:
				$label = __( 'Unconfirmed', 'groundhogg' );
				break;
			case Preferences::CONFIRMED:
				$label = __( 'Confirmed', 'groundhogg' );
				break;
			case Preferences::HARD_BOUNCE:
				$label = __( 'Bounced', 'groundhogg' );
				break;
			case Preferences::SPAM:
				$label = __( 'Spam', 'groundhogg' );
				break;
			case Preferences::UNSUBSCRIBED:
				$label = __( 'Unsubscribed', 'groundhogg' );
				break;
		}

		return [
			'label' => $label,
			'data'  => $item_data,
//			'url'  => admin_url( 'admin.php?page=gh_contacts&optin_status=' . $item_key ),
			'color' => $this->get_random_color()
		];
	}

}