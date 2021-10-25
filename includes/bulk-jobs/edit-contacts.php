<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Contact_Query;
use function Groundhogg\admin_page_url;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\get_request_query;
use function Groundhogg\is_a_contact;
use function Groundhogg\recount_tag_contacts_count;
use function Groundhogg\update_contact_with_map;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Edit_Contacts extends Bulk_Job {

	protected $edits = [];
	protected $query = null;

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'gh_edit_contacts';
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

		$args     = get_request_query();
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

		// Update the contact
		update_contact_with_map( $contact, $this->edits );

		do_action( 'groundhogg/edit_contacts/updated', $contact);
	}

	/**
	 * Do stuff before the loop
	 *
	 * @return void
	 */
	protected function pre_loop() {

		do_action( 'groundhogg/edit_contacts/pre_loop' );

		$this->edits = get_transient( 'gh_bulk_edit_fields' );
		$this->query = get_transient( 'gh_bulk_edit_query' );
	}

	/**
	 * do stuff after the loop
	 *
	 * @return void
	 */
	protected function post_loop() {
		do_action( 'groundhogg/edit_contacts/post_loop' );
	}

	/**
	 * Cleanup any options/transients/notices after the bulk job has been processed.
	 *
	 * @return void
	 */
	protected function clean_up() {
		delete_transient( 'gh_bulk_edit_fields' );
		delete_transient( 'gh_bulk_edit_query' );
		recount_tag_contacts_count();
	}

	/**
	 * Display the total import count.
	 *
	 * @return string
	 */
	protected function get_finished_notice() {
		$total_contacts_imported = get_db( 'contacts' )->count( $this->query );
		return sprintf( _n( '%s contact updated!', '%s contacts updated!', $total_contacts_imported, 'groundhogg' ), number_format_i18n( $total_contacts_imported ) );
	}

	/**
	 * Get the return URL
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_page_url( 'gh_contacts', $this->query );
	}
}