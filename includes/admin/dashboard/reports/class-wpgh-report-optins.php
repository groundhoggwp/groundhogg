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

        $array = array();
        $array['cols'][] = array('type' => 'string' , 'label' => 'Date');
        $array['cols'][] = array('type' => 'number' , 'label' => 'Number Of Contacts');


        $start_range = $this->start_time;
        $end_range = $this->start_time + ( DAY_IN_SECONDS );


        if ( $this->range === 'last_24' ){
            $start_date = $start_range;
            $end_range = $start_range + 3600;

            for ( $i = 0; $i < 24; $i++ ){


                $start_date = date( 'Y-m-d H:i:s', $start_range );
                $end_date = date( 'Y-m-d H:i:s', $end_range );

                $table = WPGH()->contacts->table_name;

                $num_contacts = $wpdb->get_var( "SELECT COUNT(email) FROM $table WHERE '$start_date' <= date_created AND date_created <= '$end_date'" );

                $col = date( 'h:i a', $start_range );

                //HERE you have the difference
                $array['rows'][]['c'] = array(
                    array('v' => $col ),
                    array('v' => $num_contacts)
                );

                $start_range = $end_range;
                $end_range = $end_range + 3600;
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

            for ( $i = 0; $i < $days; $i++ ){


                $start_date = date( 'Y-m-d H:i:s', $start_range );
                $end_date = date( 'Y-m-d H:i:s', $end_range );

                $table = WPGH()->contacts->table_name;

                $num_contacts = $wpdb->get_var( "SELECT COUNT(email) FROM $table WHERE '$start_date' <= date_created AND date_created <= '$end_date'" );

                $col = date( 'Y-m-d', $start_range );

                //HERE you have the difference
                $array['rows'][]['c'] = array(
                    array('v' => $col),
                    array('v' => $num_contacts)
                );

                $start_range = $end_range;
                $end_range = $end_range + ( DAY_IN_SECONDS );

            }
        }


        return json_encode($array);
    }

}