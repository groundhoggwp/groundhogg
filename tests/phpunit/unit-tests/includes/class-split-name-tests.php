<?php

use function Groundhogg\split_name;

/**
 * The last word is the last name and what is before it is the first name, whatever the names are made of.
 *
 * @see split_name()
 */
class Split_Name_Tests extends GH_UnitTestCase {

	public function names(): array {
		return [
			'first and last'                       => [ 'John Smith', 'John', 'Smith' ],
			'only a first name'                    => [ 'John', 'John', '' ],
			'a middle name goes with the first'    => [ 'Mary Ann Smith', 'Mary Ann', 'Smith' ],
			'a last name that is in the first'     => [ 'Malik M', 'Malik', 'M' ],
			'a last name that is the end of first' => [ 'Anna Ann', 'Anna', 'Ann' ],
			'a last name that is also the first'   => [ 'Smith Smith', 'Smith', 'Smith' ],
			'an apostrophe'                        => [ "Jo O'Neil", 'Jo', "O'Neil" ],
			'a hyphen'                             => [ 'Jo Smith-Jones', 'Jo', 'Smith-Jones' ],
			'accents'                              => [ 'José García', 'José', 'García' ],
			'characters of a regex'                => [ 'Jo (Smith', 'Jo', '(Smith' ],
			'a dot'                                => [ 'Dr. J. Smith', 'Dr. J.', 'Smith' ],
			'extra white space'                    => [ "  John \t  Smith  ", 'John', 'Smith' ],
			'nothing'                              => [ '   ', '', '' ],
		];
	}

	/**
	 * @dataProvider names
	 */
	public function test_a_name_is_split_into_first_and_last( $name, $first, $last ) {
		$this->assertSame( [ $first, $last ], split_name( $name ) );
	}

	public function test_the_parts_of_a_name_in_an_array_are_joined_first() {
		$this->assertSame( [ 'Mary Ann', 'Smith' ], split_name( [ 'Mary', 'Ann', 'Smith' ] ) );
	}
}
