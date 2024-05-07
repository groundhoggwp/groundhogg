<?php

namespace Groundhogg\Reporting\New_Reports\Traits;

use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use function Groundhogg\get_array_var;

trait Funnel_Email_Stats {

	public function get_funnel_email_stats() {

		$eventQuery = new Table_Query( 'events' );
		$eventQuery->where()
		           ->equals( 'event_type', Event::FUNNEL )
		           ->equals( 'funnel_id', $this->get_funnel_id() )
		           ->equals( 'step_id', $this->get_step_id() )
		           ->equals( 'email_id', $this->get_email_id() )
		           ->lessThanEqualTo( 'time', $this->end )
		           ->greaterThanEqualTo( 'time', $this->start );

		$sent = $eventQuery->count();

		$activityQuery = new Table_Query( 'activity' );
		$activityQuery->setSelect( 'activity_type', [ 'COUNT(ID)', 'total' ] )
		              ->where()
		              ->in( 'activity_type', [ Activity::EMAIL_OPENED, Activity::EMAIL_CLICKED, Activity::UNSUBSCRIBED ] )
		              ->equals( 'funnel_id', $this->get_funnel_id() )
		              ->equals( 'step_id', $this->get_step_id() )
		              ->equals( 'email_id', $this->get_email_id() )
		              ->lessThanEqualTo( 'timestamp', $this->end )
		              ->greaterThanEqualTo( 'timestamp', $this->start );

		$results = $activityQuery->get_results();

		$opened       = absint( get_array_var( wp_filter_object_list( $results, [ 'activity_type' => Activity::EMAIL_OPENED ], 'and', 'total' ), 0, 0 ) );
		$clicked      = absint( get_array_var( wp_filter_object_list( $results, [ 'activity_type' => Activity::EMAIL_CLICKED ], 'and', 'total' ), 0, 0 ) );
		$unsubscribed = absint( get_array_var( wp_filter_object_list( $results, [ 'activity_type' => Activity::UNSUBSCRIBED ], 'and', 'total' ), 0, 0 ) );

		return [
			'sent'         => $sent,
			'opened'       => $opened,
			'clicked'      => $clicked,
			'unsubscribed' => $unsubscribed
		];
	}

}
