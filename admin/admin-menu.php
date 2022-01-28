<?php

namespace Groundhogg\Admin;

use Groundhogg\Admin\Broadcasts\Broadcasts_Page;
use Groundhogg\Admin\Bulk_Jobs\Bulk_Job_Page;
use Groundhogg\Admin\Contacts\Contacts_Page;
use Groundhogg\Admin\Contacts\Info_Cards;
use Groundhogg\Admin\Contacts\Tables\Contact_Table_Columns;
use Groundhogg\Admin\Dashboard\Dashboard_Widgets;
use Groundhogg\Admin\Emails\Emails_Page;
use Groundhogg\Admin\Events\Events_Page;
use Groundhogg\Admin\Funnels\Funnels_Page;
use Groundhogg\Admin\Guided_Setup\Guided_Setup;
use Groundhogg\Admin\Help\Help_Page;
use Groundhogg\Admin\Pro\Pro_Page;
use Groundhogg\Admin\Reports\Reports_Page;
use Groundhogg\Admin\Settings\Settings_Page;
use Groundhogg\Admin\Tags\Tags_Page;
use Groundhogg\Admin\Tools\Tools_Page;
use Groundhogg\Admin\User\Admin_User;
use Groundhogg\Admin\Welcome\Welcome_Page;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_url_var;
use function Groundhogg\has_premium_features;
use function Groundhogg\is_admin_bar_widget_disabled;
use function Groundhogg\is_option_enabled;
use function Groundhogg\is_pro_features_active;
use function Groundhogg\is_white_labeled;
use function Groundhogg\isset_not_empty;
use function Groundhogg\white_labeled_name;

/**
 * Admin Manager to manage databases in Groundhogg
 *
 * Class Manager
 *
 * @package Groundhogg\Admin
 */
class Admin_Menu {

	/**
	 * @var Admin_Page[]
	 */
	protected $pages = [];

	/**
	 * Manager constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init_admin' ] );
		add_action( 'admin_bar_menu', [ $this, 'admin_bar' ], 999 );
		add_filter( 'admin_body_class', [ $this, 'admin_body_class' ] );
	}

	public function admin_body_class( $class ) {

		$current_page = get_url_var( 'page' );

		if ( ! $current_page ){
			return $class;
		}

		if ( preg_match( '/^gh_/', $current_page ) || $current_page === 'groundhogg' ){
			$class .= ' groundhogg-admin-page';
		}

		return $class;
	}

	/**
	 * @param $admin_bar \WP_Admin_Bar
	 */
	public function admin_bar( $admin_bar ) {

		if ( is_admin_bar_widget_disabled() ) {
			return;
		}

		$admin_bar->add_node( [
			'id'     => 'groundhogg',
			'title'  => is_white_labeled() ? white_labeled_name() : '<svg height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="11.4 13.2 212.1 237.9">
  <linearGradient id="a" x1="35.6" x2="199.3" y1="214" y2="50.4" gradientUnits="userSpaceOnUse">
    <stop offset="0.3" stop-color="#db851a"/>
    <stop offset="1" stop-color="#db6f1a"/>
  </linearGradient>
  <path fill="url(#a)" d="M22.7 64.4l83.4-48.2c7-4 15.7-4 22.7 0l83.4 48.2c7 4 11.3 11.5 11.3 19.6v96.3c0 8.1-4.3 15.6-11.3 19.6l-83.4 48.2c-7 4-15.7 4-22.7 0L22.7 200c-7-4-11.3-11.5-11.3-19.6V84a22.5 22.5 0 0111.3-19.6z"/>
  <path fill="#db5100" d="M183.5 140.8v4.9A66.1 66.1 0 11164 98.8l-24.5 24.3a31.4 31.4 0 103.6 40.9h-25.6v-23.3h66z"/>
  <path fill="#fff" d="M183.5 126.1v4.9A66.1 66.1 0 11164 84.1l-24.5 24.3a31.4 31.4 0 103.6 40.9h-25.6V126h66z"/>
</svg>',
			'parent' => 'top-secondary',
			'meta'   => [
				'class' => 'groundhogg-admin-bar-menu',
				'title' => white_labeled_name()
			]
		] );

		if ( get_contactdata() ) {
			$admin_bar->add_node(
				array(
					'parent' => 'user-actions',
					'id'     => 'contact-info',
					'title'  => __( 'Contact Record' ),
					'href'   => admin_page_url( 'gh_contacts', [ 'action'  => 'edit',
					                                             'contact' => get_contactdata()->ID
					] ),
					'meta'   => array(
						'tabindex' => - 1,
					),
				)
			);
		}


	}

	/**
	 * Setup the base DBs for the plugin
	 */
	public function init_admin() {

		$this->welcome  = new Welcome_Page();
		$this->contacts = new Contacts_Page();
		$this->tags     = new Tags_Page();

		if ( ! is_pro_features_active() || ! is_option_enabled( 'gh_use_advanced_email_editor' ) ) {
			$this->emails = new Emails_Page();
		}

		$this->broadcasts = new Broadcasts_Page();
		$this->funnels    = new Funnels_Page();

		$this->events    = new Events_Page();
		$this->tools     = new Tools_Page();
		$this->settings  = new Settings_Page();
		$this->bulk_jobs = new Bulk_Job_Page();

		$this->reporting = new Reports_Page();
//        $this->dashboard = new Dashboard_Widgets();

		if ( ! is_white_labeled() ) {
			$this->guided_setup = new Guided_Setup();
			$this->help         = new Help_Page();

			if ( ! has_premium_features() ) {
				$this->pro = new Pro_Page();
			}
		}

		// user profile edits...
		new Admin_User();

		do_action( 'groundhogg/admin/init', $this );
	}

	/**
	 * Set the data to the given value
	 *
	 * @param $key string
	 *
	 * @return Admin_Page|Funnels_Page|Contacts_Page
	 */
	public function get_page( $key ) {
		return $this->$key;
	}

	/**
	 * Magic get method
	 *
	 * @param $key string
	 *
	 * @return bool|Admin_Page
	 */
	public function __get( $key ) {
		if ( isset_not_empty( $this->pages, $key ) ) {
			return $this->pages[ $key ];
		}

		return false;
	}

	/**
	 * Set the data to the given value
	 *
	 * @param $key   string
	 * @param $value Admin_Page
	 */
	public function __set( $key, $value ) {
		$this->pages[ $key ] = $value;
	}

}
