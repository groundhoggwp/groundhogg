<?php

namespace Groundhogg\Bulk_Jobs;

// Exit if accessed directly
use function Groundhogg\_nf;
use function Groundhogg\get_array_var;
use function Groundhogg\get_post_var;
use Groundhogg\Plugin;
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bulk job
 *
 * Provides a framework for extensions which require bulk jobs through the bulk job processor.
 *
 * @since       1.3
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
abstract class Bulk_Job {

	/**
	 * keep track of skipped items.
	 *
	 * @var int
	 */
	protected $skipped = 0;

	protected static $is_rest = false;

	protected $items_per_request;
	protected $items_offset;
	protected $context;
	protected $job_id; // random bulkjob id to manage the transient

	/**
	 * WPGH_Bulk_Jon constructor.
	 */
	public function __construct() {
		add_filter( "groundhogg/bulk_job/{$this->get_action()}/max_items", [ $this, 'max_items' ], 10, 2 );
		add_filter( "groundhogg/bulk_job/{$this->get_action()}/query", [ $this, 'query' ] );
		add_action( "groundhogg/bulk_job/{$this->get_action()}/ajax", [ $this, 'process' ] );

		add_action( "groundhogg/bulk_job/{$this->get_action()}/rest", [ $this, 'rest_handler' ], 10, 4 );
	}

	protected static function is_rest() {
		return self::$is_rest;
	}

	protected static function set_is_rest( $is_rest ) {
		return self::$is_rest = $is_rest;
	}

	/**
	 * @param $items_per_request int the number of items to be processed during this request
	 * @param $items_offset      int the offset, the number of items processed so far
	 * @param $context           array will contain relevant information about the request for the items
	 * @param $job_id            int|string  id to manage custom transient to store the data
	 */
	public function rest_handler( $items_per_request, $items_offset, $context, $job_id ) {

		self::set_is_rest( true );

		$this->items_per_request = absint( $items_per_request );
		$this->items_offset      = absint( $items_offset );
		$this->context           = $context;
		$this->job_id            = $job_id;

		$this->process();
	}

	/**
	 * Get a value from the context
	 *
	 * @param $key
	 * @param $default
	 *
	 * @return mixed
	 */
	protected function get_context( $key = '', $default = false ) {
		return get_array_var( $this->context, $key, $default );
	}

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	abstract public function get_action();

	/**
	 * Start the bulk job by redirecting to the bulk jobs page.
	 *
	 * @param $additional array any additional arguments to add to the link
	 */
	public function start( $additional = [] ) {
		wp_redirect( $this->get_start_url( $additional ) );
		die();
	}

	/**
	 * Get the URL which will start the job.
	 *
	 * @param $additional array any additional arguments to add to the link
	 *
	 * @return string
	 */
	public function get_start_url( $additional = [] ) {
		return add_query_arg( array_merge( [ 'action' => $this->get_action() ], $this->get_start_query_args(), $additional ), admin_url( 'admin.php?page=gh_bulk_jobs' ) );
	}

	/**
	 * Get additional query args if any
	 *
	 * @return array
	 */
	protected function get_start_query_args() {
		return [];
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	abstract public function query( $items );

	/**
	 * Get the maximum number of items which can be processed at a time.
	 *
	 * @param $max   int
	 * @param $items array
	 *
	 * @return int
	 */
	abstract public function max_items( $max, $items );

	/**
	 * Do something when an item is skipped
	 *
	 * @param $item
	 */
	protected function skip_item( $item ) {
		$this->skipped ++;
	}

	/**
	 * Process the bulk job.
	 */
	public function process() {

		$start = microtime( true );

		if ( ! self::is_rest() && ! key_exists( 'the_end', $_POST ) ) {

			$error = new \WP_Error(
				'error',
				__( 'There was an error performing this process. This is most likely due to the PHP max_input_vars variable not being high enough.', 'groundhogg' )
			);

			wp_send_json_error( $error );
		}

		$items = $this->get_items();
		if (self::$is_rest) {
			$total = count( $items );
			$items = array_slice( (array) $items, $this->items_offset, $this->items_per_request );
		}
		$completed = 0;

		$this->pre_loop();

		foreach ( $items as $item ) {
		if (self::$is_rest){
			$this->process_item( is_object( $item ) ? (array) $item : $item );
		} else {
			$this->process_item( $item );
		}

			$completed ++;
		}

		$this->post_loop();

		// Clean up any output like DB errors.
		$output = ob_get_clean();

		$end  = microtime( true );
		$diff = round( $end - $start, 2 );

		if ( $this->skipped > 0 ) {
			$msg = sprintf( __( 'Processed %s items in %s seconds. Skipped %s items.', 'groundhogg' ), _nf( $completed ), _nf( $diff, 2 ), _nf( $this->skipped ) );
		} else {
			$msg = sprintf( __( 'Processed %s items in %s seconds.', 'groundhogg' ), _nf( $completed ), _nf( $diff, 2 ) );
		}

		$response   = [
			'complete'      => $completed - $this->skipped,
			'skipped'       => $this->skipped,
			'complete_nf'   => _nf( $completed - $this->skipped ),
			'skipped_nf'    => _nf( $this->skipped ),
			'message'       => esc_html( $msg ),
			'output'        => $output,
			'finished'    => false
		];

		if (self::$is_rest) {
			$next_index = $this->items_offset + $this->items_per_request;
			$response['next_index'   ] = $next_index ;
			$response['total_records'] = $total ;
			$response['next_request' ] = ( $next_index > $total ) ? false : true ;
		}



		if ( $this->is_the_end()  || ( self::$is_rest && $next_index> $total ) ) {
			$this->clean_up();

			$response[ 'return_url' ] = $this->get_return_url();
			$response[ 'finished' ]   = true;

			Plugin::instance()->notices->add( 'finished', $this->get_finished_notice() );

		}

		$this->send_response( $response );
	}

	/**
	 * Get a list of items from the bulk job.
	 *
	 * @return array
	 */
	public function get_items() {
		return self::is_rest() ?
			$this->get_items_restfully() :
			get_post_var( 'items', [] );
	}

	/**
	 * Get items restfully.
	 *
	 * @return array
	 */
	public function get_items_restfully() {

		if ( get_transient( $this->get_action() . '-' . $this->job_id ) ) {
			return get_transient( $this->get_action() . '-' . $this->job_id );
		}

		//get items
		$items = $this->query( [] );

		if ( count( $items ) > $this->items_per_request ) {
			// set transient for second time to speed up the process
			set_transient( $this->get_action() . '-' . $this->job_id, $items );
		}

		return $items;

	}

	/**
	 * Check to see if the current process will be the final one.
	 *
	 * @return mixed
	 */
	public function is_the_end() {

		if ( self::is_rest() ) {
			return $this->is_the_end_restfully();
		}

		return filter_var( get_post_var( 'the_end', false ), FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Check whether the the end has been reached restfully/
	 */
	public function is_the_end_restfully() {

		delete_transient( $this->get_action() . '-' . $this->job_id );

		return true;
	}

	/**
	 * Do stuff before the loop
	 *
	 * @return void
	 */
	abstract protected function pre_loop();

	/**
	 * Process an item
	 *
	 * @param $item mixed|array
	 * @param $args array
	 *
	 * @return void
	 */
	abstract protected function process_item( $item );

	/**
	 * do stuff after the loop
	 *
	 * @return void
	 */
	abstract protected function post_loop();

	/**
	 * Cleanup any options/transients/notices after the bulk job has been processed.
	 *
	 * @return void
	 */
	abstract protected function clean_up();

	/**
	 * Get the return url.
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_url( 'admin.php?page=groundhogg' );
	}

	/**
	 * get text for the finished notice
	 *
	 * @return string
	 */
	protected function get_finished_notice() {
		return _x( 'Job finished!', 'notice', 'groundhogg' );
	}

	/**
	 * @param $response
	 */
	protected function send_response( $response ) {
		wp_send_json( apply_filters( "groundhogg/bulk_job/{$this->get_action()}/send_response", $response ) );
	}

}

