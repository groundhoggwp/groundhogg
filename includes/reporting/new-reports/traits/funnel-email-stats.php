<?php

namespace Groundhogg\Reporting\New_Reports\Traits;

use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use function Groundhogg\find_object;
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
		              ->setGroupby( 'activity_type' )
		              ->where()
		              ->in( 'activity_type', [ Activity::EMAIL_OPENED, Activity::EMAIL_CLICKED, Activity::UNSUBSCRIBED ] )
		              ->equals( 'funnel_id', $this->get_funnel_id() )
		              ->equals( 'step_id', $this->get_step_id() )
		              ->equals( 'email_id', $this->get_email_id() )
		              ->lessThanEqualTo( 'timestamp', $this->end )
		              ->greaterThanEqualTo( 'timestamp', $this->start );

		$results = $activityQuery->get_results();

		$opens        = get_array_var( find_object( $results, [ 'activity_type' => Activity::EMAIL_OPENED ] ), 'total', 0 );
		$clicks       = get_array_var( find_object( $results, [ 'activity_type' => Activity::EMAIL_CLICKED ] ), 'total', 0 );
		$unsubscribed = get_array_var( find_object( $results, [ 'activity_type' => Activity::UNSUBSCRIBED ] ), 'total', 0 );

		return [
			'sent'         => $sent,
			'opened'       => $opens,
			'clicked'      => $clicks,
			'unsubscribed' => $unsubscribed
		];
	}

}
