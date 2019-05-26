<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class Search_Engines extends Table_Widget
{

    /**
     * Ge the report ID...
     *
     * @return string
     */
    protected function get_report_id()
    {
        return 'contacts_by_search_engine';
    }

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

        ];
    }
}