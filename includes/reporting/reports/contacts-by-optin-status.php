<?php

namespace Groundhogg\Reporting\Reports;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */
class Contacts_By_Optin_Status extends Contacts_By_Data {

	/**
	 * @return string
	 */
	public function get_id() {
		return 'contacts_by_optin_status';
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return __( 'Contacts By Opt-in Status', 'groundhogg' );
	}

	/**
	 * Return the key used to query the DB
	 *
	 * @return string
	 */
	public function get_key() {
		return 'optin_status';
	}
}
