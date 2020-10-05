<?php

namespace Groundhogg\DB;

// Exit if accessed directly
use Groundhogg\Contact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact Meta DB
 *
 * Allows for the use of metadata api usage
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Contact_Meta extends Meta_DB {

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_contactmeta';
	}

	/**
	 * Get the DB version
	 *
	 * @return mixed
	 */
	public function get_db_version() {
		return '2.0';
	}

	/**
	 * Get the object type we're inserting/updateing/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'contact';
	}

	/**
	 * Handle the updating of IDs when a contact is merged.
	 * Only update meta values that do not already exist for the primary contact record
	 * Then delete the rest
	 *
	 * @param $primary Contact
	 * @param $other Contact
	 */
	public function contacts_merged( $primary, $other ) {

		global $wpdb;

		// Change meta keys but only ones the primary contact does not already have...
		$wpdb->query( "UPDATE {$this->table_name} SET `contact_id` = {$primary->get_id()}
		WHERE `contact_id` = {$other->get_id()} AND `meta_key` NOT IN (
			SELECT `meta_key` FROM {$this->table_name} WHERE `contact_id` = {$primary->get_id()}
		); " );

		$this->bulk_delete( [
			'contact_id' => $other->get_id()
		] );
	}
}