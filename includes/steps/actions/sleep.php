<?php

namespace Groundhogg\Steps\Actions;


use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create User
 *
 * Creates a WordPress user account for the contact, or assigns one to the contact if one exists.
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
class Sleep extends Action {

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/create-user/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'Sleep', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'sleep';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Sleep for 1 second', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/create-user.png';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {
		echo Plugin::$instance->utils->html->description( 'Sleep for a second.' );
	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
		return true;
	}

	/**
	 * Process the apply tag step...
	 *
	 * @param $contact Contact
	 * @param $event Event
	 *
	 * @return true
	 */
	public function run( $contact, $event ) {
		sleep( 1 );

		return true;
	}
}