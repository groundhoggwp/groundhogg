<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Broadcast;
use Groundhogg\Contact_Query;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use Groundhogg\Reporting\Reports\Report;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;

class Ddl_Broadcasts extends Base_Report {


	public function get_data() {

		$broadcasts = get_db( 'broadcasts' );
		$broadcasts = $broadcasts->query( [ 'status' => 'sent' ] );

		$options = [];

		foreach ( $broadcasts as $broadcast ) {
			$broadcast                       = new Broadcast( absint( $broadcast->ID ) );
			$options[ $broadcast->get_id() ] = $broadcast->get_title();
		}
		return ["chart" =>  $options ];
	}


}
