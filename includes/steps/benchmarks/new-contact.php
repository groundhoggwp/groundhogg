<?php

namespace Groundhogg\Steps\Benchmarks;

use Groundhogg\Contact;
use function Groundhogg\get_contactdata;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Account Created
 *
 * This will run proceeding actions whenever a WordPRess acount is created
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class New_Contact extends Benchmark {

	/**
	 * The step type
	 */
	const TYPE = 'new_contact';

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/benchmarks/account-created/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return _x( 'New Contact', 'step_name', 'groundhogg' );

	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return self::TYPE;
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Runs whenever a new contact is created.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/account-created.png';
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {

	}

	/**
	 * Save the step settings
	 *
	 * @param $step Step
	 */
	public function save( $step ) {

	}

	/**
	 * get the hook for which the benchmark will run
	 *
	 * @return int[]
	 */
	protected function get_complete_hooks() {
		return [ 'groundhogg/db/post_insert/contact' => 2 ];
	}

	/**
	 * @param $id int
	 * @param $data array
	 */
	public function setup( $id, $data ) {
		$this->add_data( 'contact', get_contactdata( $id ) );
	}

	/**
	 * Get the contact from the data set.
	 *
	 * @return Contact
	 */
	protected function get_the_contact() {
		return $this->get_data( 'contact' );
	}

	/**
	 * @return bool
	 */
	protected function can_complete_step() {
		return true;
	}
}
