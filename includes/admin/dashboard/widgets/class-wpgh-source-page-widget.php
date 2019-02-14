<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_Source_Page_Widget extends WPGH_Reporting_Widget
{

    /**
     * WPGH_Report_V2 constructor.
     */
    public function __construct()
    {
        $this->wid = 'groundhogg_source_page_widget';
        $this->name = _x( 'Source Page Report', 'widget_name', 'groundhogg' );

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

            $lead_source = WPGH()->contact_meta->get_meta( $contact->ID, 'source_page', true );

            if ( $lead_source ){

                if ( filter_var( $lead_source, FILTER_VALIDATE_URL ) ){
                    /* TO avoid long lists of specifics, limit to just the root domin. */
                    $lead_source = parse_url( $lead_source, PHP_URL_PATH );
                }

                $lead_source = preg_replace('/\?.*/', '', $lead_source);

                if ( isset($sources[$lead_source]) ){
                    $sources[$lead_source]++;
                } else {
                    $sources[$lead_source] = 1;
                }

            }

        }

        if ( empty( $sources ) ){
            printf( '<p class="description">%s</p>', __( 'No new pages to report.', 'groundhogg' ) );
            return;
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        ?>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _e( 'Source Page', 'groundhogg' ); ?></th>
            <th><?php _e( 'Contacts', 'groundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php

        foreach ( $sources as $source => $num_contacts ):

            ?>
            <tr>
                <td class=""><?php printf( '<a href="%s">%s</a>', admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=%s&meta_value=%s&meta_compare=RLIKE&date_after=%s&date_before=%s', 'source_page', urlencode( $source ), date( 'Y-m-d', $this->start_time), date( 'Y-m-d', $this->end_time ) ) ), $source ); ?></td>
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

            $lead_source = WPGH()->contact_meta->get_meta( $contact->ID, 'source_page', true );

            if ( $lead_source ){

                if ( filter_var( $lead_source, FILTER_VALIDATE_URL ) ){

                    /* TO avoid long lists of specifics, limit to just the root domin. */
                    $lead_source = parse_url( $lead_source, PHP_URL_PATH );

                }

                if ( isset($sources[$lead_source]) ){
                    $sources[$lead_source]++;
                } else {
                    $sources[$lead_source] = 1;
                }

            }

        }

        if ( empty( $sources ) ){
            return __( 'No new sources to report.', 'groundhogg' );
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        $export_info = array();

        foreach ( $sources as $source => $num_contacts ):

           $export_info[] = array(
               __( 'Source Page URL', 'groundhogg' ) => $source,
               __( 'Number of Contacts', 'groundhogg' ) => $num_contacts,
           );

        endforeach;

        return $export_info;


    }


}