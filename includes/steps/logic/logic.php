<?php

namespace Groundhogg\Steps\Logic;

use Groundhogg\Contact;
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

	final public function get_sub_group() {
		return self::LOGIC;
	}

	/**
	 * Get the action for the
	 *
	 * @param Contact $contact
	 *
	 * @return mixed
	 */
	abstract public function get_logic_action( Contact $contact );

	public function sortable_item( $step ) {

		if ( ! is_a( $this, Branch_Logic::class ) ) {

			?><div class="sortable-item logic"><?php

			if ( $step->get_funnel()->is_editing() ) {
				$this->add_step_button();
			}

			?>
            <div class="flow-line"></div><?php
		}

		parent::sortable_item( $step );

		if ( ! is_a( $this, Branch_Logic::class ) ) {
			?>
            <div class="flow-line"></div>
            </div><?php
		}

	}

}
