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

abstract class Admin_Page extends Supports_Errors {

	protected $screen_id;

	/**
	 * Page constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register' ], $this->get_priority() );

		if ( wp_doing_ajax() ) {
			$this->add_ajax_actions();
		}

		if ( $this->is_current_page() ) {

			add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'register_pointers' ] );
			add_filter( 'admin_title', [ $this, 'admin_title' ], 10, 2 );

			add_filter( "set-screen-option", [ $this, 'set_screen_options' ], 10, 3 );
			add_filter( "set_screen_option_{$this->get_slug()}_per_page", [
				$this,
				'set_screen_option_per_page'
			], 10, 3 );

			add_action( 'admin_init', [ $this, 'process_action' ] );

			$this->add_additional_actions();
		}
	}

	/**
	 * Modify the tab title...
	 *
	 * @param $admin_title string
	 * @param $title       string
	 *
	 * @return mixed string
	 */
	public function admin_title( $admin_title, $title ) {
		return $admin_title;
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
	 * Add Ajax actions...
	 *
	 * @return mixed
	 */
	abstract protected function add_ajax_actions();

	/**
	 * Adds additional actions.
	 *
	 * @return mixed
	 */
	abstract protected function add_additional_actions();

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
	 * Get the item type for this page
	 *
	 * @return mixed
	 */
	abstract public function get_item_type();

	/**
	 * Adds an S
	 *
	 * @return string
	 */
	public function get_item_type_plural() {
		return $this->get_item_type() . 's';
	}

	/**
	 * Whether this page is the current page
	 *
	 * @return bool
	 */
	public function is_current_page() {
		// Return basic check to see if we are on the current page doing a normal request
		if ( ! wp_doing_ajax() ) {
			return get_request_var( 'page' ) === $this->get_slug();
		}

		return false;
	}

	/**
	 * Enqueue any scripts
	 */
	abstract public function scripts();

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

		$this->screen_id = $page;

		add_action( "load-" . $page, [ $this, 'help' ] );
		add_action( "load-" . $page, [ $this, 'screen_options' ] );
	}

	/**
	 * Add some screen options
	 */
	public function screen_options() {

		$args = array(
			'label'   => __( 'Per page', 'groundhogg' ),
			'default' => 20,
			'option'  => $this->get_slug() . '_per_page'
		);


		if ( $this->get_current_action() === 'view' ) {
			add_screen_option( 'per_page', $args );
		}
	}

	/**
	 * @param $keep
	 * @param $option
	 * @param $value
	 *
	 * @return mixed
	 */
	public function set_screen_options( $keep, $option, $value ) {

		if ( $this->get_slug() . '_per_page' === $option ) {
			return $value;
		}

		return $keep;
	}

	/**
	 * Save screen option per page
	 *
	 * @param $keep
	 * @param $option
	 * @param $value
	 *
	 * @return mixed|void
	 */
	public function set_screen_option_per_page( $keep, $option, $value ) {
		return absint( $value );
	}

	/**
	 * Add any help items
	 *
	 * @return mixed
	 */
	abstract public function help();

	/**
	 * @return void
	 */
	public function register_pointers() {
		new Pointers( $this->get_pointers() );
	}

	/**
	 * @return array
	 */
	protected function get_pointers() {
		$pointers = [];

		if ( method_exists( $this, 'get_pointers_' . $this->get_current_action() ) ) {
			$pointers = call_user_func( [ $this, 'get_pointers_' . $this->get_current_action() ] );
		}

		return apply_filters( "groundhogg/admin/{$this->get_slug()}/{$this->get_current_action()}/pointers", $pointers );
	}

	/**
	 * Get the affected items on this page
	 *
	 * @return array|bool
	 */
	protected function get_items() {
		$items = get_request_var( $this->get_item_type(), null );

		if ( ! $items ) {
			return false;
		}

		return is_array( $items ) ? $items : array( $items );
	}

	/**
	 * Get the current action
	 *
	 * @return bool|string
	 */
	protected function get_current_action() {
		if ( isset_not_empty( $_REQUEST, 'filter_action' ) ) {
			return false;
		}

		if ( isset_not_empty( $_REQUEST, 'action' ) && $_REQUEST['action'] != - 1 ) {
			return sanitize_text_field( get_request_var( 'action' ) );
		}

		if ( isset_not_empty( $_REQUEST, 'action2' ) && $_REQUEST['action2'] != - 1 ) {
			return sanitize_text_field( get_request_var( 'action2' ) );
		}

		return 'view';
	}

	/**
	 * Get the previous action
	 *
	 * @return mixed
	 */
	protected function get_previous_action() {
		$action = get_transient( 'gh_last_action' );

		delete_transient( 'gh_last_action' );

		return $action;
	}

	/**
	 * Get the screen title
	 */
	protected function get_title() {
		return $this->get_name();
	}

	/**
	 * @return mixed
	 */
	public function get_screen_id() {
		return $this->screen_id;
	}

	/**
	 * Verify that the current user can perform the action
	 *
	 * @return bool
	 */
	protected function verify_action() {
		if ( ! get_request_var( '_wpnonce' ) || ! current_user_can( $this->get_cap() ) ) {
			return false;
		}

		$nonce = get_request_var( '_wpnonce' );

		$checks = [
			wp_verify_nonce( $nonce ),
			wp_verify_nonce( $nonce, $this->get_current_action() ),
			wp_verify_nonce( $nonce, sprintf( 'bulk-%s', $this->get_item_type_plural() ) )
		];

		return in_array( true, $checks );
	}

	/**
	 * Die if no access
	 */
	protected function wp_die_no_access() {
		if ( wp_doing_ajax() ) {
			return wp_send_json_error( __( "Invalid permissions.", 'groundhogg' ) );
		}

		return wp_die( __( "Invalid permissions.", 'groundhogg' ), 'No Access!' );
	}

	/**
	 * Output a search form
	 *
	 * @param        $title
	 * @param string $name
	 */
	protected function search_form( $title, $name = 's' ) {

		if ( method_exists( $this, 'get_current_tab' ) ) {
			?>
            <div style="margin-top: 10px"></div><?php
		}

		?>
        <form method="get" class="search-form">
			<?php html()->hidden_GET_inputs( true ); ?>
            <input type="hidden" name="page" value="<?php esc_attr_e( get_request_var( 'page' ) ); ?>">
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php echo $title; ?>:</label>
                <input type="search" id="post-search-input" name="<?php echo $name ?>"
                       value="<?php esc_attr_e( get_request_var( $name ) ); ?>">
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( $title ); ?>">
            </p>
        </form>
		<?php
	}

	/**
	 * Process the given action
	 */
	public function process_action() {

		if ( ! $this->get_current_action() || ! $this->verify_action() ) {
			return;
		}

		$base_url = remove_query_arg( [ '_wpnonce', 'action', 'process_queue' ], wp_get_referer() );

		$func = sprintf( "process_%s", $this->get_current_action() );

		$exitCode = null;

		$action_or_filter = "groundhogg/admin/{$this->get_slug()}/process/{$this->get_current_action()}";

		if ( method_exists( $this, $func ) ) {
			$exitCode = call_user_func( [ $this, $func ] );
		} else if ( has_filter( $action_or_filter ) ) {
			$exitCode = apply_filters( $action_or_filter, $exitCode );
		}

		set_transient( 'groundhogg_last_action', $this->get_current_action(), 30 );

		if ( is_wp_error( $exitCode ) ) {
			$this->add_notice( $exitCode );

			return;
		}

		if ( is_string( $exitCode ) && esc_url_raw( $exitCode ) ) {
			wp_redirect( $exitCode );
			die();
		}

		// Return to self if true response.
		if ( $exitCode === true ) {
			return;
		}

		// IF NULL return to main table
		$items = $this->get_items();

		if ( ! empty( $items ) ) {
			$base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_items() ) ), $base_url );
		}

		wp_safe_redirect( $base_url );
		die();
	}

	/**
	 * Get an array of links => titles for page title actions
	 *
	 * @return array[]
	 */
	protected function get_title_actions() {
		return [
			[
				'link'   => $this->admin_url( [ 'action' => 'add' ] ),
				'action' => __( 'Add New', 'groundhogg' ),
				'target' => '_self',
			]
		];
	}

	/**
	 * Output the title actions
	 */
	protected function do_title_actions() {
		$actions = apply_filters( "groundhogg/admin/{$this->get_slug()}/{$this->get_current_action()}/title_actions", $this->get_title_actions() );

		foreach ( $actions as $action ):

			$action = wp_parse_args( $action, [
				'link'    => admin_url(),
				'action'  => __( 'Add New', 'groundhogg' ),
				'target'  => '_self',
				'id'      => '',
				'classes' => '',
			] );

			echo html()->e( 'a', [
				'class'  => 'page-title-action aria-button-if-js ' . $action['classes'],
				'target' => $action['target'],
				'href'   => $action['link'],
				'id'     => $action['id'],
			], $action['action'] );

		endforeach;

	}

	/**
	 * Output the basic view.
	 *
	 * @return mixed
	 */
	abstract public function view();


	/**
	 * Display the title and dependent action include the appropriate page content
	 */
	public function page() {

		do_action( "groundhogg/admin/{$this->get_slug()}/before" );

		?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo $this->get_title(); ?></h1>
			<?php $this->do_title_actions(); ?>
            <div id="notices">
				<?php Plugin::instance()->notices->notices(); ?>
            </div>
            <hr class="wp-header-end">
			<?php

			if ( method_exists( $this, $this->get_current_action() ) ) {
				call_user_func( [ $this, $this->get_current_action() ] );
			} else if ( has_action( "groundhogg/admin/{$this->get_slug()}/display/{$this->get_current_action()}" ) ) {
				do_action( "groundhogg/admin/{$this->get_slug()}/display/{$this->get_current_action()}", $this );
			} else {
				call_user_func( [ $this, 'view' ] );
			}

			?>
        </div>
		<?php

		do_action( "groundhogg/admin/{$this->get_slug()}/after" );
	}

	/**
	 * Get the admin url with the given query string.
	 *
	 * @param string|array $query
	 *
	 * @return string
	 */
	public function admin_url( $query = [] ) {
		$base = add_query_arg( [ 'page' => $this->get_slug() ], admin_url( 'admin.php' ) );

		if ( empty( $query ) ) {
			return $base;
		}

		$url = $base;

		if ( is_array( $query ) ) {
			$url = add_query_arg( $query, $base );
		}

		if ( is_string( $query ) ) {
			$url = $base . '&' . $query;
		}

		return $url;
	}

	/**
	 * Adds an admin notice
	 *
	 * @param string $code
	 * @param string $message
	 * @param string $type
	 * @param bool $cap
	 */
	protected function add_notice( $code = '', $message = '', $type = 'success', $cap = false ) {
		if ( ! $cap ) {
			$cap = $this->get_cap();
		}

		Plugin::instance()->notices->add( $code, $message, $type, $cap );
	}

	/**
	 * Removes an admin notice
	 *
	 * @param string $code
	 */
	protected function remove_notice( $code = '' ) {
		Plugin::instance()->notices->remove( $code );
	}

	/**
	 * Output any notices...
	 */
	protected function notices() {
		Plugin::instance()->notices->notices();
	}

	/**
	 * Send any response data to the given ajax request
	 *
	 * @param array $data
	 *
	 * @return bool|void
	 */
	protected function send_ajax_response( $data = [] ) {
		if ( ! wp_doing_ajax() ) {
			return;
		}

		if ( ! is_array( $data ) ) {
			$data = (array) $data;
		}

		ob_start();

		Plugin::instance()->notices->notices();

		$notices = ob_get_clean();

		$response = [
			'notices' => $notices,
			'data'    => $data
		];

		wp_send_json_success( $response );
	}

	/**
	 * Default process view
	 */
	public function process_view() {
		$paged = get_request_var( 'paged', 1 );

		return add_query_arg( 'paged', $paged, wp_get_referer() );
	}
}