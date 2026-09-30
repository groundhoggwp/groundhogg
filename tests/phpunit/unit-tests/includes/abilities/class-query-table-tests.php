<?php

use function Groundhogg\get_db;

/**
 * groundhogg/query-table, in particular columns whose names aren't lowercase, like the `ID` primary key
 *
 * Runs against the campaigns table, which uses the generic DB::query() - contacts goes through Contact_Query instead,
 * which doesn't read the `where` the ability builds.
 */
class Query_Table_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/query-table' ) ) {
			$this->markTestSkipped( 'The groundhogg/query-table ability is not registered.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function campaign( string $name ): int {
		$id = get_db( 'campaigns' )->add( [ 'name' => $name, 'slug' => sanitize_title( $name ) . '-' . wp_generate_password( 6, false ) ] );

		$this->assertNotEmpty( $id );

		return (int) $id;
	}

	protected function query( array $input ) {
		$out = wp_get_ability( 'groundhogg/query-table' )->execute( array_merge( [ 'table' => 'campaigns' ], $input ) );

		if ( is_wp_error( $out ) ) {
			return $out;
		}

		return json_decode( wp_json_encode( $out ), true );
	}

	protected function ids( array $out ): array {
		return array_map( 'absint', wp_list_pluck( $out['items'], 'ID' ) );
	}

	public function test_select_keeps_the_id_column() {
		$id = $this->campaign( 'select' );

		$out = $this->query( [
			'select' => [ 'ID', 'name' ],
			'where'  => [ [ 'column' => 'ID', 'compare' => 'equals', 'value' => $id ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( [ 'ID', 'name' ], $out['columns'] );
		$this->assertEquals( [ $id ], $this->ids( $out ) );
		$this->assertEquals( 'select', $out['items'][0]['name'] );
	}

	public function test_select_of_only_id() {
		$id = $this->campaign( 'only id' );

		$out = $this->query( [
			'select' => [ 'ID' ],
			'where'  => [ [ 'column' => 'ID', 'compare' => 'equals', 'value' => $id ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( [ 'ID' ], $out['columns'] );
		$this->assertEquals( [ $id ], $this->ids( $out ) );
		$this->assertEquals( [ 'ID' ], array_keys( $out['items'][0] ) );
	}

	public function test_where_on_id() {
		$first  = $this->campaign( 'first' );
		$second = $this->campaign( 'second' );
		$third  = $this->campaign( 'third' );

		$one = $this->query( [ 'where' => [ [ 'column' => 'ID', 'compare' => 'equals', 'value' => $second ] ] ] );

		$this->assertNotWPError( $one );
		$this->assertEquals( 1, $one['total_items'] );
		$this->assertEquals( [ $second ], $this->ids( $one ) );

		$some = $this->query( [ 'where' => [ [ 'column' => 'ID', 'compare' => 'in', 'value' => [ $first, $third ] ] ], 'orderby' => 'ID', 'order' => 'ASC' ] );

		$this->assertNotWPError( $some );
		$this->assertEquals( 2, $some['total_items'] );
		$this->assertEquals( [ $first, $third ], $this->ids( $some ) );
	}

	public function test_orderby_id() {
		$first  = $this->campaign( 'one' );
		$second = $this->campaign( 'two' );
		$in     = [ [ 'column' => 'ID', 'compare' => 'in', 'value' => [ $first, $second ] ] ];

		$asc = $this->query( [ 'where' => $in, 'orderby' => 'ID', 'order' => 'ASC' ] );
		$this->assertNotWPError( $asc );
		$this->assertEquals( [ $first, $second ], $this->ids( $asc ) );

		$desc = $this->query( [ 'where' => $in, 'orderby' => 'ID', 'order' => 'DESC' ] );
		$this->assertNotWPError( $desc );
		$this->assertEquals( [ $second, $first ], $this->ids( $desc ) );
	}

	public function test_lowercase_columns_still_work() {
		$id = $this->campaign( 'Lowercase Columns' );

		$out = $this->query( [
			'select'  => [ 'name' ],
			'orderby' => 'name',
			'where'   => [ [ 'column' => 'name', 'compare' => 'equals', 'value' => 'Lowercase Columns' ] ],
		] );

		$this->assertNotWPError( $out );
		$this->assertEquals( [ 'name' ], $out['columns'] );
		$this->assertEquals( 'Lowercase Columns', $out['items'][0]['name'] );
	}

	public function test_unknown_columns_are_still_refused() {

		foreach ( [
			'select'  => [ 'select' => [ 'nope' ] ],
			'where'   => [ 'where' => [ [ 'column' => 'nope', 'value' => 1 ] ] ],
			'orderby' => [ 'orderby' => 'nope' ],
		] as $which => $input ) {
			$this->assertWPError( $this->query( $input ), $which );
		}

		// a differently-cased name doesn't get matched to a real column, and punctuation is stripped rather than passed on
		$this->assertWPError( $this->query( [ 'where' => [ [ 'column' => 'id', 'value' => 1 ] ] ] ) );
		$this->assertWPError( $this->query( [ 'where' => [ [ 'column' => 'ID; DROP TABLE x', 'value' => 1 ] ] ] ) );
	}
}
