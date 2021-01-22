<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

use function Groundhogg\isset_not_empty;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */

abstract class Category_Graph extends Line_Graph
{
    /**
     * @return string
     */
    public function get_mode(){
        return 'categories';
    }

}