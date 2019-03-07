<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class WPGH_Geographic_Country_Report extends WPGH_Circle_Graph_Report
{
    public function __construct()
    {
        $this->wid = 'geographic_country_report';
        $this->name = _x( 'Geographic Report: Country', 'widget_name', 'groundhogg' );

        parent::__construct();
    }

    public function get_data()
    {

        global $wpdb;

        $dataset  =  array();

        if ( isset( $_REQUEST[ 'bulk_geo_locate' ] ) ){
            $this->bulk_geo_locate();
        }

        $table_name = WPGH()->contact_meta->table_name;
        $results = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM $table_name WHERE meta_key = %s", 'country' ) );

        foreach ( $results as $result ){

            $result->meta_value = substr( strtoupper( $result->meta_value ), 0, 2 );

            if ( key_exists( $result->meta_value, $dataset ) ){
                $dataset[ $result->meta_value ][ 'data' ]++;
            } else {

                $label = 'unknown';
                if ( $result->meta_value ){
                    $label = wpgh_get_countries_list( $result->meta_value );
                }

                $dataset[ $result->meta_value ] = [
                    'label' => $label,
                    'data' => 1,
                    'url'  => admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=country&meta_value=%s', $result->meta_value ) )
                ];

            }

        }

        if ( empty( $dataset ) ){
            $dataset[] = [
                'label' => 'No Data',
                'data' => 1,
                'url'  => '#'
            ];
        }

        $dataset = array_values( $dataset );
        usort( $dataset , array( $this, 'sort' ) );

        /* Pair down the results to largest 10 */
        if ( count( $dataset ) > 10 ){

            $other_dataset = [
                'label' => __( 'Other' ),
                'data' => 0,
                'url'  => '#'
            ];

            $other_countries = array_slice( $dataset, 10 );
            $dataset = array_slice( $dataset, 0, 10);

            foreach ( $other_countries as $c_data ){
                $other_dataset[ 'data' ] += $c_data[ 'data' ];
            }

            $dataset[] = $other_dataset;

        }

        usort( $dataset , array( $this, 'sort' ) );

        return array_values( $dataset );
    }

    public function sort( $a, $b )
    {
        return $b[ 'data' ] - $a[ 'data' ];
    }

    /**
     * Bulk geo locate contacts
     */
    private function bulk_geo_locate()
    {
        $contacts = WPGH()->contacts->get_contacts();
        foreach ( $contacts as $contact ){
            $contact = wpgh_get_contact( $contact->ID );
            if ( ! $contact->country ){
                $contact->extrapolate_location();
            }
        }
    }

    /**
     * Show extra info
     *
     * @return string
     */
    protected function extra_widget_info()
    {

//        ?>
<!--        <form method="get" action="" style="margin-top: 10px;">-->
<!--            --><?php
//
//            $this->form_reporting_inputs();
//
//            submit_button( __( 'Bulk Geo-locate Contacts' ), 'secondary', 'bulk_geo_locate', false );
//
//            ?>
<!--        </form>-->
<!--        --><?php

        $data = $this->get_data();

        ?>
        <hr>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _ex( 'Country', 'column_title','groundhogg' ); ?></th>
            <th><?php _ex( 'Contacts', 'column_title', 'groundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php

        foreach ( $data as $dataset ):
            ?>
        <tr>
            <td><a href="<?php echo $dataset[ 'url' ]; ?>"><?php echo $dataset[ 'label' ] ?></a></td>
            <td class="summary-total"><?php echo $dataset[ 'data' ] ?></td>
        </tr>
        <?php
        endforeach;

        ?></tbody>
        </table><?php

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