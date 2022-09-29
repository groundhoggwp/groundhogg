<?php

namespace Groundhogg;

use Groundhogg\DB\Funnels;
use Groundhogg\DB\Meta_DB;
use Groundhogg\DB\Steps;

class Funnel extends Base_Object_With_Meta {

	protected $is_template = false;
	protected $steps = [];

	public function __construct( $identifier_or_args = 0, $is_template_data = false ) {

		if ( $is_template_data ) {
			$this->setup_template_data( $identifier_or_args );

			return;
		}

		parent::__construct( $identifier_or_args );
	}

	protected function setup_template_data( $data ) {

		$data = (object) $data;

		$this->is_template = true;
		$this->ID          = $data->ID;
		$this->data        = (array) $data->data;
		$this->meta        = (array) $data->meta;
		$this->steps       = (array) $data->steps;
	}

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
	 * Mass update sattus of steps related to this funnel
	 *
	 * @param $status
	 *
	 * @return bool
	 */
	public function update_step_status( $status ){
		return get_db( 'steps' )->update([
			'funnel_id' => $this->get_id()
		], [
			'step_status' => $status
		]);
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

		$edited_steps = array_map( function ( $edited_step ) {
			return new Temp_Step( $edited_step );
		}, $edited['steps'] );

		// Create a copy of the "previous" steps pre-commit
		$previous_steps = $this->get_steps();

		$loop = [];

		// Loop thru all the edited steps
		foreach ( $edited_steps as $edited_step ) {

			$loop[] = $edited_step;

			// validate the step settings through the use of the save method from the Funnel_Step()
			$edited_step->validate();

			if ( $edited_step->has_errors() ) {

				$loop[] = $edited_step->get_errors();

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

		// There were no errors, so we can commit the changes to the funnel
		foreach ( $edited_steps as $edited_step ) {
			$edited_step->commit();
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

		// Unpause any active events which haven't been+ deleted as a result of the associated step being deleted
		$this->unpause_events();

		// Update the status of the funnel to active
		$this->update( [
			'status' => 'active'
		] );

		$this->update_meta( [
			'edited' => [
				'steps' => $this->get_steps()
			]
		] );

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

			// Pause any waiting events
			$this->pause_events();
		}

		$this->update_step_status( $this->is_active() ? 'active' : 'inactive' );

		return $updated;
	}

	/**
	 * Get the ID of the conversion step...
	 * This can be defined, or is assumed the last benchmark in the funnel...
	 *
	 * @return int[]
	 */
	public function get_conversion_step_ids() {
		return get_object_ids( array_filter( $this->get_steps(), function ( $step ) {
			return $step->is_conversion();
		} ) );
	}

	/**
	 * Get the ID of the conversion step...
	 * This can be defined, or is assumed the last benchmark in the funnel...
	 *
	 * @return int
	 */
	public function legacy_conversion_step_id() {
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
	 * @return array
	 */
	public function get_entry_step_ids() {
		return get_object_ids( array_filter( $this->get_steps(), function ( $step ) {
			return $step->is_starting() || $step->is_entry();
		} ) );
	}

	/**
	 * Get the step IDs associate with this funnel
	 *
	 * @param array $query
	 *
	 * @return array
	 */
	public function get_step_ids( $query = [] ) {
		return get_object_ids( $this->get_steps( $query ) );
	}

	/**
	 * Get a bunch of steps
	 *
	 * @param array $query
	 *
	 * @return Step[]
	 */
	public function get_steps( $query = [] ) {

		$query = wp_parse_args( $query, [
			'funnel_id' => $this->get_id(),
			'orderby'   => 'step_order',
			'order'     => 'ASC',
		] );

		$steps = $this->get_steps_db()->query( $query );

		return array_map_to_step( $steps );
	}

	/**
	 * Get the funnel as an array.
	 *
	 * @return array|bool
	 */
	public function legacy_export() {
		$export          = [];
		$export['title'] = $this->get_title();
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

		return apply_filters( 'groundhogg/funnel/export', $export, $this );
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
	 * @return bool|int|\WP_Error
	 */
	public function import( $data ) {

		// legacy import
		if ( isset_not_empty( $data, 'title' ) ) {
			return $this->legacy_import( json_decode( json_encode( $data ), true ) );
		}

		$this->setup_template_data( $data );

		$this->create( [
			'title'  => $this->get_title(),
			'author' => get_current_user_id(),
			'status' => 'inactive'
		] );

		if ( ! $this->exists() ) {
			return new \WP_Error( 'error', 'Unable to create funnel.' );
		}

		/**
		 * @var $steps Step[]
		 */
		$steps = [];

		foreach ( $this->steps as $i => $_step ) {

			$_step = (object) $_step;

			$data              = (array) $_step->data;
			$data['funnel_id'] = $this->get_id();
			$step              = new Step();

			$step->create( $data );
			$step->update_meta( (array) $_step->meta );
			$step->import( (array) $_step->export );

			// Save the original ID from the donor site
			$step->update_meta( 'imported_step_id', $_step->ID );

			$steps[ $i ] = $step;
		}

		// Re-run through the steps and perform cleanup actions...
		foreach ( $steps as $step ) {
			$step->post_import();
		}

		// don't need imported_step_id forever, just get rid of it
		get_db( 'stepmeta' )->delete( [
			'meta_key' => 'imported_step_id'
		] );

		return $this->get_id();
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
