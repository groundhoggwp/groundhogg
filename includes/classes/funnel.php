<?php

namespace Groundhogg;

use Groundhogg\DB\Funnels;
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
		event_queue_db()->update( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::WAITING,
		], [
			'status'         => Event::PAUSED,
			'time_scheduled' => time()
		] );
	}

	/**
	 * Unpause any waiting events in the event queue
	 */
	public function unpause_events() {
		event_queue_db()->update( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::PAUSED,
		], [
			'status'         => Event::WAITING,
			'time_scheduled' => time()
		] );
	}

	/**
	 * Cancel paused or waiting events
	 */
	public function cancel_events() {

		$time = time();

		// Cancel waiting events
		event_queue_db()->update( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::WAITING,
		], [
			'status'         => Event::CANCELLED,
			'time_scheduled' => $time
		] );

		// Cancel paused events
		event_queue_db()->update( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::PAUSED,
		], [
			'status'         => Event::CANCELLED,
			'time_scheduled' => $time
		] );

		// Move to history
		event_queue_db()->move_events_to_history( [
			'funnel_id'  => $this->get_id(),
			'event_type' => Event::FUNNEL,
			'status'     => Event::CANCELLED,
		], 'AND' );
	}

	/**
	 * Mass update status of steps related to this funnel
	 *
	 * @return bool
	 */
	public function update_step_status() {
		return get_db( 'steps' )->update( [
			'funnel_id' => $this->get_id()
		], [
			'step_status' => $this->is_active() ? 'active' : 'inactive'
		] );
	}

	/**
	 * Pause, unpause, or cancel events based on the current status of the funnel
	 *
	 * @return void
	 */
	public function update_events_from_status() {
		switch ( $this->get_status() ) {
			case 'active':
				$this->unpause_events();
				break;
			case 'inactive':
				$this->pause_events();
				break;
			case 'archived':
				$this->cancel_events();
				break;
		}
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

		// can't commit if not active...
		if ( ! $this->is_active() ){
			return false;
		}

		$steps = $this->get_steps();

		// commit all the step changes
		foreach ( $steps as $step ) {
			$step->commit();
		}
	}

	/**
	 * Pause or unpause events depending on the status change of the funnel
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function update( $data = [] ) {

		$old_status = $this->get_status();
		$updated    = parent::update( $data );
		$new_status = $this->get_status();

		// When the status of the funnel changes we must handle events and steps accordingly
		if ( $new_status !== $old_status ) {
			$this->update_step_status();
			$this->update_events_from_status();
		}

		return $updated;
	}

	/**
	 * Get all the conversion steps in this funnel
	 *
	 * @return Step[]
	 */
	public function get_conversion_steps() {
		return array_filter( $this->get_steps(), function ( $step ) {
			return $step->is_conversion();
		} );
	}

	/**
	 * Get the ID of the conversion steps...
	 * This can be defined, or is assumed the last benchmark in the funnel...
	 *
	 * @return int[]
	 */
	public function get_conversion_step_ids() {
		return get_object_ids( $this->get_conversion_steps() );
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
	 * Get entry steps
	 *
	 * @return Step[]
	 */
	public function get_entry_steps() {
		return array_filter( $this->get_steps(), function ( $step ) {
			return $step->is_starting() || $step->is_entry();
		} );
	}

	/**
	 * Get IDs of entry steps
	 *
	 * @return array
	 */
	public function get_entry_step_ids() {
		return get_object_ids( $this->get_entry_steps() );
	}

	/**
	 * All send_email steps
	 *
	 * @return Step[]
	 */
	public function get_email_steps() {
		return array_filter( $this->get_steps(), function ( $step ) {
			return $step->type_is( 'send_email' );
		} );
	}

	/**
	 * Retrieve all email assets within a funnel
	 *
	 * @return Email[]
	 */
	public function get_emails() {
		return array_filter( array_map( function ( Step $step ) {

			$email_id = $step->get_meta( 'email_id' );
			if ( ! $email_id ) {
				return false;
			}

			return new Email( $email_id );

		}, $this->get_email_steps() ) );
	}

	/**
	 * Get the step IDs associated with this funnel
	 *
	 * @param array $query
	 *
	 * @return array
	 */
	public function get_step_ids( $query = [] ) {
		return get_object_ids( $this->get_steps( $query ) );
	}

	/**
	 * Get the total number of steps in the funnel
	 *
	 * @return array|bool|int|object|null
	 */
	public function get_num_steps() {
		return db()->steps->count( [
			'funnel_id' => $this->get_id(),
		] );
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

	public function get_steps_for_editor() {
		$steps = $this->get_steps();

		foreach ( $steps as $step ) {
			$step->merge_changes();
		}

		usort( $steps, function ( Step $a, Step $b ) {
			return $a->get_order() - $b->get_order();
		} );

		return $steps;
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

	public function is_editing() {

		if ( wp_doing_ajax() || wp_is_serving_rest_request() ) {
			wp_parse_str( wp_parse_url( wp_get_referer() , PHP_URL_QUERY ), $params );
			if ( get_array_var( $params, 'page' ) === 'gh_funnels' && get_array_var( $params, 'action' ) === 'edit' && isset_not_empty( $params, 'funnel' ) ){
				return true;
			}
		}

		return get_url_var( 'page' ) === 'gh_funnels'
		       && get_url_var( 'action' ) === 'edit'
		       && absint( get_url_var( 'funnel' ) ) === $this->ID;
	}

	/**
	 * Get the funnel as an array.
	 *
	 * @return array|bool
	 */
	public function get_as_array() {

		if ( $this->is_editing() ){
			$steps = $this->get_steps_for_editor();
		} else {
			$steps = $this->get_steps();
		}

		return array_merge( parent::get_as_array(), [
			'steps'     => $steps,
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

			$data['step_status'] = 'inactive'; // force status to inactive

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
				'funnel_id'  => $funnel_id,
				'step_title' => $step_title,
				'step_group' => $step_group,
				'step_type'  => $step_type,
				'step_order' => $i + 1,
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
			'funnel_id'  => $this->get_id(),
			'step_order' => count( $this->get_step_ids() ) + 1,
			'meta'       => [],
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

	/**
	 * Handler to also delete steps
	 *
	 * @return bool
	 */
	public function delete() {

		$steps = $this->get_steps();

		// delete all the steps in the funnel as well
		foreach ( $steps as $step ) {
			$step->delete();
		}

		return parent::delete();
	}
}
