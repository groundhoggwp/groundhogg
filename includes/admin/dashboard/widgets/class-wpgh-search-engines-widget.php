<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_Search_Engines_Widget extends WPGH_Lead_Source_Report_Widget
{

    private $search_engines = array();

    /**
     * WPGH_Report_V2 constructor.
     */
    public function __construct()
    {
        $this->wid = 'groundhogg_search_engines_widget';
        $this->name = _x( 'Search Engine Report', 'widget_name', 'groundhogg' );
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
        $contact_ids = $this->get_contact_ids_created_within_time_range();
        $ids = implode( ',', $contact_ids );

        $sources = array();

        global $wpdb;
        $table_name = WPGH()->contact_meta->table_name;

        $lead_sources = $this->get_lead_sources();

        foreach ( $lead_sources as $lead_source ){
            if ( ! empty( $lead_source ) && filter_var( $lead_source, FILTER_VALIDATE_URL ) ){

                /* TO avoid long lists of specifics, limit to just the root domin. */
                $test_lead_source = parse_url( $lead_source, PHP_URL_HOST );
                $test_lead_source = str_replace( 'www.', '', $test_lead_source );

                foreach ( $this->search_engines as $engine_name => $atts ){

                    $urls = $atts[0]['urls'];
                    if ( $this->in_urls( $test_lead_source, $urls ) ) {
                        $num_contacts = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(meta_id) FROM $table_name WHERE meta_key = %s AND meta_value = %s AND contact_id IN ( $ids )", 'lead_source', $lead_source ) );
                        $sources[ $lead_source ] = [ 'count' => $num_contacts, 'name' => $engine_name ];

                    }
                }
            }
        }

        if ( empty( $sources ) ){
            printf( '<p class="description">%s</p>', _x( 'No new search engine sources to report.', 'notice', 'groundhogg' ) );
            return;
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        ?>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _ex( 'Search Engine', 'column_title' ,'groundhogg' ); ?></th>
            <th><?php _ex( 'Contacts', 'column_title', 'groundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php

        foreach ( $sources as $source => $data ):

            ?>
            <tr>
                <td class=""><?php printf( '<a href="%s">%s</a>', admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=%s&meta_value=%s&meta_compare=RLIKE&date_after=%s&date_before=%s', 'lead_source', urlencode( strtolower( $source ) ), date( 'Y-m-d', $this->start_time), date( 'Y-m-d', $this->end_time ) ) ), $data[ 'name' ] ); ?></td>
                <td class="summary-total"><?php printf( '%d', $data[ 'count' ] ); ?></td>
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

        $lead_sources = $this->get_lead_sources();

        foreach ( $lead_sources as $lead_source ){
            if ( ! empty( $lead_source ) && filter_var( $lead_source, FILTER_VALIDATE_URL ) ){

                /* TO avoid long lists of specifics, limit to just the root domin. */
                $test_lead_source = parse_url( $lead_source, PHP_URL_HOST );
                $test_lead_source = str_replace( 'www.', '', $test_lead_source );

                foreach ( $this->search_engines as $engine_name => $atts ){

                    $urls = $atts[0]['urls'];
                    if ( $this->in_urls( $test_lead_source, $urls ) ) {
                        $num_contacts = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(meta_id) FROM $table_name WHERE meta_key = %s AND meta_value = %s AND contact_id IN ( $ids )", 'lead_source', $lead_source ) );
                        $sources[ $lead_source ] = [ 'count' => $num_contacts, 'name' => $engine_name ];

                    }
                }
            }
        }

        if ( empty( $sources ) ){
            return _x( 'No new search engine sources to report.', 'notice', 'groundhogg' );
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        $export_info = array();

        foreach ( $sources as $source => $data ):

            $export_info[] = array(
                _x( 'Search Engine', 'column_title', 'groundhogg' ) => $data[ 'name' ],
                _x( 'Search Engine Url', 'column_title', 'groundhogg' ) => $source,
                _x( 'Number of Contacts', 'column_title', 'groundhogg' ) => $data[ 'count' ],
            );

        endforeach;

        return $export_info;
    }
}