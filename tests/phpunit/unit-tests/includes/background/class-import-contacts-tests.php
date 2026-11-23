<?php

use Groundhogg\Background\Import_Contacts;
use function Groundhogg\count_csv_rows;
use function Groundhogg\files;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\get_items_from_csv;
use function Groundhogg\validate_tags;

/**
 * CSV imports where lines aren't rows: quoted cells with line breaks, blank lines, and Excel's byte order mark
 */
class Import_Contacts_Tests extends GH_UnitTestCase {

	protected $files = [];

	public function tearDown(): void {
		foreach ( $this->files as $file ) {
			wp_delete_file( $file );
		}

		parent::tearDown();
	}

	/**
	 * Write a CSV to the imports folder
	 *
	 * @param array  $rows   rows of cells, the first is the header
	 * @param string $prefix e.g. a byte order mark
	 *
	 * @return string the file name
	 */
	protected function csv( array $rows, string $prefix = '' ): string {

		$name = 'import-test-' . uniqid() . '.csv';
		$path = files()->get_csv_imports_dir( $name, true );

		$handle = fopen( 'php://temp', 'r+' );

		foreach ( $rows as $row ) {
			if ( $row === null ) {
				fwrite( $handle, "\n" );
				continue;
			}

			fputcsv( $handle, $row );
		}

		rewind( $handle );
		file_put_contents( $path, $prefix . stream_get_contents( $handle ) );
		fclose( $handle );

		$this->files[] = $path;

		return $name;
	}

	/**
	 * Rows whose notes span several lines, with a blank line and a bad email mixed in
	 *
	 * @param int $count
	 *
	 * @return array
	 */
	protected function rows( int $count ): array {

		$rows = [ [ 'Email', 'First Name', 'Notes' ] ];

		for ( $i = 0; $i < $count; $i ++ ) {
			$email  = $i % 50 === 7 ? 'not-an-email-' . $i : "row{$i}@import-test.example";
			$rows[] = [ $email, "Person {$i}", "First line {$i}\nSecond line\nThird line" ];

			if ( $i === 30 ) {
				$rows[] = null; // a blank line
			}
		}

		return $rows;
	}

	public function test_rows_are_counted_as_records_not_lines() {

		$path = files()->get_csv_imports_dir( $this->csv( $this->rows( 10 ) ) );

		$this->assertSame( 10, count_csv_rows( $path ) );
	}

	public function test_an_offset_skips_records_not_lines() {

		$path  = files()->get_csv_imports_dir( $this->csv( $this->rows( 10 ) ) );
		$items = get_items_from_csv( $path, 2, 4 );

		$this->assertCount( 2, $items );
		$this->assertSame( 'row4@import-test.example', $items[0]['Email'] );
		$this->assertSame( 'row5@import-test.example', $items[1]['Email'] );
	}

	public function test_the_excel_byte_order_mark_is_not_part_of_the_first_header() {

		$path  = files()->get_csv_imports_dir( $this->csv( $this->rows( 1 ), "\xEF\xBB\xBF" ) );
		$items = get_items_from_csv( $path, 1 );

		$this->assertArrayHasKey( 'Email', $items[0] );
		$this->assertSame( 'row0@import-test.example', $items[0]['Email'] );
	}

	/**
	 * Run the task like the background runner does, saved and loaded again between batches
	 *
	 * @param Import_Contacts $task
	 *
	 * @return Import_Contacts
	 */
	protected function run_in_background( Import_Contacts $task ): Import_Contacts {

		$batches = 0;

		do {
			$task = unserialize( serialize( $task ) );
			$this->assertTrue( $task->can_run() );
			$done = $task->process();
			$task->stop();
		} while ( $done === false && ++ $batches < 20 );

		$this->assertTrue( $done, 'The import finishes' );

		return $task;
	}

	public function test_every_row_is_imported_once_across_batches() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$count = Import_Contacts::BATCH_LIMIT * 2 + 5;
		$tag   = validate_tags( [ 'Import test ' . uniqid() ] )[0];
		$file  = $this->csv( $this->rows( $count ), "\xEF\xBB\xBF" );

		$task = $this->run_in_background( new Import_Contacts( $file, [
			'field_map' => [ 'Email' => 'email', 'First Name' => 'first_name', 'Notes' => 'notes' ],
			'tags'      => [ $tag ],
		] ) );

		$bad = count( array_filter( range( 0, $count - 1 ), function ( $i ) {
			return $i % 50 === 7;
		} ) );

		$this->assertSame( $count - $bad, get_db( 'contacts' )->count( [ 'tags_include' => [ $tag ] ] ) );
		$this->assertSame( $bad, $task->get_skipped(), 'Rows without a valid email are counted as skipped' );

		foreach ( [ 0, 99, 100, 101, 199, 200, $count - 1 ] as $i ) {
			$contact = get_contactdata( "row{$i}@import-test.example" );
			$this->assertTrue( $contact && $contact->exists(), "Row $i is imported" );
			$this->assertSame( "Person {$i}", $contact->get_first_name(), "Row $i has its own name" );
		}
	}

	public function test_a_task_queued_before_positions_were_kept_resumes_at_its_batch() {

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$count = Import_Contacts::BATCH_LIMIT + 3;
		$tag   = validate_tags( [ 'Import test ' . uniqid() ] )[0];
		$file  = $this->csv( $this->rows( $count ) );

		// what an older version saved, one batch in
		$task = new Import_Contacts( $file, [], 0 );
		$task->__unserialize( [
			'fileName' => $file,
			'batch'    => 1,
			'user_id'  => get_current_user_id(),
			'settings' => [
				'field_map' => [ 'Email' => 'email', 'First Name' => 'first_name' ],
				'tags'      => [ $tag ],
			],
		] );

		$this->run_in_background( $task );

		$this->assertSame( 3, get_db( 'contacts' )->count( [ 'tags_include' => [ $tag ] ] ), 'Only the rows after the first batch' );
		$this->assertSame( 'Person ' . ( $count - 1 ), get_contactdata( 'row' . ( $count - 1 ) . '@import-test.example' )->get_first_name() );
	}
}
