<?php

namespace Groundhogg\Reporting\New_Reports;

use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\get_db;
use function Groundhogg\Ymd_His;

class Total_Active_Contacts extends Base_Quick_Stat {

	public function get_link() {
		return admin_page_url( 'gh_contacts', [
			'filters' => base64_json_encode( [
				[
					[
						'type'       => 'was_active',
						'date_range' => 'between',
						'before'     => Ymd_His( $this->end ),
						'after'      => Ymd_His( $this->start )
					]
				]
			] )
		] );
	}

	/**
	 * Query the results
	 *
	 * @param $start int
	 * @param $end   int
	 *
	 * @return mixed
	 */
	protected function query( $start, $end ) {

		$count = get_db( 'activity' )->count( [
			'select'   => 'contact_id',
			'distinct' => true,
			'where'    => [
				'relationship' => 'AND',
				// Start
				[
					'col'     => 'timestamp',
					'val'     => $start,
					'compare' => '>='
				],
				// END
				[
					'col'     => 'timestamp',
					'val'     => $end,
					'compare' => '<='
				],
				[
					'col'     => 'activity_type',
					'val'     => 'email_opened',
					'compare' => '='
				]
			]
		] );

		return $count;
	}
}
