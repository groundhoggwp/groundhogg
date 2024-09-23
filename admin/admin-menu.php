<?php

namespace Groundhogg\Admin;

use Groundhogg\Admin\Broadcasts\Broadcasts_Page;
use Groundhogg\Admin\Bulk_Jobs\Bulk_Job_Page;
use Groundhogg\Admin\Campaigns\Campaigns_Page;
use Groundhogg\Admin\Contacts\Contacts_Page;
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
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_url_var;
use function Groundhogg\groundhogg_icon;
use function Groundhogg\has_premium_features;
use function Groundhogg\is_admin_bar_widget_disabled;
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
		add_filter( 'groundhogg/admin/menu_priority', [ $this, 'force_priority' ], 10, 2 );
	}

	/**
	 * We re-organized the menu, so we'll hack the priority so that everything
	 * is in the right place without having to update all of the addons
	 *
	 * @param            $priority
	 * @param Admin_Page $page
	 *
	 * @return bool|mixed
	 */
	function force_priority( $priority, Admin_Page $page ) {

		$slug = $page->get_slug();

		// Known slugs in addons that need to be moved
		$preset_order = [
			'gh_appointments' => 25,
			'gh_pipeline'     => 35,
			'gh_ls_rules'     => 40,
			'gh_payments'     => 45,
			'gh_rsp'          => 45,
			'gh_sms'          => 65,
			'gh_superlinks'   => 75,
			'gh_calendars'    => 80,
			'gh_replacements' => 120,
			'gh_aws'          => 125,
			'gh_extensions'   => 150,
		];

		return get_array_var( $preset_order, $slug, $priority );
	}

	/**
	 * Add the .groundhogg-admin-page body class to groundhogg pages
	 *
	 * @param $class
	 *
	 * @return mixed|string
	 */
	public function admin_body_class( $class ) {

		$current_page = get_url_var( 'page' );

		if ( ! $current_page ) {
			return $class;
		}

		if ( preg_match( '/^gh_/', $current_page ) || $current_page === 'groundhogg' ) {
			$class .= " groundhogg-admin-page $current_page";
		}

		return $class;
	}

	/**
	 * Add the admin bar widget
	 *
	 * @param $admin_bar \WP_Admin_Bar
	 */
	public function admin_bar( $admin_bar ) {

		if ( is_admin_bar_widget_disabled() || ! current_user_can( 'view_contacts' ) ) {
			return;
		}

		$admin_bar->add_node( [
			'id'     => 'groundhogg',
			'title'  => is_white_labeled() ? white_labeled_name() : groundhogg_icon( 20, false ),
			'parent' => 'top-secondary',
			'meta'   => [
				'class' => 'groundhogg-admin-bar-menu',
				'title' => white_labeled_name()
			]
		] );

		$admin_contact = get_contactdata();

		if ( $admin_contact && $admin_contact->exists() ) {
			$admin_bar->add_node(
				array(
					'parent' => 'user-actions',
					'id'     => 'contact-info',
					'title'  => __( 'Contact Record', 'groundhogg' ),
					'href'   => $admin_contact->admin_link(),
					'meta'   => array(
						'tabindex' => - 1,
					),
				)
			);
		}


	}

	/**
	 * Set up the menu pages for the plugin
	 */
	public function init_admin() {

		$this->welcome  = new Welcome_Page();
		$this->contacts = new Contacts_Page();
		$this->tags     = new Tags_Page();

		$this->emails     = new Emails_Page();
		$this->broadcasts = new Broadcasts_Page();
		$this->funnels    = new Funnels_Page();
		$this->campaigns  = new Campaigns_Page();

		$this->events    = new Events_Page();
		$this->tools     = new Tools_Page();
		$this->settings  = new Settings_Page();
		$this->bulk_jobs = new Bulk_Job_Page();

		$this->reporting = new Reports_Page();

		if ( ! is_white_labeled() || current_user_can( 'manage_gh_white_label' ) ) {
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
