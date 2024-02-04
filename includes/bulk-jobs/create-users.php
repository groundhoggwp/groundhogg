<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Contact_Query;
use function Groundhogg\create_contact_from_user;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Create_Users extends Bulk_Job {

	protected $send_notification = false;
	protected $role = 'subscriber';

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'gh_create_users';
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {
		if ( ! current_user_can( 'add_users' ) ) {
			return $items;
		}

		$query = new Contact_Query();
		$args  = [
			'tags_include' => wp_parse_id_list( get_request_var( 'tags_include' ) ),
			'tags_exclude' => wp_parse_id_list( get_request_var( 'tags_exclude' ) )
		];

		$contacts = $query->query( $args );
		$ids      = wp_list_pluck( $contacts, 'ID' );

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
		if ( ! current_user_can( 'add_users' ) ) {
			return $max;
		}

		return min( 20, intval( ini_get( 'max_input_vars' ) ) );
	}

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 */
	protected function process_item( $item ) {
		if ( ! current_user_can( 'add_users' ) ) {
			return;
		}

		$contact = get_contactdata( $item );

		// Do no run if the contact already has a linked user account
		if ( ! $contact || $contact->get_userdata() || email_exists( $contact->get_email() ) ) {
			return;
		}

		$uid = wp_create_user( $contact->get_email(), wp_generate_password(), $contact->get_email() );

		if ( $uid && ! is_wp_error( $uid ) ) {

			if ( $this->send_notification ) {
				wp_new_user_notification( $uid, null, 'user' );
			}

			$contact->update( [ 'user_id' => $uid ] );

			$contact->get_userdata()->add_role( $this->role );
		}

	}

	/**
	 * Do stuff before the loop
	 *
	 * @return void
	 */
	protected function pre_loop() {

		$config = get_transient( 'gh_create_user_job_config' );

		$this->send_notification = get_array_var( $config, 'send_email' );
		$this->role              = get_array_var( $config, 'role', 'subscriber' );
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
		delete_transient( 'gh_send_account_email' );
	}


	/**
	 * Get the return URL
	 *
	 * @return string
	 */
	protected function get_return_url() {
		$url = admin_url( 'users.php' );

		return $url;
	}
}
