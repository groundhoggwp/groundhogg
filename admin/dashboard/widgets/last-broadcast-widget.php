<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

use function Groundhogg\key_to_words;
use function Groundhogg\words_to_key;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class Last_Broadcast_Widget extends Circle_Graph
{

    /**
     * Any additional information needed for the widget.
     *
     * @return void
     */
    protected function extra_widget_info()
    {
        // TODO: Implement extra_widget_info() method.
    }

    /**
     * Normalize a datum
     *
     * @param $item_key
     * @param $item_data
     * @return array
     */
    protected function normalize_datum($item_key, $item_data)
    {
        return [
            'label' => ucwords( key_to_words( $item_key ) ),
            'data' => $item_data,
            'url'  => '#', // TODO add URL to contacts page...
        ];
    }

    /**
     * Ge the report ID...
     *
     * @return string
     */
    protected function get_report_id()
    {
        return 'last_broadcast';
    }
}