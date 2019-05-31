<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Steps\Funnel_Step;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-29
 * Time: 9:45 AM
 */

abstract class Action extends Funnel_Step
{

	/**
	 * @return string
	 */
	public function get_help_article()
	{
		return 'https://docs.groundhogg.io/docs/builder/actions/';
	}

    /**
     *
     * @return string
     */
    final public function get_group()
    {
        return self::ACTION;
    }

}
