<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact_Query;
use function Groundhogg\get_db;
use Groundhogg\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Migrate_Form_Impressions extends Bulk_Job {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'show_upgrade_prompt' ] );
		parent::__construct();
	}

	public function show_upgrade_prompt() {
		if ( get_option( 'gh_migrate_form_impressions' ) && current_user_can( 'manage_options' ) ) {
			Plugin::$instance->notices->add( 'db-update', "<a href='{$this->get_start_url()}'>" . __( 'Thank you for updating to 2.0! Please click here to update your database.', 'groundhogg' ) . "</a>" );
		}
	}

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'migrate_impressions';
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {
		if ( ! current_user_can( 'edit_funnels' ) ) {
			return $items;
		}

		$query = get_db( 'activity' )->query( [ 'activity_type' => Activity::FORM_IMPRESSION ] );
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
		if ( ! current_user_can( 'edit_funnels' ) ) {
			return $max;
		}

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
		$item     = absint( $item );
		$activity = new Activity( $item );
		if ( $activity->exists() ) {
			get_db( 'form_impressions' )->add( [
				'timestamp' => $activity->get_timestamp(),
				'form_id'   => $activity->get_step_id()
			] );
			$activity->delete();
		}
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
		delete_option( 'gh_migrate_form_impressions' );
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