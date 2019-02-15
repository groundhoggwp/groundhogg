<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_Lead_Source_Widget extends WPGH_Reporting_Widget
{

    /**
     * WPGH_Report_V2 constructor.
     */
    public function __construct()
    {
        $this->wid = 'groundhogg_lead_source_widget';
        $this->name = _x( 'Lead Source Report', 'widget_name', 'groundhogg' );

        parent::__construct();
    }

    /**
     * Get table of lead sources
     */
    public function widget()
    {
        global $wpdb;

        $table = WPGH()->contacts->table_name;
        $start_date = date('Y-m-d H:i:s', $this->start_time);
        $end_date = date('Y-m-d H:i:s', $this->end_time);

        $contacts = $wpdb->get_results("SELECT ID FROM $table WHERE '$start_date' <= date_created AND date_created <= '$end_date'");

        $sources = array();

        foreach ( $contacts as $contact ){

            $lead_source = WPGH()->contact_meta->get_meta( $contact->ID, 'lead_source', true );

            if ( $lead_source ){

                if ( filter_var( $lead_source, FILTER_VALIDATE_URL ) ){

                    /* TO avoid long lists of specifics, limit to just the root domin. */
                    $lead_source = parse_url( $lead_source, PHP_URL_HOST );

                }

                if ( isset($sources[$lead_source]) ){
                    $sources[$lead_source]++;
                } else {
                    $sources[$lead_source] = 1;
                }

            }

        }

        if ( empty( $sources ) ){
            printf( '<p class="description">%s</p>', _x( 'No new lead sources to report.', 'notice', 'groundhogg' ) );
            return;
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        ?>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _ex( 'Lead Source', 'column_title', 'groundhogg' ); ?></th>
            <th><?php _ex( 'Contacts', 'column_title','groundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php

        foreach ( $sources as $source => $num_contacts ):

            ?>
            <tr>
                <td class=""><?php printf( '<a href="%s">%s</a>', admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=%s&meta_value=%s&meta_compare=RLIKE&date_after=%s&date_before=%s', 'lead_source', urlencode( $source ), date( 'Y-m-d', $this->start_time), date( 'Y-m-d', $this->end_time ) ) ), $source ); ?></td>
                <td class="summary-total"><?php printf( '%d', $num_contacts ); ?></td>
            </tr>
        <?php

        endforeach;

        ?>
        </tbody>
        </table>
        <?php

        $this->export_button();
    }

    protected function get_export_data()
    {
        global $wpdb;

        $table = WPGH()->contacts->table_name;
        $start_date = date('Y-m-d H:i:s', $this->start_time);
        $end_date = date('Y-m-d H:i:s', $this->end_time);

        $contacts = $wpdb->get_results("SELECT ID FROM $table WHERE '$start_date' <= date_created AND date_created <= '$end_date'");

        $sources = array();

        foreach ( $contacts as $contact ){

            $lead_source = WPGH()->contact_meta->get_meta( $contact->ID, 'lead_source', true );

            if ( $lead_source ){

                if ( filter_var( $lead_source, FILTER_VALIDATE_URL ) ){

                    /* TO avoid long lists of specifics, limit to just the root domin. */
                    $lead_source = parse_url( $lead_source, PHP_URL_HOST );

                }

                if ( isset($sources[$lead_source]) ){
                    $sources[$lead_source]++;
                } else {
                    $sources[$lead_source] = 1;
                }

            }

        }

        if ( empty( $sources ) ){
            return _x( 'No new lead sources to report.', 'notice', 'groundhogg' );
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        $export_info = array();

        foreach ( $sources as $source => $num_contacts ):

           $export_info[] = array(
               _x( 'Lead Source URL','column_title', 'groundhogg' ) => $source,
               _x( 'Number of Contacts','column_title', 'groundhogg' ) => $num_contacts,
           );

        endforeach;

        return $export_info;


    }


}