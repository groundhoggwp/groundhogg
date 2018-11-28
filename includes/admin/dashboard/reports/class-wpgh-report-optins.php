<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class WPGH_Report_Optins extends WPGH_Report
{

    public function get_data()
    {

        global $wpdb;

        $dataset1 = array();

        for ( $i = 0; $i < $this->points; $i++ ){

            $start_date = date( 'Y-m-d H:i:s', $this->start_range );
            $end_date = date( 'Y-m-d H:i:s', $this->end_range );

            $table = WPGH()->contacts->table_name;

            $num_contacts = $wpdb->get_var( "SELECT COUNT(email) FROM $table WHERE '$start_date' <= date_created AND date_created <= '$end_date'" );

            $col = convert_to_local_time(  $this->start_range ) * 1000;

            $dataset1[] = array( $col, $num_contacts );

            $this->start_range = $this->end_range;
            $this->end_range = $this->end_range + $this->difference;
        }

        $ds =  array();
        $ds[] = array(
            'label' => __( 'Optins' ),
            'data'  => $dataset1
        ) ;

        return json_encode( $ds );
    }

}