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
		$array          = parent::get_as_array();
		$steps          = $this->get_steps();
		$array['steps'] = [];

		if ( ! $steps ) {
			return $array;
		}

		foreach ( $steps as $step ) {
			$array['steps'][] = $step->get_as_array();
		}

		$array = apply_filters( 'groundhogg/funnel/export', $array, $this );

		return $array;
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

	public function isValidFunnel() {

		$steps = $this->get_steps();

		foreach ( $steps as $step ){

			$step->validate();

			if ( $step->has_errors() ){

				foreach ( $step->get_errors() as $error ){
					$error->add_data( $step->get_id(), 'step_id' );
					$this->add_error( $error );
				}

			}

		}

		return ! $this->has_errors();
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