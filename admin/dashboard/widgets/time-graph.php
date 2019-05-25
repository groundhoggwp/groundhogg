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

abstract class Time_Graph extends Line_Graph
{
    /**
     * @return string
     */
    public function get_mode(){
        return 'time';
    }

    /**
     * @param $datum
     * @return int
     */
    abstract public function get_time_from_datum( $datum );

    /**
     * Get the time slots for the given time range...
     *
     * @return array
     */
    public function get_date_points()
    {
        $points = Plugin::$instance->reporting->get_points();
        $start = Plugin::$instance->reporting->get_start_time();
        $diff = Plugin::$instance->reporting->get_difference();

        $date_points = [];

        for ( $i = 0;$i<$points;$i++){
            $start = Plugin::$instance->utils->date_time->round_to( $start, $diff );
            $date_points[ $start ] = [ $start * 1000, 0, date( 'Y-m-d H:i:s', $start ) ];
            $start+=$diff;
        }

        return $date_points;
    }

    /**
     * Group the given data into their respective time slots...
     *
     * @param $data
     * @return array
     */
    public function group_by_time( $data ){

        $times = $this->get_date_points();

        foreach ( $data as $datum ){
            $date_point = Plugin::$instance->utils->date_time->round_to(
                    $this->get_time_from_datum( $datum ),
                    Plugin::$instance->reporting->get_difference()
            );

            if ( isset_not_empty( $times, $date_point ) ){
                $times[ $date_point ][ 1 ]++;
            }
        }

        return array_values( $times );
    }

    /**
     * @return array
     */
    public function get_data()
    {
        $data = [];

        foreach ( $this->get_report_ids() as $report_id ){

            $report = Plugin::$instance->reporting->get_report( $report_id );

            if ( $report ){
                $data[] = [
                    'label' => $report->get_name(),
                    'data'  => $this->group_by_time( $report->get_data() )
                ];
            }

        }

        $this->dataset = $data;

        return $data;
    }
}