<?php

namespace Groundhogg\DB;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Submission Meta DB
 *
 * Allows for the use of metadata api usage
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 2.0
 */
class Webhook_Meta extends Meta_DB {

	/**
	 * Get the DB suffix
	 *
	 * @return string
	 */
	public function get_db_suffix() {
		return 'gh_webhookmeta';
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
	 * Get the object type we're inserting/updating/deleting.
	 *
	 * @return string
	 */
	public function get_object_type() {
		return 'webhook';
	}
}