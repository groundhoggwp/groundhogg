<?php

namespace Groundhogg\Admin\Pro;

use Groundhogg\Admin\Admin_Page;
use function Groundhogg\dashicon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Pro_Page extends Admin_Page {

	/**
	 * Add Ajax actions...
	 *
	 * @return mixed
	 */
	protected function add_ajax_actions() {
		// TODO: Implement add_ajax_actions() method.
	}

	/**
	 * Adds additional actions.
	 *
	 * @return mixed
	 */
	protected function add_additional_actions() {
		$pricing_url = add_query_arg( [
			'utm_source'   => 'admin-menu',
			'utm_medium'   => 'wp-dash',
			'utm_campaign' => 'go-pro',
			'utm_content'  => 'go-pro-link',
		], 'https://www.groundhogg.io/pricing/' );

		$discount = get_user_meta( wp_get_current_user()->ID, 'gh_free_extension_discount', true );

		if ( $discount ) {
			$pricing_url = add_query_arg( [ 'discount' => $discount ], $pricing_url );
		}

		wp_redirect( $pricing_url );
		die();
	}

	public function get_priority() {
		return 9999;
	}

	/**
	 * Get the page slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'gh_go_pro';
	}

	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	public function get_name() {
		return dashicon( 'star-filled' ) . esc_html__( 'Go Pro', 'groundhogg' );
	}

	/**
	 * The required minimum capability required to load the page
	 *
	 * @return string
	 */
	public function get_cap() {
		return 'edit_contacts';
	}

	/**
	 * Get the item type for this page
	 *
	 * @return mixed
	 */
	public function get_item_type() {
		// TODO: Implement get_item_type() method.
	}

	/**
	 * Enqueue any scripts
	 */
	public function scripts() {
		// TODO: Implement scripts() method.
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
	}
}
