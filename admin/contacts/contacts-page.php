<?php

namespace Groundhogg\Admin\Contacts;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Admin\React_Admin_Page;
use Groundhogg\Classes\Note;
use Groundhogg\Saved_Searches;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_query;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\normalize_files;
use Groundhogg\Plugin;
use Groundhogg\Contact;
use Groundhogg\Preferences;
use function Groundhogg\send_email_notification;
use function Groundhogg\validate_tags;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page gh_contacts
 *
 * This class registers the page with the admin menu, contains the private scripts to add contacts,
 * delete contacts, and manage contacts in the admin area
 *
 * There are several hooks you can use to add your own functionality to manage a contact in the default admin view.
 * The most relevant will likely be the following...
 *
 * add_action( 'wpgh_admin_update_contact_after', 'my_save_function' ); ($id)
 *
 * When saving custom information or doing something else. Runs after the admin saves a contact via the admin screen.
 *
 * @package     Admin
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Contacts_Page extends React_Admin_Page {

	/**
	 * Get the page slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'gh_contacts';
	}

	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Contacts', 'page_title', 'groundhogg' );
	}

	/**
	 * The required minimum capability required to load the page
	 *
	 * @return string
	 */
	public function get_cap() {
		return 'view_contacts';

	}

	public function get_priority() {
		return 5;
	}

	/**
	 *
	 */
	public function view() {



	}

}