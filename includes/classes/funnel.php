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
		return Plugin::instance()->dbs->get_db( 'funnels' );
	}

	/**
	 * @return Steps
	 */
	protected function get_steps_db() {
		return Plugin::instance()->dbs->get_db( 'steps' );
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

	public function get_conversion_step()
	{
		return absint( $this->conversion_step );
	}

	public function get_first_step()
	{
		return   $this->get_steps( [
			'step_order' => 1
		] )[0] ->get_id();
	}


	/**
	 * Get the step IDs associate with this funnel
	 *
	 * @param array $query
	 *
	 * @return array
	 */
	public function get_step_ids( $query = [] ) {
		$query = array_merge( $query, [ 'funnel_id' => $this->get_id() ] );

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
			$steps[] = Plugin::$instance->utils->get_step( $raw_step_id );
		}

		usort( $steps, function ( Step $a, Step $b ) {
			if ( $a->get_order() == $b->get_order() ) {
				return 0;
			}

			return ( $a->get_order() < $b->get_order() ) ? - 1 : 1;
		} );

		return $steps;
	}

	/**
	 * Get the funnel as an array.
	 *
	 * @return array|bool
	 */
	public function get_as_array() {
		$export          = [];
		$export['title'] = sprintf( "%s - Copy", $this->get_title() );
		$export['steps'] = [];

		$steps = $this->get_steps();

		if ( ! $steps ) {
			return false;
		}

		foreach ( $steps as $i => $step ) {

			$export['steps'][ $i ]          = [];
			$export['steps'][ $i ]['title'] = $step->get_title();
			$export['steps'][ $i ]['group'] = $step->get_group();
			$export['steps'][ $i ]['order'] = $step->get_order();
			$export['steps'][ $i ]['type']  = $step->get_type();
			$export['steps'][ $i ]['meta']  = $step->get_meta();
			$export['steps'][ $i ]['args']  = $step->export();
		}


		$export = apply_filters( 'groundhogg/funnel/export', $export, $this );

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
	 **
	 * @return bool|int|\WP_Error
	 */
	public function import( $import ) {
		if ( is_string( $import ) ) {
			$import = json_decode( $import, true );
		}

		if ( ! is_array( $import ) ) {
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