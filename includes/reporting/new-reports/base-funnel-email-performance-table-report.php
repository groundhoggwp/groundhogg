<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Email;
use Groundhogg\Event;
use Groundhogg\Step;
use function Groundhogg\_nf;
use function Groundhogg\array_find;
use function Groundhogg\contact_filters_link;
use function Groundhogg\format_number_with_percentage;
use function Groundhogg\is_good_fair_or_poor;
use function Groundhogg\percentage;
use function Groundhogg\report_link;

abstract class Base_Funnel_Email_Performance_Table_Report extends Base_Table_Report {

	protected $orderby = 1;

	public function get_label() {
		return [
			__( 'Emails', 'groundhogg' ),
//			__( 'Ored', 'groundhogg' ),
			__( 'Sent', 'groundhogg' ),
			__( 'Opens', 'groundhogg' ),
			__( 'Clicks', 'groundhogg' ),
			__( 'Unsubs', 'groundhogg' ),
		];
	}

	/**
	 * Whether this email should be included...
	 *
	 * @param $sent
	 * @param $opened
	 * @param $clicked
	 *
	 * @return mixed
	 */
	abstract protected function should_include( $sent, $opened, $clicked );

	/**
	 * Get the table data
	 *
	 * @return array|mixed
	 */
	protected function get_table_data() {

		$sentQuery = new Table_Query( 'events' );
		$sentQuery->setSelect( 'email_id', 'step_id', [ 'COUNT(ID)', 'sent' ] )
		          ->setGroupby( 'email_id', 'step_id' )
		          ->setOrderby( 'sent' )
		          ->where( 'status', Event::COMPLETE )
		          ->lessThanEqualTo( 'time', $this->end )
		          ->greaterThanEqualTo( 'time', $this->start )
		          ->equals( 'event_type', Event::FUNNEL );

		$activityQuery = new Table_Query( 'activity' );
		$activityQuery->setSelect( 'email_id', 'step_id', 'activity_type', [ 'COUNT(ID)', 'total' ] )
		              ->setGroupby( 'email_id', 'step_id', 'activity_type' )
		              ->setOrderby( 'total' )
		              ->whereIn( 'activity_type', [
			              Activity::EMAIL_OPENED,
			              Activity::EMAIL_CLICKED,
			              Activity::UNSUBSCRIBED
		              ] )
		              ->lessThanEqualTo( 'timestamp', $this->end )
		              ->greaterThanEqualTo( 'timestamp', $this->start )
		              ->greaterThan( 'funnel_id', Broadcast::FUNNEL_ID );


		if ( $this->get_funnel_id() ) {
			$sentQuery->where( 'funnel_id', $this->get_funnel_id() );
			$activityQuery->where( 'funnel_id', $this->get_funnel_id() );
		}

		$sentResults     = $sentQuery->get_results();
		$activityResults = $activityQuery->get_results();

		$list = [];

		foreach ( $sentResults as $sentResult ) {

			$email_id = absint( $sentResult->email_id );
			$step_id  = absint( $sentResult->step_id );

			$email = new Email( $email_id );
			$step = new Step( $step_id );

			if ( ! $email->exists() || ! $step->exists() ) {
				continue;
			}

			$sent = absint( $sentResult->sent );

			$openedResult = array_find( $activityResults, function ( $result ) use ( $email_id, $step_id ) {
				return $result->activity_type === Activity::EMAIL_OPENED
				       && $result->email_id == $email_id
				       && $result->step_id == $step_id;
			} );

			$opened = $openedResult ? absint( $openedResult->total ) : 0;

			$clickedResult = array_find( $activityResults, function ( $result ) use ( $email_id, $step_id ) {
				return $result->activity_type === Activity::EMAIL_CLICKED
				       && $result->email_id == $email_id
				       && $result->step_id == $step_id;
			} );

			$clicked = $clickedResult ? absint( $clickedResult->total ) : 0;

			$unsubedResult = array_find( $activityResults, function ( $result ) use ( $email_id, $step_id ) {
				return $result->activity_type === Activity::UNSUBSCRIBED
				       && $result->email_id == $email_id
				       && $result->step_id == $step_id;
			} );

			$unsubscribed = $unsubedResult ? absint( $unsubedResult->total ) : 0;

			if ( $this->should_include( $sent, $opened, $clicked ) ) {
				$list[] = [
					'label'   => report_link( $email->get_title(), [
						'tab'  => 'funnels',
						'step' => $step_id
					] ),
//					'order'        => $step->get_order(),
					'sent'    => contact_filters_link( _nf( $sent ), [
						// Group
						[
							// Filter
							[
								'type'       => 'email_received',
								'email_id'   => $email_id,
								'step_id'    => $step_id,
								'date_range' => 'between',
								'before'     => $this->endDate->ymd(),
								'after'      => $this->startDate->ymd()
							]
						]
					], $sent ),
					'opened'  => contact_filters_link( format_number_with_percentage( $opened, $sent ), [
						[
							[
								'type'       => 'email_opened',
								'email_id'   => $email_id,
								'step_id'    => $step_id,
								'date_range' => 'between',
								'before'     => $this->endDate->ymd(),
								'after'      => $this->startDate->ymd()
							]
						]
					], $opened ),
					'clicked' => contact_filters_link( format_number_with_percentage( $clicked, $opened ), [
						[
							[
								'type'       => 'email_link_clicked',
								'email_id'   => $email_id,
								'step_id'    => $step_id,
								'date_range' => 'between',
								'before'     => $this->endDate->ymd(),
								'after'      => $this->startDate->ymd()
							]
						]
					], $clicked ),
					'unsubscribes' => contact_filters_link( format_number_with_percentage( $unsubscribed, $sent ), [
						[
							[
								'type'       => 'unsubscribed',
								'email_id'   => $email_id,
								'step_id'    => $step_id,
								'date_range' => 'between',
								'before'     => $this->endDate->ymd(),
								'after'      => $this->startDate->ymd()
							]
						]
					], $unsubscribed ),
					'orderby'    => [
						// The value to compare, and the secondary column to compare
						$step->get_order(),
						$sent,
						$opened,
						$clicked,
						$unsubscribed,
					],
					'cellClasses'    => [
						// One of Good/Fair/Poor
						'', // Email title
						'', // sent
						is_good_fair_or_poor( percentage( $sent, $opened ), 40, 30, 20, 10 ), // Sent
						is_good_fair_or_poor( percentage( $opened, $clicked ), 30, 20, 10, 5 ), // clicks
						is_good_fair_or_poor( 100 - percentage( $sent, $unsubscribed ), 99, 98, 97, 95 ), // unsubscribed
					]
				];

			}

		}

		return $list;
	}

	protected function normalize_datum( $item_key, $item_data ) {
		// TODO: Implement normalize_datum() method.
	}
}
