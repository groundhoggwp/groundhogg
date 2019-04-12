<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_Social_Media_Widget extends WPGH_Lead_Source_Report_Widget
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

	protected function get_data() {

		$sources = [];

		foreach ( $this->meta_query( 'lead_source' ) as $lead_source ){
			if ( ! empty( $lead_source ) && filter_var( $lead_source, FILTER_VALIDATE_URL ) ){

				/* TO avoid long lists of specifics, limit to just the root domin. */
				$test_lead_source = parse_url( $lead_source, PHP_URL_HOST );
				$test_lead_source = str_replace( 'www.', '', $test_lead_source );

				foreach ( $this->social_networks as $network_name => $network_urls ){
					if ( in_array( $test_lead_source, $network_urls ) ){
						$num_contacts = $this->meta_query_count( 'lead_source', $lead_source );
						$sources[ $lead_source ] = [ 'count' => $num_contacts, 'name' => $network_name ];
					}
				}
			}
		}

		return $sources;
	}

    /**
     * Get table of lead sources
     */
    public function widget()
    {
	    $sources = $this->get_data();

	    if ( empty( $sources ) ){
            printf( '<p class="description">%s</p>', _x( 'No new social media sources to report.', 'notice', 'groundhogg' ) );
            return;
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        ?>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _ex( 'Social Media Site', 'column_title','groundhogg' ); ?></th>
            <th><?php _ex( 'Contacts', 'column_title','groundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php

        foreach ( $sources as $source => $data ):

            ?>
            <tr>
                <?php if ( filter_var( $source, FILTER_VALIDATE_URL ) ): ?>
                    <td class=""><?php printf( '<a href="%s">%s</a>', $source, $data[ 'name' ] ); ?></td>
                <?php else: ?>
                    <td class=""><?php printf( '%s', $source ); ?></td>
                <?php endif; ?>
                <td class="summary-total"><?php printf( '<a href="%s">%s</a>', admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=%s&meta_value=%s&meta_compare=RLIKE&date_after=%s&date_before=%s', 'lead_source', urlencode( $source ), date( 'Y-m-d', $this->start_time), date( 'Y-m-d', $this->end_time ) ) ), $data[ 'count' ]  ); ?></td>
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
	    $sources = $this->get_data();

	    if ( empty( $sources ) ){
            return _x( 'No new social media sources to report.', 'notice', 'groundhogg' );
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        $export_info = array();

        foreach ( $sources as $source => $data ):

            $export_info[] = array(
                _x( 'Social Media Source', 'column_title','groundhogg' ) => $data[ 'name' ],
                _x( 'Social Media Url', 'column_title','groundhogg' ) => $source,
                _x( 'Number of Contacts', 'column_title','groundhogg' ) => $data[ 'count' ],
            );

        endforeach;

        return $export_info;
    }
}