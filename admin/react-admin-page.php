<?php

namespace Groundhogg\Admin;

use Groundhogg\Supports_Errors;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use Groundhogg\Plugin;
use function Groundhogg\isset_not_empty;
use Groundhogg\Pointers;

/**
 * Abstract Admin Page
 *
 * This is a base class for all admin pages
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class React_Admin_Page extends Supports_Errors {

	/**
	 * Page constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register' ], $this->get_priority() );
		add_action( 'admin_init', [ $this, 'admin_init' ] );
	}

	public function admin_init() {
		$page = get_plugin_page_hook( $this->get_slug(), $this->get_parent_slug() );

		add_action( "load-$page", [ $this, 'remove_all_other_content' ] );
		add_action( "load-$page", [ $this, 're_add_needed_actions' ] );
	}

	/**
	 * Get the parent slug
	 *
	 * @return string
	 */
	protected function get_parent_slug() {
		return 'groundhogg';
	}

	/**
	 * Get the menu order between 1 - 99
	 *
	 * @return int
	 */
	public function get_priority() {
		return 10;
	}

	/**
	 * Get the page slug
	 *
	 * @return string
	 */
	abstract public function get_slug();

	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * The required minimum capability required to load the page
	 *
	 * @return string
	 */
	abstract public function get_cap();

	/**
	 * Register the page
	 */
	public function register() {

		$page = add_submenu_page(
			$this->get_parent_slug(),
			$this->get_name(),
			$this->get_name(),
			$this->get_cap(),
			$this->get_slug(),
			[ $this, 'page' ]
		);
	}

	public function remove_all_other_content() {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'admin_footer' );
		remove_all_actions( 'admin_head' );
	}

	public function re_add_needed_actions() {

		add_action( 'admin_head', function () {
			?>
            <style>
                #wpcontent {
                    padding-left: 0;
                }
            </style><?php
		} );

	}

	/**
	 * Get the screen title
	 */
	protected function get_title() {
		return $this->get_name();
	}

	abstract public function view();

	/**
	 * Call the function which will load any react scripts
	 * Output the main app div
	 */
	public function page() {

		?>
            <div id="groundhogg-app"></div>
		<?php

		wp_localize_script( 'groundhogg-react', 'groundhogg', [
			'current_app' => $this->get_slug()
		] );

		wp_enqueue_script( 'groundhogg-react' );
		wp_enqueue_style( 'groundhogg-react' );

		// any additional scripts/actions

		$action = get_url_var( 'action', 'view' );

		// enqueue relevant scripts etc...
		if ( method_exists( $this, $action ) ) {
			call_user_func( [ $this, $action ] );
		}
	}
}