<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class WPGH_Report_Optins extends WPGH_Line_Graph_Report_V2
{
    public function __construct()
    {
        $this->wid = 'new_contacts_report';
        $this->name = _x( 'New Contacts Report', 'widget_name', 'groundhogg' );

        parent::__construct();
    }

    public function get_data()
    {

        global $wpdb;

        $dataset1 = array();

        for ( $i = 0; $i < $this->points; $i++ ){

            $start_date = date( 'Y-m-d H:i:s', $this->start_range );
            $end_date = date( 'Y-m-d H:i:s', $this->end_range );

            $table = WPGH()->contacts->table_name;

            $num_contacts = $wpdb->get_var( "SELECT COUNT(email) FROM $table WHERE '$start_date' <= date_created AND date_created <= '$end_date'" );

            $col = $this->start_range * 1000;

            $dataset1[] = array( $col, $num_contacts );

            $this->start_range = $this->end_range;
            $this->end_range = $this->end_range + $this->difference;
        }

        $ds =  array();
        $ds[] = array(
            'label' => _x( 'Optins', 'stats', 'groundhogg' ),
            'data'  => $dataset1
        ) ;

        return json_encode( $ds );
    }

    /**
     * Show extr info
     *
     * @return string
     */
    protected function extra_widget_info()
    {
        global $wpdb;

        $table = WPGH()->contacts->table_name;
        $start_date = date('Y-m-d H:i:s', $this->start_time);
        $end_date = date('Y-m-d H:i:s', $this->end_time);

        $num_contacts = $wpdb->get_var("SELECT COUNT(email) FROM $table WHERE '$start_date' <= date_created AND date_created <= '$end_date'");

        $start_date = date('Y-m-d H:i:s', $this->start_time - ( $this->end_time - $this->start_time ) );
        $end_date = date('Y-m-d H:i:s', $this->end_time - ( $this->end_time - $this->start_time ) );
        $previous_period = $wpdb->get_var("SELECT COUNT(email) FROM $table WHERE '$start_date' <= date_created AND date_created <= '$end_date'");

        ?>
        <table class="chart-summary">
            <tbody>
            <tr>
                <td><?php printf('%s: <span class="summary-total">%d</span>', _x('Total Contacts', 'stats', 'groundhogg'), $num_contacts); ?></td>
                <td><?php printf('%s: <span class="summary-total">%d</span>', _x('Previous Period', 'stats', 'groundhogg'), $previous_period); ?></td>
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

        $dataset1 = array();

        for ( $i = 0; $i < $this->points; $i++ ){

            $start_date = date( 'Y-m-d H:i:s', $this->start_range );
            $end_date = date( 'Y-m-d H:i:s', $this->end_range );

            $table = WPGH()->contacts->table_name;

            $num_contacts = $wpdb->get_var( "SELECT COUNT(email) FROM $table WHERE '$start_date' <= date_created AND date_created <= '$end_date'" );

            $from = wpgh_convert_to_local_time( $this->start_range );
            $to = wpgh_convert_to_local_time( $this->end_range );

            $dataset1[] = array(
                _x( 'From', 'stats', 'groundhogg' )     => date( 'F jS', $from ),
                _x( 'To', 'stats', 'groundhogg' )       => date( 'F jS', $to ),
                _x( 'Contacts', 'stats', 'groundhogg' ) => $num_contacts
            );

            $this->start_range = $this->end_range;
            $this->end_range = $this->end_range + $this->difference;
        }

        return $dataset1;

    }
}