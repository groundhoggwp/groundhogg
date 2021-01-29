<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Steps\Funnel_Step;
use function Groundhogg\get_array_var;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-29
 * Time: 9:45 AM
 */
abstract class Action extends Funnel_Step {

	const GROUP = 'action';

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/';
	}

	/**
	 *
	 * @return string
	 */
	final public function get_group() {
		return self::ACTION;
	}

	public function pre_validate( $step ) {
		parent::pre_validate( $step );

		$this->validate_delay();
	}

	/**
	 * Actions are the only steps with delays, so we'll validate the delay in the base action
	 * class.
	 */
	public function validate_delay(){

		$delay = $this->get_current_step()->get_delay_config();

		$type = get_array_var( $delay, 'type' );

		switch ( $type ) {

			case '':
				break;

		}

	}

}
