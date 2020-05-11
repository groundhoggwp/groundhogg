<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use Groundhogg\Event;
use function Groundhogg\enqueue_event;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use function Groundhogg\html;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Migrate_Waiting_Events extends Bulk_Job {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'show_upgrade_prompt' ] );
		parent::__construct();
	}

	public function show_upgrade_prompt() {
		if ( ! get_option( 'gh_migrate_waiting_events' ) || ! current_user_can( 'perform_bulk_actions' ) ) {
			return;
		}

		$update_button = html()->e( 'a', [
			'href'  => $this->get_start_url(),
			'class' => 'button button-secondary'
		], __( 'Migrate now!', 'groundhogg' ) );

		$notice = sprintf( __( "%s requires a database migration. Consider backing up your site before migrating. </p><p>%s", 'groundhogg' ), white_labeled_name(), $update_button );

		Plugin::$instance->notices->add( 'db-update', $notice );
	}

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'migrate_waiting_events';
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {
		if ( ! current_user_can( 'perform_bulk_actions' ) ) {
			return $items;
		}

		$query = get_db( 'events' )->query( [ 'status' => Event::WAITING ] );
		$ids   = wp_list_pluck( $query, 'ID' );

		return $ids;
	}

	/**
	 * Get the maximum number of items which can be processed at a time.
	 *
	 * @param $max int
	 * @param $items array
	 *
	 * @return int
	 */
	public function max_items( $max, $items ) {
		return min( 100, intval( ini_get( 'max_input_vars' ) ) );
	}

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 */
	protected function process_item( $item ) {
		$id = absint( $item );

		// Get the waiting event from the existing events table
		$event = new Event( $id, 'events' );

		// get the raw data
		$event_data = $event->get_data();
		unset( $event_data['ID'] );

		// Create the event in the queue
		enqueue_event( $event_data );

		// Delete the event from the history table
		$event->delete();
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
		delete_option( 'gh_migrate_waiting_events' );
	}

	/**
	 * Get the return URL
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_url( 'admin.php?page=groundhogg' );
	}
}