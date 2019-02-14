<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class WPGH_Report_Form_Activity extends WPGH_Line_Graph_Report_V2
{

    public function __construct()
    {
        $this->wid = 'form_activity_report';
        $this->name = _x( 'Form Activity Report', 'widget_name', 'groundhogg' );

        parent::__construct();
    }

    public function get_data()
    {

        global $wpdb;

        $dataset1 = array();
        $dataset2 = array();
//        $dataset3 = array();


        for ( $i = 0; $i < $this->points; $i++ ){

            $events = WPGH()->events->table_name;
            $steps  = WPGH()->steps->table_name;

            $num_form_fills = $wpdb->get_var( "SELECT COUNT(e.ID) FROM $events AS e LEFT JOIN $steps AS s ON e.step_id = s.ID WHERE $this->start_range < e.time AND e.time <= $this->end_range AND s.step_type = 'form_fill'" );

            $nump_impressions = WPGH()->activity->count( array( 'start' => $this->start_range, 'end' => $this->end_range, 'activity_type' => 'form_impression' ) );
//            $num_clicks = WPGH()->activity->count( array( 'start' => $this->start_range, 'end' => $this->end_range, 'activity_type' => 'email_link_click' ) );

            $col = $this->start_range * 1000;

            $dataset1[] = array( $col, $num_form_fills );
            $dataset2[] = array( $col, $nump_impressions );
//            $dataset3[] = array( $col, $num_clicks );


            $this->start_range = $this->end_range;
            $this->end_range = $this->end_range + $this->difference;
        }

        $ds =  array();
        $ds[] = array(
            'label' => __( 'Form Fills', 'groundhogg' ),
            'data'  => $dataset1
        ) ;
        $ds[] = array(
            'label' => __( 'Form Impressions', 'groundhogg' ),
            'data'  => $dataset2
        ) ;


        return json_encode($ds);
    }

    /**
     * Show extra info
     *
     * @return string
     */
    protected function extra_widget_info()
    {
        global $wpdb;

        $events = WPGH()->events->table_name;
        $steps  = WPGH()->steps->table_name;

        $num_form_fills = $wpdb->get_var( "SELECT COUNT(e.ID) FROM $events AS e LEFT JOIN $steps AS s ON e.step_id = s.ID WHERE $this->start_time < e.time AND e.time <= $this->end_time AND s.step_type = 'form_fill'" );
        $num_impressions = WPGH()->activity->count( array( 'start' => $this->start_time, 'end' => $this->end_time, 'activity_type' => 'form_impression' ) );

        ?>
        <table class="chart-summary">
            <tbody>
            <tr>
                <td><?php printf('%s: <span class="summary-total">%d</span>', __('Total Impressions', 'groundhogg'), $num_impressions); ?></td>
                <td><?php printf('%s: <span class="summary-total">%d</span>', __('Total Submissions', 'groundhogg'), $num_form_fills); ?></td>
                <td><?php printf('%s: <span class="summary-total">%d%%</span>', __('Average Conversion Rate', 'groundhogg'), ceil( ( $num_form_fills / max( $num_impressions , 1 ) ) * 100 ) ); ?></td>
            </tr>
            </tbody>
        </table>
        <?php

        $this->export_button();

        return '';

    }

    /**
     * Return export info in friendly format
     *
     * @return array
     */
    protected function get_export_data()
    {

        global $wpdb;

        $export_info = array();

        for ( $i = 0; $i < $this->points; $i++ ){

            $events = WPGH()->events->table_name;
            $steps  = WPGH()->steps->table_name;

            $num_form_fills = $wpdb->get_var( "SELECT COUNT(e.ID) FROM $events AS e LEFT JOIN $steps AS s ON e.step_id = s.ID WHERE $this->start_range < e.time AND e.time <= $this->end_range AND s.step_type = 'form_fill'" );
            $nump_impressions = WPGH()->activity->count( array( 'start' => $this->start_range, 'end' => $this->end_range, 'activity_type' => 'form_impression' ) );

            $from = convert_to_local_time( $this->start_range );
            $to = convert_to_local_time( $this->end_range );

            $export_info[] = array(
                __( 'From', 'groundhogg' ) => date( 'F jS', $from ),
                __( 'To', 'groundhogg' ) => date( 'F jS', $to ),
                __( 'Form Impressions', 'groundhogg' ) => $num_form_fills,
                __( 'Form Submissions', 'groundhogg' ) => $nump_impressions,
                __( 'Average Conversion Rate', 'groundhogg' ) => ceil( ( $num_form_fills / max( $nump_impressions, 1 ) ) * 100 ) . '%',
            );

            $this->start_range = $this->end_range;
            $this->end_range = $this->end_range + $this->difference;

        }

        return $export_info;

    }

}