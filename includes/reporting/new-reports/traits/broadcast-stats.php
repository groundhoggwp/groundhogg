<?php

namespace Groundhogg\Reporting\New_Reports\Traits;

use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use function Groundhogg\find_object;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\html;

trait Broadcast_Stats {

	public function get_broadcast_stats() {

		$broadcast = $this->get_broadcast();

		if ( ! $broadcast ) {
			return [
				'sent'         => 0,
				'opened'       => 0,
				'clicked'      => 0,
				'unsubscribed' => 0
			];
		}

		$eventQuery = new Table_Query( 'events' );
		$eventQuery->where()
		           ->equals( 'event_type', Event::BROADCAST )
		           ->equals( 'funnel_id', Broadcast::FUNNEL_ID )
		           ->equals( 'step_id', $broadcast->get_id() );

		$sent  = $eventQuery->count();
		$opens = 0;

		$activityQuery = new Table_Query( 'activity' );
		$activityQuery->setGroupby( 'activity_type' );

		if ( $broadcast->is_sms() ) {
			$activityQuery->setSelect( 'activity_type', [ 'COUNT(ID)', 'total' ] )
			              ->where()
			              ->in( 'activity_type', [ Activity::SMS_CLICKED, Activity::UNSUBSCRIBED ] )
			              ->equals( 'funnel_id', Broadcast::FUNNEL_ID )
			              ->equals( 'step_id', $broadcast->get_id() );

			$results = $activityQuery->get_results();
			$clicked = get_array_var( find_object( $results, [ 'activity_type' => Activity::SMS_CLICKED ] ), 'total', 0 );
		} else {
			$activityQuery->setSelect( 'activity_type', [ 'COUNT(ID)', 'total' ] )
			              ->where()
			              ->in( 'activity_type', [ Activity::EMAIL_OPENED, Activity::EMAIL_CLICKED, Activity::UNSUBSCRIBED ] )
			              ->equals( 'funnel_id', Broadcast::FUNNEL_ID )
			              ->equals( 'step_id', $broadcast->get_id() );

			$results = $activityQuery->get_results();

			$opens   = get_array_var( find_object( $results, [ 'activity_type' => Activity::EMAIL_OPENED ] ), 'total', 0 );
			$clicked = get_array_var( find_object( $results, [ 'activity_type' => Activity::EMAIL_CLICKED ] ), 'total', 0 );

		}

		$unsubscribed = get_array_var( find_object( $results, [ 'activity_type' => Activity::UNSUBSCRIBED ] ), 'total', 0 );

		return [
			'sent'         => $sent,
			'opened'       => $opens,
			'clicked'      => $clicked,
			'unsubscribed' => $unsubscribed
		];
	}

	protected $broadcast;

	/**
	 * @return bool|Broadcast
	 */
	public function get_broadcast() {

		if ( $this->broadcast ) {
			return $this->broadcast;
		}

		if ( $this->get_broadcast_id() ) {
			$this->broadcast = new Broadcast( $this->get_broadcast_id() );

			return $this->broadcast;
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

		$last_broadcast     = array_shift( $all_broadcasts );
		$last_broadcast_id  = absint( $last_broadcast->ID );
		$this->broadcast_id = $last_broadcast_id;
		$this->broadcast    = new Broadcast( $this->broadcast_id );

		return $this->broadcast;
	}

	/**
	 * Text to display if no data is available...
	 */
	protected function no_data_notice() {
		return html()->e( 'div', [ 'class' => 'notice notice-warning' ], [
			html()->e( 'p', [], __( 'Send a broadcast first.', 'groundhogg' ) )
		] );
	}


}
