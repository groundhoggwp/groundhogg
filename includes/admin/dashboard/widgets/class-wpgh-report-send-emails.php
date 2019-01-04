<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class WPGH_Report_Send_Emails extends WPGH_Line_Graph_Report_V2
{
    public function __construct()
    {
        $this->wid = 'sent_activity_report';
        $this->name = __( 'Email Activity Report', 'groundhogg' );

        parent::__construct();
    }

    public function get_data()
    {

        global $wpdb;

        $dataset1 = array();
        $dataset2 = array();
        $dataset3 = array();


        for ( $i = 0; $i < $this->points; $i++ ){

            $events = WPGH()->events->table_name;
            $steps  = WPGH()->steps->table_name;

            $num_emails_sent = $wpdb->get_var( "SELECT COUNT(e.ID) FROM $events AS e LEFT JOIN $steps AS s ON e.step_id = s.ID WHERE $this->start_range < e.time AND e.time <= $this->end_range AND ( s.step_type = 'send_email' OR e.funnel_id = 1 ) " );

            $num_opens = WPGH()->activity->count( array( 'start' => $this->start_range, 'end' => $this->end_range, 'activity_type' => 'email_opened' ) );
            $num_clicks = WPGH()->activity->count( array( 'start' => $this->start_range, 'end' => $this->end_range, 'activity_type' => 'email_link_click' ) );

            $col = $this->start_range * 1000;

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

    /**
     * Show extr info
     *
     * @return string
     */
    protected function extra_widget_info()
    {
        global $wpdb;
        $events = WPGH()->events->table_name;
        $steps  = WPGH()->steps->table_name;

        /* ALL SENDS */
        $num_emails_sent = $wpdb->get_var( "SELECT COUNT(e.ID) FROM $events AS e LEFT JOIN $steps AS s ON e.step_id = s.ID WHERE $this->start_time < e.time AND e.time <= $this->end_time AND ( s.step_type = 'send_email' OR e.funnel_id = 1 ) " );
        $num_opens = WPGH()->activity->count( array( 'start' => $this->start_time, 'end' => $this->end_time, 'activity_type' => 'email_opened' ) );
        $num_clicks = WPGH()->activity->count( array( 'start' => $this->start_time, 'end' => $this->end_time, 'activity_type' => 'email_link_click' ) );

        /* ALL OPENS */
        /* ALL CLICKS */

        ?>
        <table class="chart-summary">
            <tbody>
            <tr>
                <td><?php printf( '%s: <span class="summary-total">%d</span>', __( 'Total Sent', 'groundhogg' ), $num_emails_sent ); ?></td>
                <td><?php printf( '%s: <span class="summary-total">%d</span>', __( 'Total Opened', 'groundhogg' ), $num_opens ); ?></td>
                <td style="text-align: right;"><?php printf( '%s: <span class="summary-total">%d</span>', __( 'Total Clicked', 'groundhogg' ), $num_clicks ); ?></td>
            </tr>
            <tr>
                <td><?php printf( '%s: <span class="summary-total">%d%%</span>', __( 'Average Open Rate', 'groundhogg' ), ceil( ( $num_opens / max( $num_emails_sent, 1 ) ) * 100 ) ); ?></td>
                <td colspan="2" style="text-align: right;"><?php printf( '%s: <span class="summary-total">%d%%</span>', __( 'Average Click Through Rate', 'groundhogg' ), ceil( ( $num_clicks / max( $num_opens, 1 ) ) * 100 ) ); ?></td>
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

            $num_emails_sent = $wpdb->get_var( "SELECT COUNT(e.ID) FROM $events AS e LEFT JOIN $steps AS s ON e.step_id = s.ID WHERE $this->start_range < e.time AND e.time <= $this->end_range AND ( s.step_type = 'send_email' OR e.funnel_id = 1 ) " );

            $num_opens = WPGH()->activity->count( array( 'start' => $this->start_range, 'end' => $this->end_range, 'activity_type' => 'email_opened' ) );
            $num_clicks = WPGH()->activity->count( array( 'start' => $this->start_range, 'end' => $this->end_range, 'activity_type' => 'email_link_click' ) );

            $from = convert_to_local_time( $this->start_range );
            $to = convert_to_local_time( $this->end_range );

            $export_info[] = array(
                __( 'From' ) => date( 'F jS', $from ),
                __( 'To' ) => date( 'F jS', $to ),
                __( 'Emails Sent', 'Groundhogg' ) => $num_emails_sent,
                __( 'Emails Opened', 'Groundhogg' ) => $num_opens,
                __( 'Emails Clicked', 'Groundhogg' ) => $num_clicks,
                __( 'Average Open Rate', 'Groundhogg' ) => ceil( ( $num_opens / max( $num_emails_sent, 1 ) ) * 100 ) . '%',
                __( 'Average Click Through Rate', 'Groundhogg' ) => ceil( ( $num_clicks / max( $num_opens, 1 ) ) * 100 ) . '%',
            );

            $this->start_range = $this->end_range;
            $this->end_range = $this->end_range + $this->difference;

        }

        return $export_info;

    }
}