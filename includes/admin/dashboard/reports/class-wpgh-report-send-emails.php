<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class WPGH_Report_Send_Emails extends WPGH_Report
{

    public function get_data()
    {

        global $wpdb;

        $dataset1 = array();
        $dataset2 = array();
        $dataset3 = array();


        for ( $i = 0; $i < $this->points; $i++ ){

            $events = WPGH()->events->table_name;
            $steps  = WPGH()->steps->table_name;

            $num_emails_sent = $wpdb->get_var( "SELECT COUNT(e.ID) FROM $events AS e LEFT JOIN $steps AS s ON e.step_id = s.ID WHERE '$this->start_range' <= e.time AND e.time < '$this->end_range' AND ( s.step_type = 'send_email' OR e.funnel_id = 1 ) " );

            $num_opens = WPGH()->activity->count( array( 'start' => $this->start_range, 'end' => $this->end_range, 'activity_type' => 'email_opened' ) );
            $num_clicks = WPGH()->activity->count( array( 'start' => $this->start_range, 'end' => $this->end_range, 'activity_type' => 'email_link_click' ) );

            $col = convert_to_local_time( $this->start_range ) * 1000;

            $dataset1[] = array( $col, $num_emails_sent );
            $dataset2[] = array( $col, $num_opens );
            $dataset3[] = array( $col, $num_clicks );


            $this->start_range = $this->end_range;
            $this->end_range = $this->end_range + $this->difference;
        }

        $ds =  array();
        $ds[] = array(
            'label' => __( 'Emails Sent' ),
            'data'  => $dataset1
        ) ;
        $ds[] = array(
            'label' => __( 'Emails Opened' ),
            'data'  => $dataset2
        ) ;
        $ds[] = array(
            'label' => __( 'Emails Clicked' ),
            'data'  => $dataset3
        ) ;


        return json_encode($ds);
    }

}