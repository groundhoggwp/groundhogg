<?php

namespace Groundhogg\Admin;

use function Groundhogg\get_request_var;
use function Groundhogg\groundhogg_icon;
use function Groundhogg\header_icon;

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

abstract class Tabbed_Admin_Page extends Admin_Page {

	/**
	 * array of [ 'name', 'slug' ]
	 *
	 * @return array[]
	 */
	abstract protected function get_tabs();

	/**
	 * @return array
	 */
	protected function parsed_tabs() {

		$tabs = $this->get_tabs();

		$tabs = array_map( function ( $tab ) {
			return wp_parse_args( $tab, [
				'name' => '',
				'slug' => 'gh_tab',
				'cap'  => 'view_contacts'
			] );
		}, $tabs );

		return array_filter( $tabs, function ( $tab ) {
			return current_user_can( $tab['cap'] );
		} );

	}

	/**
	 * Get the current tab.
	 *
	 * @return mixed
	 */
	public function get_current_tab() {
		$tabs = $this->parsed_tabs();

		return get_request_var( 'tab', array_shift( $tabs )['slug'] );
	}

	/**
	 * Retrieves the cap for the current tab
	 *
	 * @return string
	 */
	public function get_current_tab_cap() {

		$cap = false;

		foreach ( $this->parsed_tabs() as $tab ) {
			if ( $tab['slug'] === $this->get_current_tab() ) {
				$cap = $tab['cap'];
				break;
			}
		}

		/**
		 * Filter the capability for the current tab
		 *
		 * @param $cap    string the cap
		 * @param $tab    string the current tab
		 * @param $action string the current action
		 */
		return apply_filters( 'groundhogg/admin/get_current_tab_cap', $cap, $this->get_current_tab(), $this->get_current_action() );
	}

	/**
	 * Process the given action
	 */
	public function process_action() {

		if ( ! $this->get_current_action() || ! $this->verify_action() ) {
			return;
		}

		$base_url = remove_query_arg( [ '_wpnonce', 'action' ], wp_get_referer() );

		$callbacks = [
			"process_{$this->get_current_tab()}_{$this->get_current_action()}",
			"{$this->get_current_tab()}_{$this->get_current_action()}_process",
			"process_{$this->get_current_action()}",
			"{$this->get_current_action()}_process",
		];

		$exitCode = null;

		// Loop through potential callbacks and use first match.
		foreach ( $callbacks as $callback ) {
			if ( method_exists( $this, $callback ) ) {
				$exitCode = call_user_func( [ $this, $callback ] );
				break;
			} else if ( has_filter( "groundhogg/admin/{$this->get_slug()}/{$callback}" ) ) {
				$exitCode = apply_filters( "groundhogg/admin/{$this->get_slug()}/{$callback}", $exitCode );
				break;
			}
		}

		set_transient( 'groundhogg_last_action', $this->get_current_action(), 30 );

		if ( is_wp_error( $exitCode ) ) {
			$this->add_notice( $exitCode );

			return;
		}

		if ( is_string( $exitCode ) && esc_url_raw( $exitCode ) ) {
			wp_safe_redirect( $exitCode );
			die();
		}

		// Return to self if true response.
		if ( $exitCode === true ) {
			return;
		}

		$items = $this->get_items();

		// IF NULL return to main table
		if ( ! empty( $items ) ) {
			$base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_items() ) ), $base_url );
		}

		wp_safe_redirect( $base_url );
		die();
	}

	public function script_action() {
		parent::script_action();

		/**
		 * To enqueue related scripts for this page and tab
		 *
		 * @param $page   Admin_Page the current page
		 * @param $action string the current action
		 * @param $tab    string the current tab
		 */
		do_action( "groundhogg/admin/{$this->get_slug()}/{$this->get_current_tab()}/scripts", $this, $this->get_current_action(), $this->get_current_tab() );

		/**
		 * To enqueue related scripts for this page, tab, and specific action
		 *
		 * @param $page   Admin_Page
		 * @param $action string the current action
		 * @param $tab    string the current tab
		 *
		 */
		do_action( "groundhogg/admin/{$this->get_slug()}/{$this->get_current_tab()}/{$this->get_current_action()}/scripts", $this, $this->get_current_action(), $this->get_current_tab() );
	}

	/**
	 * Output HTML for the page tabs
	 */
	protected function do_page_tabs() {

		?>
        <!-- BEGIN TABS -->
        <h2 class="nav-tab-wrapper gh-nav">
			<?php foreach ( $this->parsed_tabs() as $id => $tab ): ?>
				<?php if ( ! current_user_can( $tab['cap'] ) ) {
					continue;
				} ?>
                <a href="?page=<?php echo $this->get_slug(); ?>&tab=<?php echo $tab['slug']; ?>"
                   class="nav-tab <?php echo $this->get_current_tab() == $tab['slug'] ? 'nav-tab-active' : ''; ?>"><?php _e( $tab['name'], 'groundhogg' ); ?></a>
			<?php endforeach; ?>
        </h2>
		<?php
	}

	/**
	 * Modified Admin page to support tabbing.
	 */
	public function page() {

		do_action( "groundhogg/admin/{$this->get_slug()}", $this );
		do_action( "groundhogg/admin/{$this->get_slug()}/{$this->get_current_tab()}", $this );

		?>
        <div id="<?php esc_attr_e( $this->get_slug() . '-header' ); ?>" class="gh-header admin-page-header tabbed-admin-page-header is-sticky no-padding display-flex flex-start" style="margin-left:-20px;padding-right: 20px">
			<?php header_icon(); ?>
            <h1><?php echo $this->get_title(); ?></h1>
			<?php $this->do_title_actions(); ?>
        </div>
		<?php $this->do_page_tabs(); ?>
        <script>
          (()=>{
            const pageHeader = document.getElementById( '<?php esc_attr_e( $this->get_slug() . '-header' ) ?>' )
            const parent = pageHeader.parentElement; // Get the parent element
            const navTabs = document.querySelector('h2.gh-nav')

            if (parent) {
              parent.prepend(navTabs);
              parent.prepend(pageHeader); // Move the element to the first child position
            }
          })()

        </script>
        <div class="wrap">
			<?php $this->notices(); ?>
            <hr class="wp-header-end">
			<?php

			if ( current_user_can( $this->get_current_tab_cap() ) ) {

				$methods = [
					"{$this->get_current_tab()}_{$this->get_current_action()}",
					"{$this->get_current_action()}_{$this->get_current_tab()}",
					"{$this->get_current_tab()}_view",
					"view_{$this->get_current_tab()}",
					$this->get_current_tab(),
					$this->get_current_action(),
					"view",
				];

				foreach ( $methods as $method ) {
					if ( method_exists( $this, $method ) ) {
						call_user_func( [ $this, $method ] );
						break;
					} else if ( has_action( "groundhogg/admin/{$this->get_slug()}/display/{$method}" ) ) {
						do_action( "groundhogg/admin/{$this->get_slug()}/display/{$method}", $this );
						break;
					}
				}

			}

			?>
        </div>
		<?php
	}

	public function view() {
		return $this->get_cap();
	}
}
