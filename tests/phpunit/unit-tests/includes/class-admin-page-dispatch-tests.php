<?php

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Admin\Tabbed_Admin_Page;

/**
 * page() dispatches to a method named by the request, and process_action() is the only place that checks
 * the nonce. A handler named in the action (?action=process_delete) must not be reachable from page(), or
 * it runs with no nonce at all (CSRF).
 *
 * @see Admin_Page::is_view_method()
 * @see Admin_Page::page()
 * @see Tabbed_Admin_Page::page()
 */
class Admin_Page_Dispatch_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	public function tearDown(): void {
		unset( $_REQUEST['action'], $_REQUEST['tab'], $_GET['action'], $_GET['tab'] );
		parent::tearDown();
	}

	protected function request( $vars ) {
		foreach ( $vars as $key => $value ) {
			$_REQUEST[ $key ] = $value;
			$_GET[ $key ]     = $value;
		}
	}

	protected function render( $page ) {
		ob_start();
		$page->page();
		ob_end_clean();

		return $page->called;
	}

	protected function make_page() {
		return new class extends Admin_Page {

			public $called = [];

			protected function add_ajax_actions() {
			}

			protected function add_additional_actions() {
			}

			public function get_slug() {
				return 'gh_dispatch_test';
			}

			public function get_name() {
				return 'Dispatch Test';
			}

			public function get_cap() {
				return 'manage_options';
			}

			public function get_item_type() {
				return 'thing';
			}

			public function scripts() {
			}

			public function help() {
			}

			public function view() {
				$this->called[] = 'view';
			}

			public function add() {
				$this->called[] = 'add';
			}

			public function process_delete() {
				$this->called[] = 'process_delete';
			}

			public function delete_process() {
				$this->called[] = 'delete_process';
			}

			public function ajax_delete() {
				$this->called[] = 'ajax_delete';
			}
		};
	}

	protected function make_tabbed_page() {
		return new class extends Tabbed_Admin_Page {

			public $called = [];

			protected function add_ajax_actions() {
			}

			protected function add_additional_actions() {
			}

			public function get_slug() {
				return 'gh_dispatch_tabbed_test';
			}

			public function get_name() {
				return 'Dispatch Tabbed Test';
			}

			public function get_cap() {
				return 'manage_options';
			}

			public function get_item_type() {
				return 'thing';
			}

			protected function get_tabs() {
				return [
					[
						'name' => 'Events',
						'slug' => 'events',
						'cap'  => 'manage_options',
					],
					[
						'name' => 'Cron',
						'slug' => 'cron',
						'cap'  => 'manage_options',
					],
				];
			}

			public function scripts() {
			}

			public function help() {
			}

			// The events tab has no view of its own, so the bare action is a candidate, like Events_Page
			public function view() {
				$this->called[] = 'view';
			}

			public function events_add() {
				$this->called[] = 'events_add';
			}

			public function process_purge() {
				$this->called[] = 'process_purge';
			}

			public function cron_view() {
				$this->called[] = 'cron_view';
			}

			// Named process_{action}_{tab}, so reachable as {action}_{tab}, like Tools_Page's cron handlers
			public function process_cron_install_gh_cron() {
				$this->called[] = 'process_cron_install_gh_cron';
			}
		};
	}

	public function test_a_view_named_in_the_action_is_displayed() {
		$this->request( [ 'action' => 'add' ] );

		$this->assertSame( [ 'add' ], $this->render( $this->make_page() ) );
	}

	public function test_a_handler_named_in_the_action_is_not_run() {
		$this->request( [ 'action' => 'process_delete' ] );

		$this->assertSame( [ 'view' ], $this->render( $this->make_page() ) );
	}

	public function test_a_handler_named_in_another_case_is_not_run() {
		// PHP method names are case-insensitive
		$this->request( [ 'action' => 'Process_Delete' ] );

		$this->assertSame( [ 'view' ], $this->render( $this->make_page() ) );
	}

	public function test_suffixed_handlers_and_ajax_callbacks_are_not_run() {
		$this->request( [ 'action' => 'delete_process' ] );
		$this->assertSame( [ 'view' ], $this->render( $this->make_page() ) );

		$this->request( [ 'action' => 'ajax_delete' ] );
		$this->assertSame( [ 'view' ], $this->render( $this->make_page() ) );
	}

	public function test_the_page_itself_is_not_called_from_the_action() {
		// action=page used to make page() call itself until PHP gave up
		$this->request( [ 'action' => 'page' ] );

		$this->assertSame( [ 'view' ], $this->render( $this->make_page() ) );
	}

	public function test_a_tabbed_page_displays_its_tab_views() {
		$this->request( [ 'tab' => 'events', 'action' => 'add' ] );
		$this->assertSame( [ 'events_add' ], $this->render( $this->make_tabbed_page() ) );

		$this->request( [ 'tab' => 'events', 'action' => 'view' ] );
		$this->assertSame( [ 'view' ], $this->render( $this->make_tabbed_page() ) );

		$this->request( [ 'tab' => 'cron', 'action' => 'view' ] );
		$this->assertSame( [ 'cron_view' ], $this->render( $this->make_tabbed_page() ) );
	}

	public function test_a_tabbed_page_does_not_run_a_handler_named_in_the_action() {
		$this->request( [ 'tab' => 'events', 'action' => 'process_purge' ] );

		$this->assertSame( [ 'view' ], $this->render( $this->make_tabbed_page() ) );
	}

	public function test_a_tabbed_page_does_not_run_a_handler_made_from_the_action_and_tab() {
		// {action}_{tab} = process_cron_install_gh_cron
		$this->request( [ 'tab' => 'cron', 'action' => 'process_cron_install_gh' ] );

		$this->assertSame( [ 'cron_view' ], $this->render( $this->make_tabbed_page() ) );
	}
}
