<?php

namespace Groundhogg\Classes;

use Groundhogg\Background\Task;
use Groundhogg\Base_Object;
use Groundhogg\Utils\Limits;
use function Groundhogg\get_db;

class Background_Task extends Base_Object {

	/**
	 * @var Task
	 */
	protected $task;

	protected function post_setup() {
		$this->task    = maybe_unserialize( $this->task );
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
	 * @return bool
	 */
	public function process() {

		// Can the task be run
		if ( ! $this->task->can_run() ) {
			return false;
		}

		Limits::start();

		$complete = false;

		// While there is still more of the task to do
		while ( ! Limits::limits_exceeded() && ! $complete ) {
			$complete = $this->task->process();
			Limits::processed_action();
		}

		// Cleanup
		$this->task->stop();

		$data = [
			'task' => $this->task
		];

		if ( $complete ) {
			$data['status'] = 'done';
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

	public function is_done(){
		return $this->status === 'done';
	}

}
