<?php

namespace Groundhogg\Admin;

/**
 * Class Page_Extension
 * Easy class to add additional functionality to core pages from extensions
 *
 * @package Groundhogg\Admin
 */
abstract class Admin_Page_Extension {

	/**
	 * Page_Extension constructor.
	 */
	public function __construct() {
		add_action( "groundhogg/admin/{$this->get_slug()}/display/{$this->get_action()}", [ $this, 'render' ], 99 );
		add_filter( "groundhogg/admin/{$this->get_slug()}/process/{$this->get_action()}", [ $this, 'process' ], 99, 1 );
	}

	/**
	 * Get the parent page this extension should be associated with
	 *
	 * @return string
	 */
	abstract protected function get_slug();

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
