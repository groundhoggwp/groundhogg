<?php

use function Groundhogg\get_db;

class Test_Total_Active_Contacts extends GH_UnitTestCase {

	public function test_query() {

		$this->factory()->truncate();
		$this->factory()->activity->create_many( 50, [
			'activity_type' => 'email_opened',
		] );

		$start = time() - WEEK_IN_SECONDS;
		$end   = time();

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

		$this->assertEquals( 50, $count );

	}


}