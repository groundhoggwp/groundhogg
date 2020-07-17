<?php

namespace Groundhogg;

use Groundhogg\DB\Funnels;
use Groundhogg\DB\Meta_DB;
use Groundhogg\DB\Steps;

class Funnel extends Base_Object_With_Meta {
	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {
		// TODO: Implement post_setup() method.
	}

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return Funnels
	 */
	protected function get_db() {
		return get_db( 'funnels' );
	}

	/**
	 * @return Steps
	 */
	protected function get_steps_db() {
		return get_db( 'steps' );
	}

	protected function get_meta_db() {
		return Plugin::instance()->dbs->get_db( 'funnelmeta' );
	}


	/**
	 * A string to represent the object type
	 *
	 * @return string
	 */
	protected function get_object_type() {
		return 'funnel';
	}

	public function get_id() {
		return absint( $this->ID );
	}

	public function get_title() {
		return $this->title;
	}

	public function get_status() {
		return $this->status;
	}

	public function is_active() {
		return $this->get_status() === 'active';
	}

	/**
	 * Get the ID of the conversion step...
	 * This can be defined, or is assumed the last benchmark in the funnel...
	 *
	 * @return int
	 */
	public function get_conversion_step_id() {
		$conversion_step_id = absint( $this->conversion_step );

		if ( ! $conversion_step_id ) {
			$steps = $this->get_steps( [
				'step_group' => Step::BENCHMARK,
			] );

			$last = array_pop( $steps );

			if ( $last ) {

				return $last->get_id();
			}

			return 0;

		}

		return $conversion_step_id;
	}

	public function get_first_action_id() {
		$actions = $this->get_step_ids( [
			'step_group' => Step::ACTION,
		] );

		return array_shift( $actions );
	}

	/**
	 * @return int
	 */
	public function get_first_step_id() {
		$actions = $this->get_step_ids( [
			'step_group' => Step::BENCHMARK,
		] );

		return array_shift( $actions );
	}


	/**
	 * Get the step IDs associate with this funnel
	 *
	 * @param array $query
	 *
	 * @return array
	 */
	public function get_step_ids( $query = [] ) {
		$query = array_merge( $query, [
			'funnel_id' => $this->get_id(),
			'orderby'   => 'step_order',
			'order'     => 'ASC',
		] );

		return wp_parse_id_list( wp_list_pluck( $this->get_steps_db()->get_steps( $query ), 'ID' ) );
	}

	/**
	 * Get a bunch of steps
	 *
	 * @param array $query
	 *
	 * @return Step[]
	 */
	public function get_steps( $query = [] ) {
		$raw_step_ids = $this->get_step_ids( $query );

		$steps = [];

		foreach ( $raw_step_ids as $raw_step_id ) {
			$steps[] = new Step( $raw_step_id );
		}

		return $steps;
	}

	/**
	 * Get the funnel as an array.
	 *
	 * @return array|bool
	 */
	public function get_as_array() {
		$export           = [];
		$export['id']     = $this->get_id();
		$export['status'] = $this->get_status();
		$export['title']  = $this->get_title();
		$export['steps']  = [];

		$steps = $this->get_steps();

		if ( ! $steps ) {
			return false;
		}

		foreach ( $steps as $i => $step ) {
			$export['steps'][ $i ]             = [];
			$export['steps'][ $i ]['id']       = $step->get_id();
			$export['steps'][ $i ]['title']    = $step->get_title();
			$export['steps'][ $i ]['group']    = $step->get_group();
			$export['steps'][ $i ]['order']    = $step->get_order();
			$export['steps'][ $i ]['type']     = $step->get_type();
			$export['steps'][ $i ]['meta']     = $step->get_meta();
			$export['steps'][ $i ]['settings'] = $step->get_meta();
			$export['steps'][ $i ]['args']     = $step->export();
			$export['steps'][ $i ]['icon']     = $step->icon();
			$export['steps'][ $i ]['delay']    = $step->get_delay_config();
		}

		$export = apply_filters( 'groundhogg/funnel/export', $export, $this );

		return $export;
	}

	/**
	 * Group benchmarks together...
	 */
	public function get_for_react_editor() {

		$export           = [];
		$export['id']     = $this->get_id();
		$export['status'] = $this->get_status();
		$export['title']  = $this->get_title();
		$export['groups'] = [];

		$steps = $this->get_steps();

		if ( ! $steps ) {
			return false;
		}

		$benchmark_group = [
			'id'    => uniqid(),
			'type'  => 'benchmark_group',
			'steps' => []
		];

		$action_group = [
			'id'    => uniqid(),
			'type'  => 'action_group',
			'steps' => []
		];

		foreach ( $steps as $i => $step ) {

			$step_config = [
				'id'    => $step->get_id(),
				'title' => $step->get_title(),
				'group' => $step->get_group(),
				'order' => $step->get_order(),
				'type'  => $step->get_type(),
				'meta'  => $step->get_meta(),
				'args'  => $step->export(),
				'icon'  => $step->icon(),
			];

			if ( $step->is_benchmark() ) {
				$benchmark_group['steps'][] = $step_config;

				// If the next step is an action or we have reached the end of the funnel
				if ( $i + 1 === count( $steps ) || ! $steps[ $i + 1 ]->is_benchmark() ) {

					// Add the benchmark group to the steps
					$export['groups'][] = $benchmark_group;

					// Reset the benchmark grouping
					$benchmark_group = [
						'id'    => uniqid(),
						'type'  => 'benchmark_group',
						'steps' => []
					];
				}
			} else if ( $step->is_action() ) {
				$action_group['steps'][] = $step_config;

				// If the next step is a benchmark or we have reached the end of the funnel
				if ( $i + 1 === count( $steps ) || ! $steps[ $i + 1 ]->is_action() ) {

					// Add the action group to the steps
					$export['groups'][] = $action_group;

					// Reset the action grouping
					$action_group = [
						'id'    => uniqid(),
						'type'  => 'action_group',
						'steps' => []
					];
				}
			}

		}

		return $export;

	}

	/**
	 * Return wrapper function.
	 *
	 * @return array|bool
	 */
	public function export() {
		return $this->get_as_array();
	}

	/**
	 * The export URL
	 *
	 * @return string
	 */
	public function export_url() {
		return managed_page_url( sprintf( 'funnels/export/%s/', Plugin::$instance->utils->encrypt_decrypt( $this->get_id() ) ) );
	}

	/**
	 * Import a funnel
	 *
	 * @param $import
	 *
	 * @return bool|int|\WP_Error
	 */
	public function import( $import ) {

		if ( is_string( $import ) ) {
			$import = json_decode( $import, true );
		}

		if ( ! is_array( $import ) || empty( $import ) ) {
			return new \WP_Error( 'invalid_funnel', 'Invalid funnel markup.' );
		}

		$title = $import['title'];

		$args = [
			'title'  => $title,
			'status' => 'inactive',
			'author' => get_current_user_id()
		];

		$funnel_id = $this->create( $args );

		if ( ! $funnel_id ) {
			return new \WP_Error( 'db_error', 'Could not add to the DB.' );
		}

		$steps = $import['steps'];

		foreach ( $steps as $i => $step_args ) {

			$step_title = $step_args['title'];
			$step_group = $step_args['group'];
			$step_type  = $step_args['type'];

			$args = array(
				'funnel_id'   => $funnel_id,
				'step_title'  => $step_title,
				'step_status' => 'ready',
				'step_group'  => $step_group,
				'step_type'   => $step_type,
				'step_order'  => $i + 1,
			);

			$step = new Step( $args );

			if ( ! $step->exists() ) {
				continue;
			}

			$step_meta = $step_args['meta'];

			foreach ( $step_meta as $key => $value ) {

				// Replace URL
				if ( is_string( $value ) ) {
					$value = search_and_replace_domain( $value );
				}

				$step->update_meta( $key, $value );

			}

			$import_args = $step_args['args'];

			$step->import( $import_args );

			// The screen will be blank, so set the first step to active
			if ( $i === 0 && is_white_labeled() ) {
				$step->update_meta( 'is_active', true );
			}

		}

		return $funnel_id;
	}

	/**
	 * @return false|string
	 */
	public function get_as_json() {
		return wp_json_encode( $this->get_as_array() );
	}

	/**
	 * Add a step to the funnel
	 *
	 * @param $args array a list of args for the step
	 *
	 * @return Step|false
	 */
	public function add_step( $args ) {

		$args = wp_parse_args( $args, [
			'funnel_id'   => $this->get_id(),
			'step_status' => 'ready',
			'step_order'  => count( $this->get_step_ids() ) + 1,
			'meta'        => [],
		] );

		$step = new Step( $args );

		if ( ! $step->exists() ) {
			return false;
		}

		foreach ( $args['meta'] as $key => $value ) {
			$step->update_meta( $key, $value );
		}

		return $step;
	}
}