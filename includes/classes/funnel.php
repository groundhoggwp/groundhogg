<?php

namespace Groundhogg;

use Groundhogg\DB\Funnels;
use Groundhogg\DB\Steps;
use Groundhogg\Utils\DateTimeHelper;

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
		$this->campaigns   = (array) $data->campaigns;
	}

	public function step_flow( $echo = true ) {

		$steps = $this->get_steps();

		$steps = array_filter( $steps, function ( Step $step ) {
			return $step->is_main_branch();
		} );

		if ( empty( $steps ) ) {
			return false;
		}

		$html = '';

		foreach ( $steps as $step ) {
			$step->get_step_element()->validate_settings( $step );
			$step_output = $step->sortable_item( $echo );
			if ( ! $echo ) {
				$html .= $step_output;
			}
		}

		if ( $echo ) {
			return true;
		}

		return $html;
	}

	public function step_settings( $echo = true ) {

		$steps = $this->get_steps();

		usort( $steps, function ( $a, $b ) {
			return $a->ID - $b->ID; // sort by ID because when using morphdom that won't change dom pos
		} );

		$html = '';

		foreach ( $steps as $step ) {
			$step->get_step_element()->validate_settings( $step );
			$html .= $step->html_v2( $echo );
		}

		return $html;
	}

	public function flow_preview( $show = 15 ) {

		$allSteps = ! empty( $this->steps ) ? $this->steps : $this->get_steps();

		?>
        <div class="funnel-preview"><?php

		$steps = array_splice( $allSteps, 0, $show );

		foreach ( $steps as $step ) {

			// from actual funnel
			if ( is_a( $step, Step::class ) ) {
				$step_type = $step->get_step_element();

				// skip unregistered steps, might be polyfill
				if ( ! $step_type->is_registered() ) {
					continue;
				}
			} // from template
			else {

				$step_type = get_array_var( $step->data, 'step_type' );

				if ( ! Plugin::instance()->step_manager->type_is_registered( $step_type ) ) {
					continue;
				}

				$step_type = Plugin::instance()->step_manager->get_element( $step_type );

			}

			?>
            <div class="step-preview">
                <div class="step-icon <?php echo $step_type->get_type() ?> <?php echo $step_type->get_group() ?>">
					<?php if ( $step_type->icon_is_svg() ): ?>
						<?php echo $step_type->get_icon_svg(); ?>
					<?php else: ?>
                        <img src="<?php echo esc_url( $step_type->get_icon() ); ?>">
					<?php endif; ?>
                </div>
                <div class="gh-tooltip top">
					<?php _e( get_array_var( $step->data, 'step_title' ) ) ?>
                </div>
            </div>
			<?php

		}

		if ( ! empty( $allSteps ) ) {
			?>
            <div class="step-preview">
                <div class="step-icon more">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16">
                        <path fill="#000" d="M4 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm6 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm4 2a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>
                    </svg>
                    <div class="gh-tooltip top">
						<?php printf( _n( '%d more step...', '%d more steps', count( $allSteps ), 'groundhogg' ), count( $allSteps ) ) ?>
                    </div>
                </div>
            </div>
			<?php
		}

		?>
        </div><?php

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

		// update inactive steps to active
		if ( $this->is_active() ) {
			return db()->steps->update( [
				'funnel_id'   => $this->get_id(),
				'step_status' => 'inactive'
			], [
				'step_status'    => 'active',
				'date_activated' => ( new DateTimeHelper() )->ymdhis()
			] );
		}

		return get_db( 'steps' )->update( [
			'funnel_id' => $this->get_id()
		], [
			'step_status' => 'inactive'
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
	 * Initialize the levels for the steps
	 *
	 * @param string $branch
	 * @param int    $level
	 *
	 * @return mixed
	 */
	public function set_step_levels( string $branch = 'main', int $level = 1 ) {

		if ( $branch === 'main' && $level === 1 ) {
			Step::increment_step_order( 0 );
		}

		$steps = $this->get_steps();

		$branch_steps = array_filter( $steps, function ( Step $step ) use ( $branch ) {
			return $step->branch_is( $branch );
		} );

		$prev     = null;
		$maxDepth = $level;

		foreach ( $branch_steps as $step ) {

			$step->update_branch_path_in_db(); // do this while we're here

			if ( $step->is_benchmark() ) {
				$step->update( [
					'step_level' => $level,
					'step_order' => Step::increment_step_order()
				] );
				$maxDepth = max( $maxDepth, $this->set_step_levels( "$step->ID", $level + 1 ) );
				$prev     = $step;
				continue;
			}

			if ( $prev && $prev->is_benchmark() ) {
				$level = $maxDepth;
			}

			$step->update( [
				'step_level' => $level,
				'step_order' => Step::increment_step_order()
			] );

			$level ++;

			if ( $step->is_branch_logic() ) {
				$sub_steps = $step->get_sub_steps();
				$branches  = array_unique( wp_list_pluck( $sub_steps, 'branch' ) );
				$maxDepth  = $level;
				foreach ( $branches as $branch ) {
					$maxDepth = max( $maxDepth, $this->set_step_levels( $branch, $level ) );
				}
				$level = $maxDepth;
			}

			$prev = $step;
		}

		return max( $level, $maxDepth );
	}

	/**
	 * Merge step changes into the real data and meta
	 */
	public function commit() {

		// can't commit if not active...
		if ( ! $this->is_active() ) {
			return;
		}

		$steps = $this->get_real_steps(); // use instead of ::get_steps() to avoid merged changes

		// commit all the step changes
		foreach ( $steps as $step ) {
			$step->commit();
		}

		$this->update_step_status();
	}

	/**
	 * Clear any changes and delete inactive steps that may have been added
	 */
	public function uncommit() {

		// can't uncommit if not active...
		if ( ! $this->is_active() ) {
			return;
		}

		$steps = $this->get_real_steps();

		// commit all the step changes
		foreach ( $steps as $step ) {

			// delete any inactive steps
			if ( ! $step->is_active() ) {
				$step->delete_and_commit();
				continue;
			}

			$step->clear_changes();
		}
	}

	/**
	 * Handler to also delete steps
	 *
	 * @return bool
	 */
	public function delete() {

		$this->update( [ 'status' => 'archived' ] );
		$this->update_step_status();
		$this->cancel_events();

		$steps = $this->get_steps();

		// delete all the steps in the funnel as well
		foreach ( $steps as $step ) {
			$step->delete_and_commit();
		}

		return parent::delete();
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

		if ( $this->is_editing() ) {
			return count( $this->get_steps() );
		}

		return db()->steps->count( [
			'funnel_id' => $this->get_id(),
		] );
	}

	public function has_errors() {

		$has_errors = array_any( $this->get_steps(), function ( Step $step ) {
			$step->get_step_element()->validate_settings( $step );

			return $step->has_errors() || $step->get_step_element()->has_errors();
		} );

		if ( $has_errors ) {
			return true;
		}

		return parent::has_errors();
	}

	/**
	 * Get a bunch of steps
	 *
	 * @param array $query
	 *
	 * @return Step[]
	 */
	public function get_steps( $query = [] ) {

		$last_changed = db()->steps->cache_get_last_changed();
		$cache_key    = "$this->ID:steps:$last_changed:" . md5serialize( $query );
		$steps        = wp_cache_get( $cache_key, db()->steps->get_cache_group(), false, $found );

		if ( $found ) {
			return $steps;
		}

		$query = wp_parse_args( $query, [
			'funnel_id' => $this->get_id(),
			'orderby'   => 'step_order',
			'order'     => 'ASC',
		] );

		// if not editing, only active steps should be included...
		if ( ! $this->is_editing() && $this->is_active() ) {
			$query['step_status'] = 'active';
		}

		$steps = $this->get_steps_db()->query( $query );

		$steps = array_map_to_step( $steps );

		if ( $this->is_editing() ) {

			foreach ( $steps as $step ) {
				$step->merge_changes();
			}

			// filter out "deleted" steps with the status as deleted in their changes
			$steps = array_filter( $steps, function ( Step $step ) {
				return $step->step_status !== 'deleted';
			} );

			// resort because of changes
			usort( $steps, function ( Step $a, Step $b ) {
				return $a->get_order() - $b->get_order();
			} );
		}

		wp_cache_set( $cache_key, $steps, db()->steps->get_cache_group(), MINUTE_IN_SECONDS );

		return $steps;
	}

	/**
	 * Same as get_steps, but without the is_editing() BS
	 * @return Step[]
	 */
	public function get_real_steps( $query = [] ) {

		$query = wp_parse_args( $query, [
			'funnel_id' => $this->get_id(),
			'orderby'   => 'step_order',
			'order'     => 'ASC',
		] );

		$steps = $this->get_steps_db()->query( $query );
		$steps = array_map_to_step( $steps );

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
			wp_parse_str( wp_parse_url( wp_get_referer(), PHP_URL_QUERY ), $params );

			if ( get_array_var( $params, 'page' ) === 'gh_funnels'
			     && get_array_var( $params, 'action' ) === 'edit'
			     && isset_not_empty( $params, 'funnel' )
			     && absint( $params['funnel'] ) === $this->get_id()
			) {
				return true;
			}
		}

		return get_url_var( 'page' ) === 'gh_funnels'
		       && get_url_var( 'action' ) === 'edit'
		       && absint( get_url_var( 'funnel' ) ) === $this->get_id();
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
	 * A funnel has changes if any of its steps have changes, the step is inactive or deleted
	 *
	 * @return bool
	 */
	public function has_changes() {
		return array_any( $this->get_steps(), function ( Step $step ) {
			return $step->has_changes() || in_array( $step->step_status, [ 'inactive', 'deleted' ] );
		} );
	}

	/**
	 * Return wrapper function.
	 *
	 * @return array|bool
	 */
	public function export() {
		// only export real steps
		$json = $this->get_as_array();

		return array_merge( $json, [
			'steps' => $this->get_real_steps()
		] );
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

		/**
		 * Relly just here as a flag for importing, but theoretically you can modify the data directly
		 *
		 * @param array $data the import JSON
		 */
		do_action_ref_array( 'groundhogg/funnel/import/before', [ &$data ] );

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

			$data                = (array) $_step->data;
			$data['funnel_id']   = $this->get_id();
			$data['step_status'] = 'inactive'; // force status to inactive

			$step = new Step();
			$step->create( $data );

			$metadata   = json_decode( json_encode( $_step->meta ), true );
			$importdata = json_decode( json_encode( $_step->export ), true );

			$step->update_meta( $metadata );
			$step->import( $importdata );

			// Save the original ID from the donor funnel
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

		do_action( 'groundhogg/funnel/import/after', $this, $data );

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
}
