<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Contact_Query;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_contactdata;
use function Groundhogg\is_a_contact;
use function Groundhogg\Ymd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fix_Birthdays extends Bulk_Job {

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'gh_fix_birthdays';
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			return $items;
		}

		$args = [
			'meta_query' => [
				'key'     => 'birthday',
				'compare' => 'EXISTS'
			],
		];

		$query    = new Contact_Query();
		$contacts = $query->query( $args );

		return wp_list_pluck( $contacts, 'ID' );
	}

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function process_item( $item ) {

		$contact = get_contactdata( $item );

		if ( ! is_a_contact( $contact ) ) {
			return;
		}

		$old_birthday = $contact->get_meta( 'birthday' );
		$birthday     = wp_parse_id_list( explode( '-', $old_birthday ) );
		$time         = mktime( 0, 0, 0, $birthday[1], $birthday[2], $birthday[0] );
		$birthday     = Ymd( $time );

		$contact->update_meta( 'birthday', $birthday );
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
			'meta_query' => [
				'key'     => 'birthday',
				'compare' => 'EXISTS'
			],
		] );
	}
}