<?php

namespace Groundhogg\Admin\Contacts;

use Groundhogg\Contact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-06-05
 * Time: 12:02 PM
 */
abstract class Tab {

	public function __construct() {
		add_filter( 'groundhogg/admin/contact/record/tabs', [ $this, 'register_tab' ] );
		add_action( "groundhogg/admin/contact/record/tab/{$this->get_id()}", [ $this, 'content' ] );
	}

	/**
	 * Register the tab
	 *
	 * @param $tabs
	 *
	 * @return mixed
	 */
	public function register_tab( $tabs ) {
		if ( current_user_can( $this->get_cap() ) ) {
			$tabs[ $this->get_id() ] = $this->get_name();
		}

		return $tabs;
	}

	public function get_cap() {
		return 'view_contacts';
	}

	/**
	 * @return string
	 */
	abstract public function get_id();

	/**
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Output tab content.
	 *
	 * @param $contact Contact
	 *
	 * @return void
	 */
	abstract public function content( $contact );

}
