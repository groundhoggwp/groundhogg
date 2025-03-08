<?php

namespace Groundhogg\Steps\Actions;

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

	public function sortable_item( $step ) {

		?><div class="sortable-item action"><?php

		if ( $step->get_funnel()->is_editing() ) {
			$this->add_step_button();
		}

		?>
        <div class="flow-line"></div><?php

		parent::sortable_item( $step );

		?>
        <div class="flow-line"></div><?php

		?></div><?php
	}

}
