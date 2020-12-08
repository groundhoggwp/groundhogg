<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Bulk_Jobs\Bulk_Job;
use Groundhogg\Extension;
use Groundhogg\License_Manager;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_array_var;
use function Groundhogg\notices;

class Check_Licenses extends Bulk_Job {

	protected $license;

	public function get_action() {
		return 'check_licenses';
	}

	/**
	 * @param array $items
	 *
	 * @return array
	 */
	public function query( $items ) {

		$licenses = License_Manager::get_extension_licenses();

		return array_map( function ( $license_details, $item_id ) {
			$license_details['item_id'] = $item_id;
		}, $licenses, array_keys( $licenses ) );
	}

	public function max_items( $max, $items ) {
		return 1;
	}

	protected function pre_loop() {
	}

	/**
	 * Verify the license status
	 *
	 * @param mixed $item
	 */
	protected function process_item( $item ) {

		$item_id = absint( get_array_var( $item, 'item_id' ) );
		$license = sanitize_text_field( get_array_var( $item, 'license' ) );

		License_Manager::verify_license( $item_id, $license );
	}

	protected function post_loop() {
		// TODO: Implement post_loop() method.
	}

	protected function clean_up() {
		// TODO: Implement clean_up() method.
	}

	protected function get_finished_notice() {
		return __( 'Verified all licenses.', 'groundhogg' );
	}

	protected function get_return_url() {
		return admin_page_url( 'gh_settings', [ 'tab' => 'extensions' ] );
	}
}
