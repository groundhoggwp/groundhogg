<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\DB\Query\Table_Query;

class Num_Broadcasts_Sent extends Base_Quick_Stat {

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	protected function query( $start, $end ) {

		$broadcastQuery = new Table_Query( 'broadcasts' );
		$broadcastQuery->where()->between( 'send_time', $start, $end );

		// filter results by campaign
		if ( $this->get_campaign_id() ) {
			// Events
			$join = $broadcastQuery->addJoin( 'LEFT', 'object_relationships' );
			$join->onColumn( 'primary_object_id', 'ID' )
			     ->equals( $join->alias . '.primary_object_type', 'broadcast' );

			$broadcastQuery->where()->equals( 'secondary_object_id', $this->get_campaign_id() )
			          ->equals( 'secondary_object_type', 'campaign' );
		}

		return $broadcastQuery->count();
	}
}
