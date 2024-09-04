<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Classes\Background_Task;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\create_contact_from_user;
use function Groundhogg\get_request_var;
use function Groundhogg\is_option_enabled;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Process_Bg_Task extends Bulk_Job {

	const LIMIT = 500;

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'gh_process_bg_task';
	}

	public function max_items( $max, $items ) {
		return 1;
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {

		if ( ! current_user_can( 'add_contacts' ) ) {
			return $items;
		}

		$task = new Background_Task( absint( get_request_var( 'task' ) ) );

		return array_fill( 0, $task->task->get_batches_remaining(), $task->get_id() );
	}

	/**
	 * Process an item
	 *
	 * @param $task int
	 *
	 * @return void
	 */
	protected function process_item( $task ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$task = new Background_Task( $task );

		try {
			$task->process();
		} catch ( \Exception $exception ) {

		}

	}

	protected function get_log_message( $completed, $time, $skipped = 0 ) {
		return sprintf( 'Processed task in %s seconds.', $time );
	}

	/**
	 * Do stuff before the loop
	 *
	 * @return void
	 */
	protected function pre_loop() {
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
	}

	/**
	 * Get the return URL
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( [ [ [ 'type' => 'is_user' ] ] ] )
		] );
	}
}
