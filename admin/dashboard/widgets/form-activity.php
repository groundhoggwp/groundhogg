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

class Form_Activity extends Time_Graph
{

    public function get_id()
    {
        return 'form_activity';
    }

    public function get_name()
    {
        return __( 'Form Activity', 'groundhogg' );
    }

    /**
     * Any additional information needed for the widget.
     *
     * @return void
     */
    protected function extra_widget_info()
    {

        $html = Plugin::$instance->utils->html;

        $total_impressions = array_sum( wp_list_pluck( $this->dataset[0][ 'data' ], 1 ) );
        $total_submissions = array_sum( wp_list_pluck( $this->dataset[1][ 'data' ], 1 ) );

        $html->striped_table(
            [ 'class' => 'form_activity' ],
            [
                __( 'Impressions', 'groundhogg' ),
                __( 'Submissions', 'groundhogg' ),
                __( 'Conversion Rate (%)', 'groundhogg' ),
            ],
            [
                [
                    $html->wrap( $total_impressions, 'span', [ 'class' => 'number-total' ] ),
                    $html->wrap( $total_submissions, 'span', [ 'class' => 'number-total' ] ),
                    $html->wrap( percentage( $total_impressions, $total_submissions ) . '%', 'span', [ 'class' => 'number-total' ] ),
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
            'form_impressions',
            'form_submissions',
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