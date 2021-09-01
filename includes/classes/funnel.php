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

	public function is_sharing_enabled() {
		return $this->get_meta( 'sharing' ) === 'enabled';
	}

	/**
	 * Pause any events in the queue
	 */
	public function pause_events() {
		get_db( 'event_queue' )->update( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::WAITING,
		], [
			'status' => Event::PAUSED
		] );
	}

	/**
	 * Unpause any waiting events in the event queue
	 */
	public function unpause_events() {
		get_db( 'event_queue' )->update( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::PAUSED,
		], [
			'status' => Event::WAITING
		] );
	}

	/**
	 * Cancel events
	 */
	public function cancel_events() {
		get_db( 'event_queue' )->update( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::WAITING,
		], [
			'status' => Event::CANCELLED
		] );
	}

	/**
	 * Delete events outright
	 */
	public function delete_waiting_events() {
		get_db( 'event_queue' )->delete( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::WAITING,
		] );
	}

	/**
	 * Commit all the changes from the previous update.
	 *
	 * - Pause all active events for this funnel
	 * - Commit all the edited steps
	 * - Delete any steps which are not in the last commit
	 * - Update the state of "edited"
	 * - Resume any remaining paused events
	 * - Update the funnel to active
	 */
	public function commit() {

		// Pause any active events
		$this->pause_events();

		$edited = $this->get_meta( 'edited' );

		// Exit if there were not changes.
		if ( ! $edited ) {
			$this->add_error( 'error', 'No changes have been made' );

			return false;
		}

		// Create temp step objects from all the steps based as a big ol' array
		$edited_steps = array_map( function ( $edited_step ) {
			return new Temp_Step( $edited_step );
		}, $edited['steps'] );

		// Create a copy of the "previous" steps pre-commit
		$previous_steps = $this->get_steps();

		// Loop thru all the edited steps
		foreach ( $edited_steps as $edited_step ) {

			// validate the step settings through the use of the save method from the Funnel_Step()
			$edited_step->validate();

			if ( $edited_step->has_errors() ) {

				foreach ( $edited_step->get_errors() as $error ) {
					$error->add_data( [ 'step' => $edited_step ] );
					$this->add_error( $error );
				}
			}
		}

		/**
		 * If the funnel has errors at this point
		 * - Reset the status of the funnel to active or inactive
		 * - unpause any waiting steps
		 * - return false
		 */
		if ( $this->has_errors() ) {

			// unpause any paused events
			$this->unpause_events();

			return false;
		}

		// Old ID => New ID
		$id_map = [];

		// There were no errors, so we can commit the changes to the funnel
		foreach ( $edited_steps as $edited_step ) {

			// The temp ID will be overwritten so store it temporarily
			$temp_id = $edited_step->get_id();

			$edited_step->commit();

			// After committing the ID will be changed from the temp ID to the new real ID
			$id_map[ $temp_id ] = $edited_step->get_id();
		}

		// Create and ID list of all the now current steps of this funnel
		$committed_step_ids = array_map( function ( $step ) {
			return $step->get_id();
		}, $edited_steps );

		// Create a list of all the steps which are not in the commit
		$steps_to_delete = array_filter( $previous_steps, function ( $step ) use ( $committed_step_ids ) {
			// If the ID is not in the committed steps, we're deleting it.
			return ! in_array( $step->get_id(), $committed_step_ids );
		} );

		// Loop through all the steps which need to be deleted and delete them
		foreach ( $steps_to_delete as $step_to_delete ) {
			$step_to_delete = new Step( $step_to_delete );
			$step_to_delete->delete();
		}

		// Update temp step Ids in other step settings to real step ids
		foreach ( $id_map as $old_id => $new_id ) {

			// This will handle only the trivial case of the step being saved in the meta value not in an array.
			get_db( 'stepmeta' )->update( [
				'meta_value' => $old_id
			], [
				'meta_value' => $new_id
			] );
		}

		/**
		 * Do any clean up actions that extensions can hook into that may need to happen after a funnel is commited
		 *
		 * @param $funnel Funnel
		 * @param $id_map int[]
		 */
		do_action( 'groundhogg/funnel/commit/after', $this, $id_map );

		// Update the status of the funnel to active
		$this->update( [
			'status' => 'active'
		] );

		// Update tghe edited state to the current state
		$this->update_meta( [
			'edited' => [
				'steps' => $this->get_steps()
			]
		] );

		// Unpause any active events which haven't been+ deleted as a result of the associated step being deleted
		$this->unpause_events();

		return true;
	}

	/**
	 * Pause or unpause events depending on the status change of the funnel
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function update( $data = [] ) {

		$was_active = $this->is_active();

		$updated = parent::update( $data );

		// Went from inactive to active
		if ( $this->is_active() && ! $was_active ) {

			// Unpause the events active events
			$this->unpause_events();
		} // Went from active to inactive
		else if ( $was_active && ! $this->is_active() ) {
			switch ( $this->get_status() ) {
				case 'archived':
					// Cancel events outright
					$this->cancel_events();
					break;
				case 'inactive':
					// Pause any waiting events
					$this->pause_events();
					break;
			}
		}

		return $updated;
	}

	/**
	 * Get the ID of the conversion step...
	 * This can be defined, or is assumed the last benchmark in the funnel...
	 *
	 * @return int[]
	 */
	public function get_conversion_step_ids() {
		return array_map( function ( $step ) {
			return $step->get_id();
		}, $this->get_steps( [ 'is_conversion' => true ] ) );
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
	public function legacy_export() {
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
	 * Get the funnel as an array.
	 *
	 * @return array|bool
	 */
	public function get_as_array() {
		return array_merge( parent::get_as_array(), [
			'steps'     => $this->get_steps(),
			'campaigns' => $this->get_related_objects( 'campaign' ),
			'links'     => [
				'export' => $this->export_url(),
				'report' => admin_page_url( 'gh_reporting', [
					'tab'         => 'v3',
					'currentPage' => 'funnels',
					'params'      => [ 'funnel' => $this->get_id() ],
				] ),
			]
		] );
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
	 * @param $template
	 *
	 * @return bool|int|\WP_Error
	 */
	public function legacy_import( $template ) {

		if ( is_string( $template ) ) {
			$template = json_decode( $template, true );
		}

		if ( ! is_array( $template ) || empty( $template ) ) {
			return new \WP_Error( 'invalid_funnel', 'Invalid funnel markup.' );
		}

		$title = $template['title'];

		$args = [
			'title'  => $title,
			'status' => 'inactive',
			'author' => get_current_user_id()
		];

		$funnel_id = $this->create( $args );

		if ( ! $funnel_id ) {
			return new \WP_Error( 'db_error', 'Could not add to the DB.' );
		}

		$steps = $template['steps'];

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