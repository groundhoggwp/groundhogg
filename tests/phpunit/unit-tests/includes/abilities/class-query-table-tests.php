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

	public function test_contacts_points_to_search_contacts() {
		$this->factory()->contacts->create();

		// would otherwise come back unfiltered, see Contacts::query()
		$out = $this->query( [
			'table' => 'contacts',
			'where' => [ [ 'column' => 'ID', 'compare' => 'equals', 'value' => 1 ] ],
		] );

		$this->assertWPError( $out );
		$this->assertEquals( 'groundhogg_use_search_contacts', $out->get_error_code() );
		$this->assertStringContainsString( 'groundhogg/search-contacts', $out->get_error_message() );

		// the same goes without any conditions
		$this->assertEquals( 'groundhogg_use_search_contacts', $this->query( [ 'table' => 'contacts' ] )->get_error_code() );
	}

	/**
	 * Rows for a table, made up from its own columns and defaults
	 *
	 * @param \Groundhogg\DB\DB $db
	 * @param int               $count
	 *
	 * @return array[] [ 'key' => the column to filter and sort on, 'values' => its value in each row ]
	 */
	protected function seed( $db, int $count = 2 ): array {

		$primary_key = $db->get_primary_key();
		$columns     = $db->get_columns();
		$defaults    = $db->get_column_defaults();

		// The relationship tables have no primary key, so their first column stands in, given distinct values
		$key    = $primary_key ?: array_key_first( $columns );
		$values = [];

		for ( $i = 0; $i < $count; $i ++ ) {

			$row = [];

			foreach ( $columns as $column => $format ) {

				if ( $column === $primary_key ) {
					continue;
				}

				if ( $column === $key ) {
					$row[ $column ] = 7001 + $i;
				} else if ( isset( $defaults[ $column ] ) && $defaults[ $column ] !== '' ) {
					// some columns hold serialized data, and default to an array
					$row[ $column ] = maybe_serialize( $defaults[ $column ] );
				} else if ( $format === '%d' ) {
					$row[ $column ] = 1;
				} else if ( preg_match( '/date|time/', $column ) ) {
					$row[ $column ] = current_time( 'mysql' );
				} else {
					$row[ $column ] = 'v' . wp_generate_password( 8, false );
				}

				// unique keys
				if ( is_string( $row[ $column ] ) && preg_match( '/slug|name|key|email/', $column ) ) {
					$row[ $column ] = 'u' . wp_generate_password( 8, false );
				}
			}

			$id = $db->insert( $row );

			if ( $primary_key ) {
				$this->assertNotEmpty( $id, 'Seeding a row failed: ' . get_class( $db ) );
				$values[] = (int) $id;
			} else {
				$values[] = $row[ $key ];
			}
		}

		return [ 'key' => $key, 'values' => $values ];
	}

	/**
	 * Some tables override DB::query() (Contacts goes through Contact_Query, tags, notes, tasks, emails, email_log and
	 * custom objects add to it), so what the ability builds needs to work for each of them
	 */
	public function test_every_table_applies_where_select_and_orderby() {

		$tables = array_diff( Groundhogg\Abilities\Db\Query_Table::ALLOWED_TABLES, [ 'contacts' ] );

		foreach ( $tables as $table ) {

			$seeded = $this->seed( get_db( $table ) );
			$key    = $seeded['key'];
			[ $first, $second ] = $seeded['values'];

			$one = $this->query( [ 'table' => $table, 'where' => [ [ 'column' => $key, 'compare' => 'equals', 'value' => $first ] ] ] );

			$this->assertNotWPError( $one, $table );
			$this->assertEquals( [ $first ], array_map( 'absint', wp_list_pluck( $one['items'], $key ) ), "$table equals" );
			$this->assertEquals( 1, $one['total_items'], "$table equals total" );

			$both = [ 'column' => $key, 'compare' => 'in', 'value' => [ $first, $second ] ];

			foreach ( [ 'ASC' => [ $first, $second ], 'DESC' => [ $second, $first ] ] as $order => $expected ) {

				$out = $this->query( [ 'table' => $table, 'where' => [ $both ], 'orderby' => $key, 'order' => $order ] );

				$this->assertNotWPError( $out, $table );
				$this->assertEquals( $expected, array_map( 'absint', wp_list_pluck( $out['items'], $key ) ), "$table in, $order" );
				$this->assertEquals( 2, $out['total_items'], "$table in total" );
			}

			$selected = $this->query( [ 'table' => $table, 'select' => [ $key ], 'where' => [ [ 'column' => $key, 'compare' => 'equals', 'value' => $second ] ] ] );

			$this->assertNotWPError( $selected, $table );
			$this->assertEquals( [ $key ], $selected['columns'], "$table select columns" );
			$this->assertEquals( [ $key ], array_keys( $selected['items'][0] ), "$table select item" );
			$this->assertEquals( $second, absint( $selected['items'][0][ $key ] ), "$table select value" );
		}
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
