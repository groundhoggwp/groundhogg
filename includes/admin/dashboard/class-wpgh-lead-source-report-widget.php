<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
class WPGH_Lead_Source_Report_Widget extends WPGH_Reporting_Widget
{

    public static $lead_sources = null;

    public function get_lead_sources()
    {
	    if ( self::$lead_sources !== null ){
		    return self::$lead_sources;
	    }

	    $contact_ids = $this->get_contact_ids_created_within_time_range();
	    $ids = implode( ',', $contact_ids );

	    if ( ! empty( $ids ) ){
		    global $wpdb;
		    $table_name = WPGH()->contact_meta->table_name;
		    self::$lead_sources = wp_list_pluck( $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT meta_value FROM $table_name WHERE meta_key = %s AND contact_id IN ( $ids )", 'lead_source' ) ), 'meta_value' );
	    }

	    return self::$lead_sources;
    }


	public function widget()
    {
        // TODO: Implement widget() method.
    }
}