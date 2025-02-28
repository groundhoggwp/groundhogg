<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Classes\Task;
use Groundhogg\Step;
use function Groundhogg\andList;
use function Groundhogg\array_bold;
use function Groundhogg\array_map_to_class;
use function Groundhogg\get_db;
use function Groundhogg\get_object_ids;
use function Groundhogg\html;
use function Groundhogg\one_of;
use function Groundhogg\orList;

class Task_Completed extends Benchmark {

	protected function get_complete_hooks() {
		return [
			'groundhogg/task/contact/completed' => 1
		];
	}

	public function setup( $task ) {
		$this->add_data( 'task', $task );
	}

	protected function get_the_contact() {
		$completed = $this->get_data( 'task' );

		return $completed->get_associated_object();
	}

	/**
	 * @return bool|void
	 */
	protected function can_complete_step() {

		// The current task that was just completed
		$completed = $this->get_data( 'task' );

		if ( ! $completed->is_for_contact() ) {
			return false;
		}

		$tasks     = $this->get_setting( 'tasks', [] );
		$condition = $this->get_setting( 'condition', 'any' );

		switch ( $condition ) {
			default:
			case 'any':
				return in_array( $completed->step_id, $tasks );
			case 'all':

				// Newly completed task is not in this array
				if ( ! in_array( $completed->step_id, $tasks ) ) {
					return false;
				}

				// Iterate through all tasks
				foreach ( $tasks as $step_id ) {

					// Get most recent created task based on the contact and step id
					$recent = get_db( 'tasks' )->query( [
						'step_id'     => $step_id,
						'object_id'   => $completed->object_id,
						'object_type' => 'contact',
						'limit'       => 1,
						'orderby'     => 'ID',
						'order'       => 'DESC'
					] );

					// No task exists, which mean it can't be completed, thus condition fails
					if ( empty( $recent ) ) {
						return false;
					}

					$recent = new Task( $recent[0] );

					// if task is not complete
					if ( ! $recent->is_complete() ) {
						return false;
					}
				}

				// If we get here, all required tasks have been completed
				return true;
		}
	}

	public function get_name() {
		return __( 'Task Completed' );
	}

	public function get_type() {
		return 'task_completed';
	}

	public function get_sub_group() {
		return 'crm';
	}

	public function get_description() {
		return __( 'Runs when a task is completed.' );
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/task-completed.svg';
	}

	protected function get_preceding_task_steps( $step ) {
		return array_filter( $step->get_preceding_actions(), function ( $step ) {
			return $step->step_type === 'create_task';
		} );
	}

	public function validate_settings( Step $step ) {
		$tasks = $this->get_preceding_task_steps( $step );
		if ( empty( $tasks ) ) {
			$step->add_error( 'no_tasks', 'There must be at least one preceding <b>Create Task</b> action.' );
		}
	}

	/**
	 * @param $step
	 *
	 * @return void
	 */
	public function settings( $step ) {

		$create_task_steps = $this->get_preceding_task_steps( $step );

		$options = [];

		foreach ( $create_task_steps as $available_step ) {
			$options[ $available_step->get_id() ] = sprintf( "%d. %s", $available_step->get_order(), $available_step->get_title() );
		}

		echo html()->e( 'p', [], __( 'Run when these preceding tasks are completed...', 'groundhogg' ) );

		echo html()->e( 'div', [
			'class' => 'gh-input-group'
		], [
			html()->dropdown( [
				'name'        => $this->setting_name_prefix( 'condition' ),
				'selected'    => $this->get_setting( 'condition', 'any' ),
				'option_none' => false,
				'style'       => [ 'vertical-align' => 'middle' ],
				'options'     =>
					[
						'any' => __( 'Any' ),
						'all' => __( 'All' ),
					]
			] ),
			html()->select2( [
				'id'       => $this->setting_id_prefix( 'tasks' ),
				'name'     => $this->setting_name_prefix( 'tasks' ) . '[]',
				'selected' => $this->get_setting( 'tasks', [] ),
				'options'  => $options,
				'multiple' => true,
			] )
		] );

		?><p></p><?php

	}

	/**
	 * Ensure the included tasks steps are only the ones that appear before this benchmark
	 *
	 * @param $step_ids
	 *
	 * @return array
	 */
	public function validate_task_step_ids( $step_ids ) {
		$step = $this->get_current_step();

		$step_ids   = wp_parse_id_list( $step_ids );
		$task_steps = array_map_to_class( $step_ids, Step::class );
		$task_steps = array_filter( $task_steps, function ( $task_step ) use ( $step ) {
			return $task_step->is_before( $step );
		} );

		return get_object_ids( $task_steps );
	}

	public function get_settings_schema() {
		return [
			'tasks'     => [
				'default'  => [],
				'sanitize' => [ $this, 'validate_task_step_ids' ]
			],
			'condition' => [
				'default'  => 'any',
				'sanitize' => function ( $value ) {
					return one_of( $value, [ 'any', 'all' ] );
				}
			]
		];
	}

	public function generate_step_title( $step ) {
		$tasks = $this->get_setting( 'tasks', [] );

		if ( empty( $tasks ) ) {
			return 'A task is completed';
		}

		$tasks = array_map_to_class( $tasks, Step::class );
		$tasks = array_bold( array_map( function ( $step ) {
			return $step->get_meta( 'summary' ) ?: 'New Task';
		}, $tasks ) );

		if ( count( $tasks ) === 1 ) {
			return sprintf( 'When %s is completed', orList( $tasks ) );
		}

		switch ( $this->get_setting( 'condition', 'any' ) ) {
			default:
			case 'any':
				return sprintf( 'When %s is completed', orList( $tasks ) );
			case 'all':
				return sprintf( 'When %s are completed', andList( $tasks ) );

		}
	}
}
