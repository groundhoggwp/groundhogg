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
	    return $this->meta_query( 'lead_source' );
    }

	public function widget()
    {
        // TODO: Implement widget() method.
    }
}