<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\DB\Query;
use Groundhogg\Preferences;

class Chart_Contacts_By_Optin_Status extends Base_Doughnut_Chart_Report {

	protected function get_chart_data() {

		$query = new Query( 'contacts' );
		$query->setSelect( 'optin_status', [ 'COUNT(ID)', 'total' ] );
		$query->where()->greaterThanEqualTo( 'date_created', $this->startDate->ymdhis() );
		$query->where()->lessThanEqualTo( 'date_created', $this->endDate->ymdhis() );
		$query->setGroupby( 'optin_status' );
		$query->setOrderby( 'total' );

		$results = $query->get_results();

		$data  = [];
		$label = [];
		$color = [];

		// normalize data
		foreach ( $results as $result ) {
			$normalized = $this->normalize_datum( absint( $result->optin_status ), absint( $result->total ) );
			$label[]    = $normalized['label'];
			$data[]     = $normalized['data'];
			$color[]    = $normalized['color'];
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
