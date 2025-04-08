<?php

namespace Groundhogg\Classes;

use Groundhogg\Background\Task;
use Groundhogg\Base_Object;
use Groundhogg\Utils\Limits;
use function Groundhogg\get_db;

class Background_Task extends Base_Object {

	public function get_progress() {
		return $this->theTask->get_progress();
	}

	public function is_claimed() {
		return ! empty( $this->claim );
	}

	public function getTask() {
		return $this->theTask;
	}

	/**
	 * @var Task
	 */
	public $theTask;

	protected function post_setup() {
		// use $theTask because using $task would prevent the row from updating because of keep_the_diff
		$this->theTask = maybe_unserialize( $this->task );
		$this->time    = absint( $this->time );
		$this->user_id = absint( $this->user_id );
	}

	protected function get_db() {
		return get_db( 'background_tasks' );
	}

	/**
	 * Sanitize the columns before adding to the DB
	 *
	 * @param $data
	 *
	 * @return array|mixed
	 */
	protected function sanitize_columns( $data = [] ) {

		foreach ( $data as $column => &$value ) {
			switch ( $column ) {
				case 'task':
					$value = maybe_serialize( $value );
					break;
			}
		}

		return $data;
	}

	/**
	 * Process the task
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function process() {

		// If the status is already in progress then this does nothing
		$this->update( [ 'status' => 'in_progress' ] );

		// This task was not claimed
		if ( ! $this->claim ) {
			$this->update( [ 'claim' => 'manual' ] );
		}

		// Can the task be run
		if ( ! $this->theTask->can_run() ) {
			throw new \Exception( 'Task can\'t run.' );
		}

		Limits::start();

		$complete = false;

		// While there is still more of the task to do
		while ( ! Limits::limits_exceeded() && $complete === false ) {
			$complete = $this->theTask->process();

			// update the task as it's being processed
//			$this->update( [
//				'task' => $this->theTask
//			] );

			Limits::processed_action();
		}

		// Cleanup
		$this->theTask->stop();

		$data = [
			'task' => $this->theTask
		];

		if ( $complete === true ) {
			$data['status'] = 'done';
		}

		// remove the manual claim
		if ( $this->claim === 'manual' ) {
			$data['claim'] = '';
		}

		// Update the table with the new task details
		$this->update( $data );

		Limits::stop();

		return $complete;

	}

	/**
	 * Cancel a task
	 *
	 * @return bool
	 */
	public function cancel() {
		return $this->update( [
			'status' => 'cancelled'
		] );
	}

	/**
	 * Resume
	 *
	 * @return bool
	 */
	public function resume() {
		return $this->update( [
			'status' => 'in_progress',
			'time'   => time()
		] );
	}

	public function is_done() {
		return $this->status === 'done';
	}

}
