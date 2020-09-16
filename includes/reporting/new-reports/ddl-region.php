<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Contact_Query;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use Groundhogg\Reporting\Reports\Report;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;

class Ddl_Region extends Base_Report {


	public function get_data() {
		$rows = get_db( 'contactmeta' )->query( [
			'contact_id' => $this->get_new_contact_ids_in_time_period(),
			'meta_key'   => 'country'
		], false );

		$values = wp_list_pluck( $rows, 'meta_value' );
		$counts = array_count_values( $values );
		$data   = [];
		foreach ( $counts as $key => $datum ) {
			if ( $key ) {
				$label        = Plugin::$instance->utils->location->get_countries_list( $key );
				$data[ $key ] = $label;
			}
		}

		return [ 'chart' => $data ];
	}


}
