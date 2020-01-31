<?php

namespace Groundhogg\Admin;

/**
 * Class Page_Extension
 * Easy class to add additional functionality to core pages from extensions
 *
 * @package Groundhogg\Admin
 */
abstract class Tabbed_Admin_Page_Extension
{

	/**
	 * Page_Extension constructor.
	 */
	public function __construct() {

		// Todo add other required actions...

		add_filter( "groundhogg/admin/{$this->get_slug()}/tabs", [ $this, 'register_tab' ], 99, 1 );
	}

	/**
	 * Register the new Tab
	 * 
	 * @param $tabs
	 *
	 * @return mixed
	 */
	public function register_tab( $tabs ) {
		
		$tabs[ $this->get_tab_slug() ] = [
			'slug' => $this->get_tab_slug(),
			'name' => $this->get_tab_name(),
		];
		
		return $tabs;
	}

	/**
	 * Get the parent page this extension should be associated with
	 *
	 * @return string
	 */
	abstract protected function get_slug();

	/**
	 * Get the tab slug
	 * 
	 * @return mixed
	 */
	abstract protected function get_tab_slug();

	/**
	 * Get the tab name
	 * 
	 * @return mixed
	 */
	abstract protected function get_tab_name();

	/**
	 * Get the action for this extension
	 *
	 * @return string
	 */
	abstract protected function get_action();

	/**
	 * Process the action
	 *
	 * @param $exitcode mixed
	 *
	 * @return mixed
	 */
	abstract public function process( $exitcode );

	/**
	 * Render any screen output
	 *
	 * @return mixed
	 */
	abstract public function render();


}
