<?php

namespace Groundhogg;

use Groundhogg\DB\Funnels;
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
			'orderby'   => 'ID',
			'order'     => 'ASC',
		] );

		return wp_parse_id_list( wp_list_pluck( $this->get_steps_db()->query( $query ), 'ID' ) );
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

		$array = parent::get_as_array();
		$steps = $this->get_steps();

		// Todo use real stats
		$array['stats'] = [
			'active_now'     => 10,
			'active_last_30' => 30,
			'complete'       => 15
		];

		$array['steps'] = [];

		if ( ! $steps ) {
			return $array;
		}

		foreach ( $steps as $step ) {
			$array['steps'][] = $step->get_as_array();
		}

		$array['edges'] = array_map( function ( $edge ) {
			return [
				'from_id'   => absint( $edge->from_id ),
				'to_id'     => absint( $edge->to_id ),
				'funnel_id' => absint( $edge->funnel_id ),
			];
		}, get_db( 'step_edges' )->query( [ 'funnel_id' => $this->get_id() ] ) );

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
	 * 1. Create the funnel with details from the JSON config file
	 * 2. Create a map of all the step IDs to a unique ID
	 * 4. Replace all the IDs in the edges and steps with the Unique ID
	 * 5. Import all the steps
	 *      update the map with the new step ID from the DB
	 * 6. Import all the edges, referencing the ID map
	 *
	 * @param $import
	 *
	 * @return bool|int|\WP_Error
	 */
	public function import( $import ) {

		// Validate the import
		if ( is_string( $import ) ) {
			$import = json_decode( $import, true );
		}

		if ( ! is_array( $import ) || empty( $import ) ) {
			return new \WP_Error( 'invalid_funnel', 'Invalid funnel markup.' );
		}

		// Create the funnel
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

		// Import the steps
		$steps = $import['steps'];
		$edges = $import['edges'];

		$id_map = [];

		foreach ( $steps as $i => $step ) {

			$old_step_id = $step['ID'];

			$data = $step['data'];

			$step_title = $data['title'];
			$step_group = $data['group'];
			$step_type  = $data['type'];

			$args = array(
				'funnel_id'   => $funnel_id,
				'step_title'  => $step_title,
				'step_status' => 'ready',
				'step_group'  => $step_group,
				'step_type'   => $step_type,
			);

			$step = new Step( $args );

			if ( ! $step->exists() ) {
				continue;
			}

			$new_step_id = $step->get_id();

			$step_meta = $step['meta'];

			foreach ( $step_meta as $key => $value ) {
				$step->update_meta( $key, $value );
			}

			// Create the ID map
			$id_map[ $old_step_id ] = $new_step_id;

		}

		// Add edges using the ID map to fetch the real step ID
		if ( ! empty( $edges ) ) {
			foreach ( $edges as $edge ) {
				get_db( 'step_edges' )->add( [
					'funnel_id' => $this->get_id(),
					'from_id'   => $id_map[ $edge['from_id'] ],
					'to_id'     => $id_map[ $edge['to_id'] ],
				] );
			}
		}

		return $funnel_id;
	}

	/**
	 * Publish changes made in the config to the steps and their respective settings
	 * This allows for automatic updates of the funnel without making changes live until the user
	 * commits the changes.
	 *
	 * 1. Pause any active events in the funnel.
	 * 2. For each of the steps in the config, update the steps in the DB with those settings.
	 * 3. Delete ALL of the edges in the step edges table
	 * 4. Recreate the edges based on the edges in the config.
	 * 5. If there are any orphaned steps (without edges) delete them.
	 * 6. Release any pending events.
	 *
	 * @var $config array the serialized json config.
	 */
	public function publish_changes() {

		// pause any active events
		get_db( 'events' )->update( [
			'funnel_id' => $this->get_id(),
			'status'    => Event::WAITING
		], [
			'status' => Event::PAUSED
		] );

		// Update the steps with the most recent information.
		$config        = $this->draft_config;
		$altered_steps = $config['steps'];

		foreach ( $altered_steps as $altered_step ) {
			$live_step = new Step( $altered_step['ID'] );
			$live_step->update( $altered_step['data'], $altered_step['meta'] );
		}

		// Delete all current edges
		get_db( 'step_edges' )->bulk_delete( [
			'funnel_id' => $this->get_id()
		] );

		// Recreate any edges
		$new_edges = $config['edges'];

		foreach ( $new_edges as $new_edge ) {
			get_db( 'step_edges' )->add( [
				'funnel_id' => $this->get_id(),
				'from_id'   => $new_edge['from_id'],
				'to_id'     => $new_edge['to_id'],
			] );
		}

		// Delete orphaned steps
		$all_step_ids = $this->get_step_ids();

		foreach ( $all_step_ids as $step_id ) {
			if ( get_db( 'step_edges' )->is_step_orphaned( $this->get_id(), $step_id ) ) {
				$step = new Step( $step_id );
				$step->delete();
			}
		}

		// Release any paused events
		get_db( 'events' )->update( [
			'funnel_id' => $this->get_id(),
			'status'    => Event::PAUSED
		], [
			'status' => Event::WAITING
		] );

	}
}