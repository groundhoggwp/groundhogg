<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use Groundhogg\Reporting\Reports\Report;
use function Groundhogg\get_db;

class Ddl_Funnels extends Base_Report {


	public function get_data() {

		$funnels = get_db( 'funnels' );
		$funnels = $funnels->query( [ 'status' => 'active' ] );

		$options = [];

		foreach ( $funnels as $funnel ) {
			$funnel                       = new Funnel( absint( $funnel->ID ) );
			$options[ $funnel->get_id() ] = $funnel->get_title();
		}

		return ["chart" =>  $options ];
	}

}
