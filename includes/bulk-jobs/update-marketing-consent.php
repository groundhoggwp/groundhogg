<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use function Groundhogg\encrypt;
use function Groundhogg\file_access_url;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_var;
use function Groundhogg\is_a_contact;
use function Groundhogg\multi_implode;
use Groundhogg\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Update_Marketing_Consent extends Bulk_Job {

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'gh_update_marketing_consent';
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {

		$query    = new Contact_Query();
		$contacts = $query->query( [] );
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
		return min( 500, intval( ini_get( 'max_input_vars' ) ) );
	}

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 */
	protected function process_item( $item ) {
		$contact = get_contactdata( $item );

		if ( ! is_a_contact( $contact ) ) {
			return;
		}

		if ( $contact->get_meta( 'gdpr_consent' ) === 'yes' ) {
			// Assume confirmation same as GDPR consent dates
			$contact->update_meta( 'marketing_consent', 'yes' );
			$contact->update_meta( 'marketing_consent_date', $contact->get_meta( 'gdpr_consent_date' ) );
		}
	}

	/**
	 * Get the args for the job.
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
		return admin_url( 'admin.php?page=gh_contacts' );
	}
}