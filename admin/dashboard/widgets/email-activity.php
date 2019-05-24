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

class Email_Activity extends Time_Graph
{

    public function get_id()
    {
        return 'email_activity';
    }

    public function get_name()
    {
        return __( 'Email Activity', 'groundhogg' );
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
            'emails_sent',
            'emails_opened',
            'emails_clicked'
        ];
    }

    /**
     * @param $datum
     * @return int
     */
    public function get_time_from_datum($datum)
    {
        if ( isset_not_empty( $datum, 'time' ) ){
            return absint( $datum->time );
        } else if ( isset_not_empty( $datum, 'timestamp' ) ){
            return absint( $datum->timestamp );
        }

        return false;
    }
}