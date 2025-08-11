<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Plugin;
use Groundhogg\Utils\Limits;
use Groundhogg\Utils\Micro_Time_Tracker;
use function Groundhogg\admin_page_url;
use function Groundhogg\db;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;

class Process_Events extends Bulk_Job {

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	public function get_action() {
		return 'run_queue';
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {

		$eventQuery = new Table_Query( db()->event_queue, 'eq' );
		$eventQuery->where()->equals( 'status', Event::WAITING )
		                    ->lessThan( 'time', time() )
		                    ->equals( 'claim', '' );

		$num_queued_events = $eventQuery->count();

		// Max 3 minutes
		$requests = ceil( $num_queued_events / 18 );

		if ( $requests < 1 ) {
			$requests = 1;
		}

		$items = [];

		for ( $i = 0; $i < $requests; $i ++ ) {
			$items[] = $i + 1;
		}

		return $items;
	}

	/**
	 * Get the maximum number of items which can be processed at a time.
	 *
	 * @param $max   int
	 * @param $items array
	 *
	 * @return int
	 */
	public function max_items( $max, $items ) {
		return 1;
	}

	/**
	 * Do stuff before the loop
	 *
	 * @return void
	 */
	protected function pre_loop() {
	}

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 */
	protected function process_item( $item ) {
	}

	/**
	 * Process the bulk job.
	 */
	public function process() {

		$time = new Micro_Time_Tracker();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- not needed
		if ( ! key_exists( 'the_end', $_POST ) ) {

			$error = new \WP_Error(
				'error',
				__( 'There was an error performing this process. This is most likely due to the PHP max_input_vars variable not being high enough.', 'groundhogg' )
			);

			wp_send_json_error( $error );
		}

		Limits::set_max_execution_time( 10 );

		$completed = Plugin::instance()->event_queue->run_queue();
		$failed    = count( Plugin::instance()->event_queue->get_errors() );

		$diff = $time->time_elapsed_rounded(3);

		if ( $failed === 0 ) {
			/* translators: 1: the number of events processed, 2: the time it took in seconds */
			$msg = sprintf( __( 'Processed %1$d events in %2$s seconds.', 'groundhogg' ), $completed, $diff );
		} else {
			/* translators: 1: the number of events processed, 2: the time it took in seconds, 3: how many events failed */
			$msg = sprintf( __( 'Processed %1$d events in %2$s seconds. %3$d events failed.', 'groundhogg' ), $completed - $failed, $diff, $failed );
		}

		$response = [
			'complete'         => 1,
			'skipped'          => 0,
			'completed_events' => $completed - $failed,
			'failed'           => $failed,
			'message'          => esc_html( $msg ),
		];

		$the_end = get_post_var( 'the_end', false );

		if ( filter_var( $the_end, FILTER_VALIDATE_BOOLEAN ) ) {

			$this->clean_up();

			$response['return_url'] = $this->get_return_url();

			Plugin::instance()->notices->add( 'finished', $this->get_finished_notice() );

		}

		$this->send_response( $response );
	}

	/**
	 * Return them to the events page
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_page_url( 'gh_events', [] );
	}

	/**
	 * do stuff after the loop
	 *
	 * @return void
	 */
	protected function post_loop() {
		// TODO: Implement post_loop() method.
	}

	/**
	 * Cleanup any options/transients/notices after the bulk job has been processed.
	 *
	 * @return void
	 */
	protected function clean_up() {
		// TODO: Implement clean_up() method.
	}
}
