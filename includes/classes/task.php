<?php

namespace Groundhogg\Classes;

use Groundhogg\Contact;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\get_array_var;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
use function Groundhogg\isset_not_empty;
use function Groundhogg\Ymd_His;

class Task extends Note {

	protected function get_db() {
		return get_db( 'tasks' );
	}

	/**
	 * @return bool
	 */
	public function complete() {
		return $this->update( [
			'date_completed' => Ymd_His()
		] );
	}

	public function incomplete() {
		return $this->update( [
			'date_completed' => ''
		] );
	}

	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	/**
	 * The task is for a contact
	 *
	 * @return bool
	 */
	public function is_for_contact() {
		return $this->object_type === 'contact';
	}

	/**
	 * If the task is complete
	 *
	 * @return bool
	 */
	public function is_complete() {
		return $this->get_date_completed()->getTimestamp() > 0;
	}

	/**
	 * @throws \Exception
	 * @return DateTimeHelper
	 */
	public function get_date_completed() {
		return new DateTimeHelper( strtotime( $this->date_completed ) );
	}

	/**
	 * @throws \Exception
	 * @return DateTimeHelper
	 */
	public function get_date_created() {
		return new DateTimeHelper( strtotime( $this->date_created ) );
	}

	/**
	 * If the task is overdue
	 *
	 * @return bool
	 */
	public function is_overdue() {
		return $this->get_due_date()->isPast() && ! $this->is_complete();
	}

	public function is_due_today() {
		return $this->get_due_date()->isToday() && ! $this->is_complete();
	}

	public function days_till_due() {
		$dueDate = $this->get_due_date();

		if ( $dueDate->isPast() || $dueDate->isToday() ) {
			return 0;
		}

		$diff = $dueDate->diff( new DateTimeHelper() );

		return $diff->days;
	}

	public function is_due_soon() {
		return $this->days_till_due() < 14;
	}

	/**
	 * @throws \Exception
	 * @return DateTimeHelper
	 */
	public function get_due_date() {
		return new DateTimeHelper( $this->due_date );
	}

	protected function sanitize_columns( $data = [] ) {

		foreach ( $data as $column => &$value ) {
			switch ( $column ) {
				case 'timestamp':
				case 'step_id':
				case 'funnel_id':
				case 'object_id':
				case 'user_id':
					$value = absint( $value );
					break;
				case 'summary':
				case 'type':
				default:
					$value = sanitize_text_field( $value );
					break;
				case 'content':
					$value = wp_kses_post( $value );
					break;
			}
		}

		return $data;
	}

	protected function add_task_activity( $type = '', $outcome = '', $note = '' ) {

		$activity = new Other_Activity;
		$activity->create( [
			'object_type'   => 'task',
			'object_id'     => $this->get_id(),
			'activity_type' => $type,
		] );

		$activity->add_meta( 'outcome', $outcome );
		$activity->add_meta( 'note', $note );
	}

	protected function get_task_activity() {
		$query = new Table_Query( 'other_activity' );
		$query->setOrderby( [ 'timestamp', 'DESC' ] )->where()
		      ->equals( 'object_id', $this->get_id() )
		      ->equals( 'object_type', 'task' );

		return $query->get_objects( Other_Activity::class );
	}

	/**
	 * Update wrapper
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public function update( $data = [] ) {

		$was_complete = $this->is_complete();

		// snooze a task
		if ( isset_not_empty( $data, 'snooze' ) ) {

			// number of days to snooze
			$snooze = absint( $data['snooze'] );

			// set to tomorrow if overdue
			if ( $this->is_overdue() ) {
				// set the snooze by the number of days
				$newDueDate = new DateTimeHelper( "+{$snooze} days" );
			} else {
				//
				$newDueDate = $this->get_due_date();
				$newDueDate->modify( "+{$snooze} days" );
			}

			// set the original time of day
			$newDueDate->modify( $this->get_due_date()->format( 'H:i:s' ) );
			// set the new due date in the data
			$data['due_date'] = $newDueDate->ymdhis();
			// Snooze is not a valid key
			unset( $data['snooze'] );
		}

		// Complete flag, but only if not previously completed
		if ( isset_not_empty( $data, 'complete' ) && ! $was_complete ) {
			$data['date_completed'] = Ymd_His();
			unset( $data['complete'] );
		}

		// Incomplete flag, but only if previously completed
		if ( isset_not_empty( $data, 'incomplete' ) && $was_complete ) {
			$data['date_completed'] = '';
			unset( $data['incomplete'] );
		}

		// handle completion activity if available
		if ( isset_not_empty( $data, 'date_completed' ) && ! $was_complete ) {

			$note    = wp_kses_post( get_array_var( $data, 'note' ) );
			$outcome = sanitize_text_field( get_array_var( $data, 'outcome' ) );

			if ( $note || $outcome ) {
				$this->add_task_activity( 'task_complete', $outcome, $note );
			}

			unset( $data['note'] );
			unset( $data['outcome'] );
		}

		// handle custom activity if available
		if ( isset_not_empty( $data, 'activity' ) ) {

			$type    = sanitize_key( get_array_var( $data, 'type' ) );
			$note    = wp_kses_post( get_array_var( $data, 'note' ) );
			$outcome = sanitize_text_field( get_array_var( $data, 'outcome' ) );

			if ( $note || $outcome ) {
				$this->add_task_activity( $type, $outcome, $note );
			}

			unset( $data['activity'] );
			unset( $data['type'] );
			unset( $data['note'] );
			unset( $data['outcome'] );
		}

		$updated = parent::update( $data );

		// If the task was not complete but was just completed following the update
		if ( ! $was_complete && $this->is_complete() ) {

			/**
			 * Whenever a task is completed
			 *
			 * @param Task $task
			 */
			do_action( 'groundhogg/task/completed', $this );

			/**
			 * A more specific action for when a task associated with a specific object type is completed
			 *
			 * @param Task $task
			 */
			do_action( "groundhogg/task/{$this->object_type}/completed", $this );
		}

		// Task was uncompleted
		if ( $was_complete && ! $this->is_complete() ) {

			/**
			 * Whenever a task is uncompleted
			 *
			 * @param Task $task
			 */
			do_action( 'groundhogg/task/uncompleted', $this );

			/**
			 * A more specific action for when a task associated with a specific object type is uncompleted
			 *
			 * @param Task $task
			 */
			do_action( "groundhogg/task/{$this->object_type}/uncompleted", $this );
		}

		return $updated;
	}

	public function get_associated_data() {
		$object = $this->get_associated_object();

		$associated = [
			'link' => '',
			'name' => $this->object_type . ' #' . $this->object_id,
			'type' => $this->object_type,
		];

		// Base functionality for contacts
		if ( is_a( $object, Contact::class ) ) {
			$associated['link'] = $object->admin_link() . '&_tab=tasks';
			$associated['name'] = empty( trim( $object->get_full_name() ) ) ? $object->get_email() : $object->get_full_name();
			$associated['type'] = $object->get_object_type();
			$associated['img']  = $object->get_profile_picture( 40 );
		}

		return apply_filters( 'groundhogg/task/associated_context', $associated, $object );
	}

	public function get_as_array() {

		$dueDate = $this->get_due_date();

		return array_merge( parent::get_as_array(), [
			'is_overdue'    => $this->is_overdue(),
			'is_complete'   => $this->is_complete(),
			'is_due_today'  => $this->is_due_today(),
			'days_till_due' => $this->days_till_due(),
			'due_timestamp' => $dueDate->getTimestamp(),
			'i18n'          => [
				'time_diff'      => human_time_diff( $this->timestamp, time() ),
				'due_in'         => human_time_diff( $dueDate->getTimestamp(), time() ),
				'completed'      => human_time_diff( strtotime( $this->date_completed ), time() ),
				'due_date'       => $dueDate->format( get_date_time_format() ),
				'completed_date' => $this->is_complete() ? $this->get_date_completed()->format( get_date_time_format() ) : '',
			],
			'associated'    => $this->get_associated_data(),
			'activity'      => $this->get_task_activity()
		] );
	}
}
