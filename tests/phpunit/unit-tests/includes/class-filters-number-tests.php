<?php

use Groundhogg\Contact_Query;
use Groundhogg\DB\Query\Filters;
use Groundhogg\DB\Query\Query;
use Groundhogg\DB\Query\Where;
use function Groundhogg\get_contactdata;

class Filters_Number_Tests extends GH_UnitTestCase {

	protected $contacts = [];

	public function setUp(): void {
		parent::setUp();

		$this->factory()->truncate();

		foreach ( [ '20.25', '20.75', '21' ] as $spent ) {
			$contact_id       = $this->factory()->contacts->create();
			$this->contacts[] = $contact_id;
			get_contactdata( $contact_id )->update_meta( 'spent', $spent );
		}
	}

	/**
	 * @param array $filter
	 *
	 * @return int[] indexes of the matching contacts
	 */
	protected function matching( array $filter ) {

		$query = new Contact_Query();
		$query->where( function ( Where $where, Contact_Query $query ) use ( $filter ) {
			$alias = $query->joinMeta( 'spent' );
			Filters::number( Query::cast2decimal( "$alias.meta_value", 10, 2 ), $filter, $where );
		} );

		$ids = wp_list_pluck( $query->query(), 'ID' );

		return array_keys( array_intersect( $this->contacts, $ids ) );
	}

	public function test_decimal_string_is_not_truncated() {
		$this->assertEquals( [ 0 ], $this->matching( [ 'compare' => 'equals', 'value' => '20.25' ] ) );
		$this->assertEquals( [ 1, 2 ], $this->matching( [ 'compare' => 'greater_than', 'value' => '20.5' ] ) );
		$this->assertEquals( [ 0 ], $this->matching( [ 'compare' => 'less_than', 'value' => '20.5' ] ) );
	}

	public function test_integer_string_still_works() {
		$this->assertEquals( [ 2 ], $this->matching( [ 'compare' => 'equals', 'value' => '21' ] ) );
		$this->assertEquals( [ 0, 1 ], $this->matching( [ 'compare' => 'less_than', 'value' => '21' ] ) );
	}

	public function test_formatted_value_matches_stored_decimal() {
		$contact_id       = $this->factory()->contacts->create();
		$this->contacts[] = $contact_id;
		get_contactdata( $contact_id )->update_meta( 'spent', '1000.99' );

		$this->assertEquals( [ 3 ], $this->matching( [ 'compare' => 'equals', 'value' => '1.000,99' ] ) );
		$this->assertEquals( [ 3 ], $this->matching( [ 'compare' => 'equals', 'value' => '1,000.99' ] ) );
		$this->assertEquals( [ 3 ], $this->matching( [ 'compare' => 'equals', 'value' => '1000,99' ] ) );
		$this->assertEquals( [ 3 ], $this->matching( [ 'compare' => 'greater_than', 'value' => '1.000' ] ) );
	}

	public function number_formats() {
		return [
			// plain
			[ '1000.99', 1000.99 ],
			[ '21', 21 ],
			[ '0', 0 ],
			[ '', 0 ],
			// both separators, the last one is the decimal
			[ '1.000,99', 1000.99 ],
			[ '1,000.99', 1000.99 ],
			[ '1.000.000,5', 1000000.5 ],
			[ '1,000,000.5', 1000000.5 ],
			// one separator used more than once is a thousands separator
			[ '1.000.000', 1000000 ],
			[ '1,000,000', 1000000 ],
			// one separator used once
			[ '1000,99', 1000.99 ],
			[ '12,5', 12.5 ],
			[ '1,000', 1000 ],
			[ '1.000', 1000 ],
			[ '999.999', 999999 ],
			[ '2.125', 2125 ], // ambiguous, read as grouped
			[ '0.125', 0.125 ], // can't be grouped, leading 0
			[ '1000.125', 1000.125 ], // can't be grouped, more than 3 digits before
			[ '.5', 0.5 ],
			// signs and symbols
			[ '-1.000,99', - 1000.99 ],
			[ '-12.5', - 12.5 ],
			[ '$1,000.99', 1000.99 ],
			[ '1 000,99 €', 1000.99 ],
		];
	}

	/**
	 * @dataProvider number_formats
	 */
	public function test_parse_number( $input, $expected ) {
		$this->assertSame( $expected, Filters::parse_number( $input ) );
	}
}
