<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use function Groundhogg\_nf;
use function Groundhogg\array_find;
use function Groundhogg\contact_filters_link;
use function Groundhogg\format_number_with_percentage;
use function Groundhogg\is_good_fair_or_poor;
use function Groundhogg\percentage;
use function Groundhogg\report_link;

class Table_Top_Performing_Broadcasts extends Base_Funnel_Email_Performance_Table_Report {

	protected function get_table_data() {

		$sentQuery = new Table_Query( 'events' );
		$sentQuery->setSelect( 'email_id', 'step_id', [ 'COUNT(ID)', 'sent' ] )
		          ->setGroupby( 'email_id', 'step_id' )
		          ->setOrderby( 'sent' )
		          ->where( 'status', Event::COMPLETE )
		          ->lessThanEqualTo( 'time', $this->end )
		          ->greaterThanEqualTo( 'time', $this->start )
		          ->equals( 'event_type', Event::BROADCAST )
		          ->equals( 'funnel_id', Broadcast::FUNNEL_ID );

		$activityQuery = new Table_Query( 'activity' );
		$activityQuery->setSelect( 'email_id', 'step_id', 'activity_type', [ 'COUNT(ID)', 'total' ] )
		              ->setGroupby( 'email_id', 'step_id', 'activity_type' )
		              ->setOrderby( 'total' )
		              ->whereIn( 'activity_type', [
			              Activity::EMAIL_OPENED,
			              Activity::EMAIL_CLICKED,
			              Activity::UNSUBSCRIBED,
			              Activity::SMS_CLICKED
		              ] )
		              ->lessThanEqualTo( 'timestamp', $this->end )
		              ->greaterThanEqualTo( 'timestamp', $this->start )
		              ->equals( 'funnel_id', Broadcast::FUNNEL_ID );

		$sentResults     = $sentQuery->get_results();
		$activityResults = $activityQuery->get_results();

		$list = [];

		foreach ( $sentResults as $sentResult ) {

			$email_id     = absint( $sentResult->email_id );
			$broadcast_id = absint( $sentResult->step_id );

			$email     = new Email( $email_id );
			$broadcast = new Broadcast( $broadcast_id );

			if ( ! $email->exists() || ! $broadcast->exists() ) {
				continue;
			}

			$sent = absint( $sentResult->sent );

			if ( ! $broadcast->is_sms() ) {
				$openedResult = array_find( $activityResults, function ( $result ) use ( $email_id, $broadcast_id ) {
					return $result->activity_type === Activity::EMAIL_OPENED
					       && $result->email_id == $email_id
					       && $result->step_id == $broadcast_id;
				} );

				$opened = $openedResult ? absint( $openedResult->total ) : 0;
			} else {
				$opened = 0;
			}

			$clickedResult = array_find( $activityResults, function ( $result ) use ( $email_id, $broadcast_id ) {
				return ( $result->activity_type === Activity::EMAIL_CLICKED || $result->activity_type === Activity::SMS_CLICKED )
				       && $result->email_id == $email_id
				       && $result->step_id == $broadcast_id;
			} );

			$clicked = $clickedResult ? absint( $clickedResult->total ) : 0;

			$unsubedResult = array_find( $activityResults, function ( $result ) use ( $email_id, $broadcast_id ) {
				return $result->activity_type === Activity::UNSUBSCRIBED
				       && $result->email_id == $email_id
				       && $result->step_id == $broadcast_id;
			} );

			$unsubscribed = $unsubedResult ? absint( $unsubedResult->total ) : 0;

			$list[] = [
				'label'        => report_link( $broadcast->get_title(), [
					'tab'       => 'broadcasts',
					'broadcast' => $broadcast_id
				] ),
				'sent'         => contact_filters_link( _nf( $sent ), [
					[
						[
							'type'         => 'broadcast_received',
							'broadcast_id' => $broadcast_id,
						]
					]
				], $sent ),
				'opened'       => $broadcast->is_sms() ? 'N/A' : contact_filters_link( format_number_with_percentage( $opened, $sent ), [
					[
						[
							'type'         => 'broadcast_opened',
							'broadcast_id' => $broadcast_id,
							'count'         => 1,
							'count_compare' => 'greater_than_or_equal_to',
						]
					]
				], $opened ),
				'clicked'      => contact_filters_link( format_number_with_percentage( $clicked, $broadcast->is_email() ? $opened : $sent ), [
					[
						[
							'type'         => 'broadcast_link_clicked',
							'broadcast_id' => $broadcast_id,
							'count'         => 1,
							'count_compare' => 'greater_than_or_equal_to',
						]
					]
				], $clicked ),
				'unsubscribes' => contact_filters_link( format_number_with_percentage( $unsubscribed, $sent ), [
					[
						[
							'type'      => 'unsubscribed',
							'email_id'  => $email_id,
							'step_id'   => $broadcast_id,
							'funnel_id' => Broadcast::FUNNEL_ID,
						]
					]
				], $unsubscribed ),
				'orderby'      => [
					// The value to compare, and the secondary column to compare
					$broadcast->get_send_time(),
					$sent,
					$opened,
					$clicked,
					$unsubscribed,
				],
				'cellClasses'  => [
					// One of Good/Fair/Poor
					'', // Email title
					'', // sent
					$broadcast->is_sms() ? '' : is_good_fair_or_poor( percentage( $sent, $opened ), 40, 30, 20, 10 ), // Opens
					is_good_fair_or_poor( percentage( $broadcast->is_sms() ? $sent : $opened, $clicked ), 30, 20, 10, 5 ), // clicks
					is_good_fair_or_poor( 100 - percentage( $sent, $unsubscribed ), 99, 98, 97, 95 ), // unsubscribed
				]
			];


		}

		return $list;
	}

	protected function should_include( $sent, $opened, $clicked ) {
		// TODO: Implement should_include() method.
	}
}
