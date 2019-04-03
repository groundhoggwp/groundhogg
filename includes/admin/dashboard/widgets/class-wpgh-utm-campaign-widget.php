<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_UTM_Campaign_Widget extends WPGH_Reporting_Widget
{

    /**
     * WPGH_Report_V2 constructor.
     */
    public function __construct()
    {
        $this->wid = 'groundhogg_utm_campaign_widget';
        $this->name = _x( 'UTM Campaign Report', 'widget_name', 'groundhogg' );

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

        $campaigns = wp_list_pluck( $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT meta_value FROM $table_name WHERE meta_key = %s AND contact_id IN ( $ids )", 'utm_campaign' ) ), 'meta_value' );

        foreach ( $campaigns as $campaign ){
            $num_contacts = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(meta_id) FROM $table_name WHERE meta_key = %s AND meta_value = %s AND contact_id IN ( $ids )", 'utm_campaign', $campaign ) );
            $sources[ $campaign ] = $num_contacts;
        }

        if ( empty( $sources ) ){
            printf( '<p class="description">%s</p>', _x( 'Nothing new to report.', 'notice', 'groundhogg' ) );
            return;
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        ?>
        <table class="chart-summary">
        <thead>
        <tr>
            <th><?php _ex( 'Campaign','column_title', 'groundhogg' ); ?></th>
            <th><?php _ex( 'Contacts','column_title', 'groundhogg' ); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php

        foreach ( $sources as $source => $num_contacts ):
            ?>
            <tr>
                <td><?php printf( '%s', $source ); ?></td>
                <td class="summary-total"><a href="<?php echo admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=utm_campaign&meta_value=%s', $source ) ); ?>"><?php printf( '%d', $num_contacts ); ?></a></td>
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

        $contacts = $this->get_contacts_created_within_time_range();

        $sources = array();

        foreach ( $contacts as $contact ){

            $utm = array();

            $utm[ 'campaign' ] = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_campaign', true );
            $utm[ 'source' ]   = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_source', true );
            $utm[ 'medium' ]   = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_medium', true );
            $utm[ 'content' ]  = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_content', true );
            $utm[ 'term' ]     = WPGH()->contact_meta->get_meta( $contact->ID, 'utm_term', true );

            $utm_string = implode( '|', $utm );

            if ( ! empty( $utm_string ) ){

                if ( isset($sources[$utm_string]) ){
                    $sources[$utm_string]++;
                } else {
                    $sources[$utm_string] = 1;
                }

            }

        }

        if ( empty( $sources ) ){
            return _x( 'Nothing new to report.','notice','groundhogg' );
        }

        asort( $sources );
        $sources = array_reverse( $sources, true );

        $export_info = array();

        foreach ( $sources as $source => $num_contacts ):

            $utm = explode( '|', $source );

            $export_info[] = array(
                _x( 'Campaign', 'column_title','groundhogg' )  => $utm[0],
                _x( 'Source', 'column_title','groundhogg' )    => $utm[1],
                _x( 'Medium', 'column_title','groundhogg' )    => $utm[2],
                _x( 'Content','column_title', 'groundhogg' )   => $utm[3],
                _x( 'Term', 'column_title','groundhogg' )      => $utm[4],
                _x( 'Contacts','column_title', 'groundhogg' )  => $num_contacts,
           );

        endforeach;

        return $export_info;


    }


}