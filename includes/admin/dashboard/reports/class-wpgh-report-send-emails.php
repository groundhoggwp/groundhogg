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


       /* $array = array();
        $array['cols'][] = array('type' => 'string' , 'label' => __('Date'));
        $array['cols'][] = array('type' => 'number' , 'label' => __('Emails Sent'));
        $array['cols'][] = array('type' => 'number' , 'label' => __('Email Opens'));
        $array['cols'][] = array('type' => 'number' , 'label' => __('Email Link Clicks'));*/


        $start_range =  $this->start_time ;
        $end_range   =  $this->start_time + ( DAY_IN_SECONDS );


        if ( $this->range === 'last_24' ){

            $end_range = $start_range + HOUR_IN_SECONDS;

            for ( $i = 0; $i < 24; $i++ ){

                $events = WPGH()->events->table_name;
                $steps  = WPGH()->steps->table_name;

                $num_emails_sent = $wpdb->get_var( "SELECT COUNT(e.ID) FROM $events AS e LEFT JOIN $steps AS s ON e.step_id = s.ID WHERE '$start_range' <= e.time AND e.time < '$end_range' AND ( s.step_type = 'send_email' OR e.funnel_id = 1 ) " );

                $num_opens = WPGH()->activity->count( array( 'start' => $start_range, 'end' => $end_range, 'activity_type' => 'email_opened' ) );
                $num_clicks = WPGH()->activity->count( array( 'start' => $start_range, 'end' => $end_range, 'activity_type' => 'email_link_click' ) );

                $col = convert_to_local_time( $start_range ) * 1000;

                $dataset1[] = array( $col, $num_emails_sent );
                $dataset2[] = array( $col, $num_opens );
                $dataset3[] = array( $col, $num_clicks );


                $start_range = $end_range;
                $end_range = $end_range + HOUR_IN_SECONDS;
            }


        } else {

            switch ( $this->range ){

                case 'last_7';
                    $days = 7;
                    break;
                default:
                case 'last_30';
                    $days = 30;
                    break;
                case 'custom';
                    $days = ( $this->end_time - $this->start_time ) / DAY_IN_SECONDS;
                    break;
            }

            for ( $i = 0; $i <= $days; $i++ ){

                $events = WPGH()->events->table_name;
                $steps  = WPGH()->steps->table_name;

                $num_emails_sent = $wpdb->get_var( "SELECT COUNT(e.ID) FROM $events AS e LEFT JOIN $steps AS s ON e.step_id = s.ID WHERE '$start_range' <= e.time AND e.time < '$end_range' AND ( s.step_type = 'send_email' OR e.funnel_id = 1 )" );
                $num_opens = WPGH()->activity->count( array( 'start' => $start_range, 'end' => $end_range, 'activity_type' => 'email_opened' ) );
                $num_clicks = WPGH()->activity->count( array( 'start' => $start_range, 'end' => $end_range, 'activity_type' => 'email_link_click' ) );

//                $col = date( 'Y-m-d', convert_to_local_time( $start_range ) );
                $col = convert_to_local_time( $start_range ) * 1000;


                $dataset1[] = array( $col, $num_emails_sent );
                $dataset2[] = array( $col, $num_opens );
                $dataset3[] = array( $col, $num_clicks );

                $start_range = $end_range;
                $end_range = $end_range + ( DAY_IN_SECONDS );

            }
        }


        $ds =  array();
        $ds[] = array(
            'label' => 'email sent',
            'data'  => $dataset1
        ) ;
        $ds[] = array(
            'label' => 'email open',
            'data'  => $dataset2
        ) ;
        $ds[] = array(
            'label' => 'email click',
            'data'  => $dataset3
        ) ;


        return json_encode($ds);
    }

}