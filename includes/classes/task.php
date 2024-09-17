<?php

namespace Groundhogg\Classes;

use Groundhogg\Contact;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_db;
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
	 * If the task is overdue
	 *
	 * @return bool
	 */
	public function is_overdue() {
		return $this->get_due_date()->isPast() && ! $this->is_complete();
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

	/**
	 * Update wrapper
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public function update( $data = [] ) {

		$was_complete = $this->is_complete();

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

	public function get_as_array() {

		$dueDate = $this->get_due_date();
		$object  = $this->get_associated_object();

		$associated = [
			'link' => '',
			'name' => '',
			'type' => '',
		];

		// Base functionality for contacts
		if ( is_a( $object, Contact::class ) ) {
			$associated['link'] = $object->admin_link() . '&_tab=tasks';
			$associated['name'] = empty( trim( $object->get_full_name() ) ) ? $object->get_email() : $object->get_full_name();
			$associated['type'] = $object->get_object_type();
			$associated['img']  = $object->get_profile_picture( 40 );
		}

		$associated = apply_filters( 'groundhogg/task/associated_context', $associated, $object );

		return array_merge( parent::get_as_array(), [
			'is_overdue'    => $this->is_overdue(),
			'is_complete'   => $this->is_complete(),
			'due_timestamp' => $dueDate->getTimestamp(),
			'i18n'          => [
				'time_diff'      => human_time_diff( $this->timestamp, time() ),
				'due_in'         => human_time_diff( $dueDate->getTimestamp(), time() ),
				'completed'      => human_time_diff( strtotime( $this->date_completed ), time() ),
				'due_date'       => $dueDate->format( get_date_time_format() ),
				'completed_date' => $this->is_complete() ? $this->get_date_completed()->format( get_date_time_format() ) : '',
			],
			'associated'    => $associated
		] );
	}
}
