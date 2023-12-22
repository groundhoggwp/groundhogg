<?php

namespace Groundhogg\Bulk_Jobs;

// Exit if accessed directly
use Groundhogg\Plugin;
use Groundhogg\Utils\Micro_Time_Tracker;
use function Groundhogg\_nf;
use function Groundhogg\get_post_var;
use function Groundhogg\get_url_var;
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
	protected $completed = 0;

	protected function _skipped( $num = 0 ) {

		if ( ! $num ) {
			$this->skipped ++;

			return;
		}

		$this->skipped += $num;
	}

	protected function _completed( $num = 0 ) {

		if ( ! $num ) {
			$this->completed ++;

			return;
		}

		$this->completed += $num;
	}

	/**
	 * WPGH_Bulk_Jon constructor.
	 */
	public function __construct() {
		add_filter( "groundhogg/bulk_job/{$this->get_action()}/max_items", [ $this, 'max_items' ], 10, 2 );
		add_filter( "groundhogg/bulk_job/{$this->get_action()}/query", [ $this, 'query' ] );
		add_action( "groundhogg/bulk_job/{$this->get_action()}/ajax", [ $this, 'process' ] );
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
	public function max_items( $max, $items ) {

		$item = array_shift( $items );

		$fields = is_array( $item ) ? count( array_keys( $item ) ) : 1;

		$max       = intval( ini_get( 'max_input_vars' ) );
		$max_items = floor( $max / $fields );

		$max_override = absint( get_url_var( 'max_items' ) );

		if ( $max_override > 0 ) {
			return $max_override;
		}

		return min( $max_items, 100 );
	}

	/**
	 * Check to see if the current process will be the final one.
	 *
	 * @return mixed
	 */
	public function is_then_end() {
		$the_end = get_post_var( 'the_end', false );

		return filter_var( $the_end, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Do something when an item is skipped
	 *
	 * @param $item
	 */
	protected function skip_item( $item ) {
		$this->_skipped();
	}

	/**
	 * Process the bulk job.
	 */
	public function process() {

		$start = new Micro_Time_Tracker();

		if ( ! key_exists( 'the_end', $_POST ) ) {

			$error = new \WP_Error(
				'error',
				__( 'There was an error performing this process. This is most likely due to the PHP max_input_vars variable not being high enough.', 'groundhogg' )
			);

			wp_send_json_error( $error );
		}

		$items      = $this->get_items();
		$item_count = count( $items );

		ob_start();

		$this->pre_loop();

		foreach ( $items as $item ) {
			$this->process_item( $item );

			if ( $item_count > 1 ) {
				$this->_completed();
			}
		}

		$this->post_loop();

		// Clean up any output like DB errors.
		$output = ob_get_clean();

		$time = $start->time_elapsed_rounded( 3 );

		$response = [
			'complete'    => $item_count,
			'skipped'     => $this->skipped,
			'complete_nf' => _nf( $this->completed - $this->skipped ),
			'skipped_nf'  => _nf( $this->skipped ),
			'message'     => esc_html( $this->get_log_message( $this->completed, $time, $this->skipped ) ),
			'output'      => $output,
		];

		$the_end = get_post_var( 'the_end', false );

		if ( filter_var( $the_end, FILTER_VALIDATE_BOOLEAN ) ) {

			$response['return_url'] = $this->get_return_url();

			Plugin::instance()->notices->add( 'finished', $this->get_finished_notice() );

			$this->clean_up();
		}

		$this->send_response( $response );
	}

	/**
	 * Get the message to show in the log.
	 *
	 * @param $completed
	 * @param $skipped
	 * @param $time
	 *
	 * @return string
	 */
	protected function get_log_message( $completed, $time, $skipped = 0 ) {
		if ( $skipped > 0 ) {
			return sprintf( __( 'Processed %s items in %s seconds. Skipped %s items.', 'groundhogg' ), _nf( $completed ), $time, _nf( $skipped ) );
		} else {
			return sprintf( __( 'Processed %s items in %s seconds.', 'groundhogg' ), $completed, $time );
		}
	}

	/**
	 * Get a list of items from the bulk job.
	 *
	 * @return array
	 */
	public function get_items() {
		return isset_not_empty( $_POST, 'items' ) ? $_POST['items'] : [];
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
	 * @param $item mixed
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

