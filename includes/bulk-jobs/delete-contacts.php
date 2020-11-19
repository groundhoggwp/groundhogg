<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use function Groundhogg\get_request_query;
use Groundhogg\Plugin;
use function Groundhogg\recount_tag_contacts_count;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Delete_Contacts extends Bulk_Job {

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'gh_delete_contacts';
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {
		if ( ! current_user_can( 'delete_contacts' ) ) {
			return $items;
		}

		$args = get_request_query();
		if ( $this::$is_rest ) {
			if (empty($this->get_context( 'tags_include'  , []))){
				return  [];
			}

			$args = [
				'tags_include' => $this->get_context( 'tags_include' )
			];


		}


		$query    = new Contact_Query();
		$contacts = $query->query( $args );
		$ids      = wp_list_pluck( $contacts, 'ID' );

		return $ids;
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
		if ( ! current_user_can( 'delete_contacts' ) ) {
			return $max;
		}

		return min( 300, intval( ini_get( 'max_input_vars' ) ) );
	}

//	/**
//	 * Get the items from a restful request
//	 *
//	 * @return array
//	 */
//	public function get_items_restfully() {
//
//		$query = $this->get_context( 'query', [] );
//
//		$query[ 'number' ] = $this->items_per_request;
//		$query[ 'offset' ] = $this->items_offset;
//
//		$query = new Contact_Query( $query );
//
//		wp_send_json(wp_parse_id_list( wp_list_pluck( $query->items, 'ID' ) ));
//		return wp_parse_id_list( wp_list_pluck( $query->items, 'ID' ) );
//	}

	/**
	 * Then end has been reached once no more contacts match the query
	 *
	 * @return bool
	 */
	public function is_the_end_restfully() {

		$query = $this->get_context( 'query', [] );

		$query[ 'number' ] = - 1;
		$query[ 'offset' ] = 0;

		$c_query = new Contact_Query();
		$count   = $c_query->count( $query );

		return $count === 0;
	}

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 */
	protected function process_item( $item ) {
		$contact = new Contact( $item );

		if ( $contact->exists() ) {
			$contact->delete();
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
		recount_tag_contacts_count();
	}

	/**
	 * Get the return URL
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_url( 'admin.php?page=gh_contacts' );
	}
}