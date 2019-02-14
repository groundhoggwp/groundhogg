<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_Social_Media_Widget extends WPGH_Reporting_Widget
{

    private $social_networks = array();

    /**
     * WPGH_Report_V2 constructor.
     */
    public function __construct()
    {
        $this->wid = 'groundhogg_social_media_widget';
        $this->name = _x( 'Social Media Report', 'widget_name', 'groundhogg' );
        $this->setup_social_networks();

        parent::__construct();
    }

    /**
     * Setup the social_netwrks array from the yaml file in lib
     */
    public function setup_social_networks()
    {
        if ( ! class_exists( 'Spyc' ) ){
            include_once WPGH_PLUGIN_DIR . 'includes/lib/yaml/Spyc.php';
        }

        $this->social_networks = Spyc::YAMLLoad( WPGH_PLUGIN_DIR . 'includes/lib/potential-known-leadsources/Socials.yml' );
    }

    /**
     * Get table of lead sources
     */
    public function widget()
    {
        global $wpdb;

//        var_dump( $this->social_networks );

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
                    $lead_source = str_replace( 'www.', '', $lead_source );

                    foreach ( $this->social_networks as $network_name => $network_urls ){

                        if ( in_array( $lead_source, $network_urls ) )

                        if ( isset($sources[$network_name]) ){
                            $sources[$network_name]++;
                        } else {
                            $sources[$network_name] = 1;
                        }
                    }
                }
            }

        }

        if ( empty( $sources ) ){
            printf( '<p class="description">%s</p>', __( 'No new social media sources to report.', 'groundhogg' ) );
            return;
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        ?>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _e( 'Social Media Site', 'groundhogg' ); ?></th>
            <th><?php _e( 'Contacts', 'groundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php

        foreach ( $sources as $source => $num_contacts ):

            ?>
            <tr>
                <td class=""><?php printf( '<a href="%s">%s</a>', admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=%s&meta_value=%s&meta_compare=RLIKE&date_after=%s&date_before=%s', 'lead_source', urlencode( strtolower( $source ) ), date( 'Y-m-d', $this->start_time), date( 'Y-m-d', $this->end_time ) ) ), $source ); ?></td>
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

    /**
     * Return export info in friendly format
     *
     * @return array|string
     */
    protected function get_export_data()
    {
        global $wpdb;

//        var_dump( $this->social_networks );

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
                    $lead_source = str_replace( 'www.', '', $lead_source );

                    foreach ( $this->social_networks as $network_name => $network_urls ){

                        if ( in_array( $lead_source, $network_urls ) )

                            if ( isset($sources[$network_name]) ){
                                $sources[$network_name]++;
                            } else {
                                $sources[$network_name] = 1;
                            }
                    }
                }
            }

        }

        if ( empty( $sources ) ){
            return __( 'No new social media sources to report.', 'groundhogg' );
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        $export_info = array();

        foreach ( $sources as $source => $num_contacts ):

            $export_info[] = array(
                __( 'Social Media Source', 'groundhogg' ) => $source,
                __( 'Number of Contacts', 'groundhogg' ) => $num_contacts,
            );

        endforeach;

        return $export_info;
    }
}