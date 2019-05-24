<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */

class New_Contacts extends Time_Graph
{

    public function get_id()
    {
        return 'new_contacts';
    }

    public function get_name()
    {
        return __( 'New Contacts', 'groundhogg' );
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
     * Return several reports used rather than just 1.
     *
     * @return string[]
     */
    protected function get_report_ids()
    {
        return [
            'new_contacts'
        ];
    }

    /**
     * @param $datum
     * @return int
     */
    public function get_time_from_datum($datum)
    {
        return strtotime( $datum->date_created );
    }
}