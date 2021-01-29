<?php

namespace Groundhogg\Admin\Reports;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Admin\React_App;

class Reports_Page extends Admin_Page {

	/**
	 * Add Ajax actions...
	 */
	protected function add_ajax_actions() {
		add_action( 'wp_ajax_groundhogg_refresh_dashboard_reports', [ $this, 'refresh_report_data' ] );
	}

	/**
	 * Adds additional actions.
	 */
	protected function add_additional_actions() {
		React_App::required_actions();
	}

	/**
	 * Get the page slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'gh_reporting';
	}

	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'Reporting';
	}

	/**
	 * The required minimum capability required to load the page
	 *
	 * @return string
	 */
	public function get_cap() {
		return 'view_reports';
	}

	public function get_priority() {
		return 2;
	}

	/**
	 * Get the item type for this page
	 */
	public function get_item_type() {
	}

	public function register_page_compat( $obj ) {
		return $obj;
	}

	public function page() {
		React_App::app();
	}

	/**
	 * Enqueue any scripts
	 */
	public function scripts() {
		React_App::scripts();
	}

	/**
	 * Add any help items
	 *
	 * @return mixed
	 */
	public function help() {
		// TODO: Implement help() method.
	}

	/**
	 * Output the basic view.
	 *
	 * @return mixed
	 */
	public function view() {
		// TODO: Implement view() method.
	}
}