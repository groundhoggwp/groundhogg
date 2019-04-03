<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_Lead_Source_Widget extends WPGH_Lead_Source_Report_Widget
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
        $contact_ids = $this->get_contact_ids_created_within_time_range();
        $ids = implode( ',', $contact_ids );

        $sources = array();

        global $wpdb;
        $table_name = WPGH()->contact_meta->table_name;

        $lead_sources = $this->get_lead_sources();

        foreach ( $lead_sources as $lead_source ){
            if ( ! empty( $lead_source ) ){
                $num_contacts = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(meta_id) FROM $table_name WHERE meta_key = %s AND meta_value = %s AND contact_id IN ( $ids )", 'lead_source', $lead_source ) );
                $sources[ $lead_source ] = $num_contacts;
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
                <?php if ( filter_var( $source, FILTER_VALIDATE_URL ) ): ?>
                <td class=""><?php printf( '<a href="%s">%s</a>', $source, wp_parse_url( $source, PHP_URL_HOST ) ); ?></td>
                <?php else: ?>
                <td class=""><?php printf( '%s', $source ); ?></td>
                <?php endif; ?>
                <td class="summary-total"><?php printf( '<a href="%s">%s</a>', admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=%s&meta_value=%s&meta_compare=RLIKE&date_after=%s&date_before=%s', 'lead_source', urlencode( $source ), date( 'Y-m-d', $this->start_time), date( 'Y-m-d', $this->end_time ) ) ), $num_contacts ); ?></td>
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
        $contact_ids = $this->get_contact_ids_created_within_time_range();
        $ids = implode( ',', $contact_ids );

        $sources = array();

        global $wpdb;
        $table_name = WPGH()->contact_meta->table_name;

        $lead_sources = wp_list_pluck( $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT meta_value FROM $table_name WHERE meta_key = %s AND contact_id IN ( $ids )", 'lead_source' ) ), 'meta_value' );

        foreach ( $lead_sources as $lead_source ){
            $num_contacts = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(meta_id) FROM $table_name WHERE meta_key = %s AND meta_value = %s AND contact_id IN ( $ids )", 'lead_source', $lead_source ) );
            if ( ! empty( $lead_source ) ){
                $sources[ $lead_source ] = $num_contacts;
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
               _x( 'Lead Source','column_title', 'groundhogg' ) => $source,
               _x( 'Number of Contacts','column_title', 'groundhogg' ) => $num_contacts,
           );

        endforeach;

        return $export_info;


    }


}