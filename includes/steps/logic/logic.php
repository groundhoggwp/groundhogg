<?php

namespace Groundhogg\Steps\Logic;

use Groundhogg\Contact;
use Groundhogg\Step;
use Groundhogg\Steps\Funnel_Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-29
 * Time: 9:45 AM
 */
abstract class Logic extends Funnel_Step {

	const GROUP = 'logic';

	/**
	 *
	 * @return string
	 */
	final public function get_group() {
		return self::LOGIC;
	}

	public function get_sub_group() {
		return self::LOGIC;
	}

	/**
	 * Get the action for the
	 *
	 * @param Contact $contact
	 *
	 * @return false|Step
	 */
	abstract public function get_logic_action( Contact $contact );

}
