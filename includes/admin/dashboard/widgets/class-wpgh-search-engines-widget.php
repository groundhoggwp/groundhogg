<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_Search_Engines_Widget extends WPGH_Reporting_Widget
{

    private $search_engines = array();

    /**
     * WPGH_Report_V2 constructor.
     */
    public function __construct()
    {
        $this->wid = 'groundhogg_search_engines_widget';
        $this->name = __( 'Search Engine Report', 'groundhogg' );
        $this->setup_social_networks();

        parent::__construct();
    }

    /**
     * Setup the search_engines array from the yaml file in lib
     */
    public function setup_social_networks()
    {
        if ( ! class_exists( 'Spyc' ) ){
            include_once WPGH_PLUGIN_DIR . 'includes/lib/yaml/Spyc.php';
        }

        $this->search_engines = Spyc::YAMLLoad( WPGH_PLUGIN_DIR . 'includes/lib/potential-known-leadsources/SearchEngines.yml' );
    }

    /**
     * Special search function for comparing lead sources to potential search engine matches.
     *
     * @param $search string the URL in question
     * @param $urls array list of string potential matches...
     *
     * @return bool
     */
    private function in_urls( $search, $urls )
    {

        foreach ( $urls as $url ){

            /* Given YAML dataset uses .{} as sequence for match all expression, convert into regex friendly */
            $url = str_replace( '.{}', '\.{1,3}', $url );
            $url = str_replace( '{}.', '.{1,}?\.?', $url );
            $pattern = '#' . $url . '#';
//            var_dump( $pattern );
            if ( preg_match( $pattern, $search ) ){
                return true;
            }

        }

        return false;
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

                    foreach ( $this->search_engines as $engine_name => $atts ){
                        $urls = $atts[0]['urls'];
                        if ( $this->in_urls( $lead_source, $urls ) ) {
//                            var_dump( $urls );
                            if ( isset($sources[$engine_name]) ){
                                $sources[$engine_name]++;
                            } else {
                                $sources[$engine_name] = 1;
                            }
                        }
                    }
                }
            }

        }

        if ( empty( $sources ) ){
            printf( '<p class="description">%s</p>', __( 'No new search engine sources to report.', 'groundhogg' ) );
            return;
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        ?>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _e( 'Search Engine', 'groundhogg' ); ?></th>
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
    }

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

                    foreach ( $this->search_engines as $engine_name => $atts ){
                        $urls = $atts[0]['urls'];
                        if ( $this->in_urls( $lead_source, $urls ) ) {
//                            var_dump( $urls );
                            if ( isset($sources[$engine_name]) ){
                                $sources[$engine_name]++;
                            } else {
                                $sources[$engine_name] = 1;
                            }
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