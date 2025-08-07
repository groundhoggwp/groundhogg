<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Broadcast;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_url_var;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-04
 * Time: 3:22 PM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Broadcast_Scheduler extends Bulk_Job {

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'gh_schedule_broadcast';
	}

	/**
	 * @var Broadcast
	 */
	private $broadcast;

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {
		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			return $items;
		}

		$broadcast = new Broadcast( absint( get_url_var( 'broadcast' ) ) );
		set_transient( 'gh_current_broadcast_id', $broadcast->get_id() );

		$items_remaining = $broadcast->get_items_remaining();

		$batches = ceil( $items_remaining / $broadcast::BATCH_LIMIT );

		return range( 1, $batches );
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
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 */
	protected function process_item( $item ) {

		if ( ! current_user_can( 'schedule_broadcasts' ) ) {
			return;
		}

		$items = $this->broadcast->enqueue_batch();

		if ( $items ) {
			$this->_completed( $items );
		} else {
			// force a retry
			wp_send_json_error();
		}
	}

	/**
	 * Do stuff before the loop
	 *
	 * @return void
	 */
	protected function pre_loop() {
		$broadcast_id    = absint( get_transient( 'gh_current_broadcast_id' ) );
		$this->broadcast = new Broadcast( $broadcast_id );
		if ( ! $this->broadcast->exists() ) {
			wp_send_json_error();
		}
	}

	/**
	 * do stuff after the loop
	 *
	 * @return void
	 */
	protected function post_loop() {
	}

	/**
	 * Cleanup any options/transients/notices after the bulk job has been processed.
	 *
	 * @return void
	 */
	protected function clean_up() {
		delete_transient( 'gh_current_broadcast_id' );
	}

	/**
	 * Get the return URL
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_page_url( 'gh_broadcasts', [ 'view' => 'scheduled' ] );
	}

	/**
	 * @return string|null
	 */
	protected function get_finished_notice() {
		return esc_html_x( 'Broadcast scheduled!', 'notice', 'groundhogg' );
	}
}
