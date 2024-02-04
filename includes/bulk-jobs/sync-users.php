<?php

namespace Groundhogg\Bulk_Jobs;

use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\create_contact_from_user;
use function Groundhogg\is_option_enabled;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sync_Users extends Bulk_Job {

	const LIMIT = 500;

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'gh_sync_users';
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

		$user_count = count_users();
		$num_users  = $user_count['total_users'];

		$num_requests = floor( $num_users / self::LIMIT );

		return range( 0, $num_requests );
	}

	/**
	 * Process an item
	 *
	 * @param $batch int
	 *
	 * @return void
	 */
	protected function process_item( $batch ) {

		if ( ! current_user_can( 'add_contacts' ) ) {
			return;
		}

		$user_query = new \WP_User_Query( [
			'number' => self::LIMIT,
			'offset' => $batch * self::LIMIT
		] );

		$users = $user_query->get_results();

		foreach ( $users as $user ){
			create_contact_from_user( $user, is_option_enabled( 'gh_sync_user_meta' ) );

			$this->_completed();
		}

	}

	protected function get_log_message( $completed, $time, $skipped = 0 ) {
		return sprintf( 'Synced %s users in %s seconds.', $completed, $time );
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
