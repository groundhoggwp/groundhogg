<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

use function Groundhogg\isset_not_empty;
use function Groundhogg\percentage;
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
        $html = Plugin::$instance->utils->html;

        $total_sent = array_sum( wp_list_pluck( $this->dataset[0][ 'data' ], 1 ) );
        $total_opens = array_sum( wp_list_pluck( $this->dataset[1][ 'data' ], 1 ) );
        $total_clicks = array_sum( wp_list_pluck( $this->dataset[2][ 'data' ], 1 ) );

        $html->list_table(
            [ 'class' => 'email_activity' ],
            [
                __( 'Sent', 'groundhogg' ),
                __( 'Opens (O.R)', 'groundhogg' ),
                __( 'Clicks (C.T.R)', 'groundhogg' ),
            ],
            [
                [
                    $html->wrap( $total_sent, 'span', [ 'class' => 'number-total' ] ),
                    $html->wrap( $total_opens . ' (' . percentage( $total_sent, $total_opens) . '%)', 'span', [ 'class' => 'number-total' ] ),
                    $html->wrap( $total_clicks . ' (' . percentage( $total_opens, $total_clicks) . '%)', 'span', [ 'class' => 'number-total' ] ),
                ]
            ],
            false
        );
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