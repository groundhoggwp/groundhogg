<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\Table_Query;
use function Groundhogg\_nf;
use function Groundhogg\contact_filters_link;
use function Groundhogg\html;

/**
 * Only used in single view
 */
class Table_Email_Links_Clicked extends Base_Table_Report {

	/**
	 * @return array|mixed
	 */
	public function get_label() {
		return [
			__( 'Link', 'groundhogg' ),
			__( 'Uniques', 'groundhogg' ),
			__( 'Clicks', 'groundhogg' ),
		];
	}

	protected function contact_filters( $url ) {
		return [
			[
				array_filter( [
					'type'       => 'email_link_clicked',
					'funnel_id'  => $this->get_funnel_id(),
					'step_id'    => $this->get_step_id(),
					'email_id'   => $this->get_email_id(),
					'link'       => $url,
					'date_range' => 'between',
					'after'      => $this->startDate->ymd(),
					'before'     => $this->endDate->ymd(),
				] )
			]
		];
	}

	protected function get_table_data() {

		$activityQuery = new Table_Query( 'activity' );

		$urlCol = 'SUBSTRING_INDEX(referer, \'?\', 1)';
		$activityQuery->add_safe_column( $urlCol );

		$activityQuery
			->setSelect( [ $urlCol, 'url' ], [ 'COUNT(ID)', 'clicks' ], [ 'COUNT(DISTINCT(contact_id))', 'contacts' ] )
			->setOrderby( 'clicks' )
			->setGroupby( 'url' )
			->whereIn( 'activity_type', [
				Activity::EMAIL_CLICKED,
				Activity::SMS_CLICKED
			] );

		if ( $this->get_broadcast_id() ) {
			// No time constraint if a broadcast
			$activityQuery->where( 'funnel_id', Broadcast::FUNNEL_ID )
			              ->equals( 'step_id', $this->get_broadcast_id() );
		} else {
			// Time constraint if funnel
			$activityQuery->where()
			              ->greaterThanEqualTo( 'timestamp', $this->start )
			              ->lessThanEqualTo( 'timestamp', $this->end );

			$activityQuery->where( 'funnel_id', $this->get_funnel_id() );
			$activityQuery->where( 'step_id', $this->get_step_id() );
			$activityQuery->where( 'email_id', $this->get_email_id() );
		}

		$linkResults = $activityQuery->get_results();

		$data = [];

		foreach ( $linkResults as $result ) {

			$clicks   = absint( $result->clicks );
			$contacts = absint( $result->contacts );
			$url      = $result->url;

			$data[] = [
				'label'    => html()->e( 'a', [
					'href'   => $url,
					'target' => '_blank'
				], preg_replace( '@https?://(?:www\.)?@', '', $url ) ),
				'contacts' => contact_filters_link( _nf( $contacts ), $this->contact_filters( $url ), $contacts ),
				'clicks'   => _nf( $clicks ),
				'orderby'  => [
					false,
					$contacts,
					$clicks
				]
			];
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
		return $item_data;
	}


}
