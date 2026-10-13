<?php

use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_contactdata;
use function Groundhogg\is_copyable_url;

/**
 * Mapping a value to `copy_file` (CSV import, REST, webhooks) used to accept any path that exists on
 * the server. When the file wasn't inside the uploads directory the sideload was refused, and the
 * cleanup after that refusal deleted it. Inside the uploads directory the original was moved away.
 *
 * Request data must never be able to name a file on the server, and copying a file must never touch
 * the original.
 *
 * @see \Groundhogg\generate_contact_with_map()
 * @see \Groundhogg\is_copyable_url()
 * @see \Groundhogg\Classes\Traits\File_Box::copy_file()
 */
class Contact_Copy_File_Security_Tests extends GH_UnitTestCase {

	/**
	 * @var string[] files to remove afterwards
	 */
	protected $created = [];

	public function tearDown(): void {
		foreach ( $this->created as $file ) {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
		}

		$this->created = [];

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Forward slashes, request data goes through wp_unslash() which would eat backslashes
	 *
	 * @return string a file that is outside of the uploads directory
	 */
	protected function file_outside_uploads() {
		$file = wp_normalize_path( get_temp_dir() ) . 'gh-copy-file-' . wp_generate_password( 8, false ) . '.txt';
		file_put_contents( $file, 'outside' );

		$this->created[] = $file;

		return $file;
	}

	/**
	 * @return string a file that is inside of the uploads directory
	 */
	protected function file_inside_uploads() {
		$dir = wp_normalize_path( wp_get_upload_dir()['basedir'] );
		wp_mkdir_p( $dir );

		$file = $dir . '/gh-copy-file-' . wp_generate_password( 8, false ) . '.txt';
		file_put_contents( $file, 'inside' );

		$this->created[] = $file;

		return $file;
	}

	protected function sales_rep() {
		$rep_id = self::factory()->user->create( [ 'role' => 'sales_rep' ] );
		wp_set_current_user( $rep_id );

		return $rep_id;
	}

	protected function rest_create_contact( array $body ) {
		$request = new WP_REST_Request( 'POST', '/gh/v4/contacts' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		return rest_do_request( $request );
	}

	/* ---------------------------------------------------------------------
	 * Request data can't name a file on the server
	 * ------------------------------------------------------------------- */

	public function test_csv_import_mapping_does_not_delete_a_file_outside_uploads() {

		$this->sales_rep();
		$victim = $this->file_outside_uploads();

		// What the import does with a row of `email,file_path` mapped to email and copy_file
		$contact = generate_contact_with_map(
			[ 'email' => 'csv-copy-file@example.test', 'file_path' => $victim ],
			[ 'email' => 'email', 'file_path' => 'copy_file' ]
		);

		$this->assertTrue( $contact->exists() );
		$this->assertFileExists( $victim );
		$this->assertCount( 0, $contact->get_files() );
	}

	public function test_rest_copy_file_key_does_not_delete_a_file_outside_uploads() {

		$this->sales_rep();
		$victim = $this->file_outside_uploads();

		$response = $this->rest_create_contact( [ 'email' => 'rest-copy-file@example.test', 'copy_file' => $victim ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertFileExists( $victim );
	}

	public function test_rest_files_key_does_not_delete_a_file_outside_uploads() {

		$this->sales_rep();
		$victim = $this->file_outside_uploads();

		$response = $this->rest_create_contact( [ 'email' => 'rest-files@example.test', 'files' => $victim ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertFileExists( $victim );
	}

	public function test_rest_batch_does_not_delete_a_file_outside_uploads() {

		$this->sales_rep();
		$victim = $this->file_outside_uploads();

		$response = $this->rest_create_contact( [ [ 'email' => 'rest-batch@example.test', 'copy_file' => $victim ] ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertFileExists( $victim );
	}

	public function test_a_list_of_paths_is_not_followed_either() {

		$this->sales_rep();
		$victim = $this->file_outside_uploads();

		generate_contact_with_map( [ 'email' => 'list-copy-file@example.test', 'copy_file' => [ $victim ] ] );

		$this->assertFileExists( $victim );
	}

	public function test_a_path_inside_uploads_is_not_moved_into_a_contact_by_request_data() {

		$this->sales_rep();
		$inside = $this->file_inside_uploads();

		$contact = generate_contact_with_map( [ 'email' => 'inside-copy-file@example.test', 'copy_file' => $inside ] );

		$this->assertFileExists( $inside );
		$this->assertCount( 0, $contact->get_files() );
	}

	/* ---------------------------------------------------------------------
	 * Contact::copy_file() itself
	 * ------------------------------------------------------------------- */

	public function test_copy_file_refuses_a_file_outside_uploads_and_leaves_it_alone() {

		$contact = get_contactdata( self::factory()->contacts->create() );
		$victim  = $this->file_outside_uploads();

		$result = $contact->copy_file( $victim );

		$this->assertWPError( $result );
		$this->assertFileExists( $victim );
		$this->assertCount( 0, $contact->get_files() );
	}

	public function test_copy_file_refuses_a_traversal_out_of_uploads() {

		$contact = get_contactdata( self::factory()->contacts->create() );
		$victim  = $this->file_outside_uploads();

		$result = $contact->copy_file( wp_normalize_path( wp_get_upload_dir()['basedir'] ) . '/../../../../../../../../' . ltrim( $victim, '/' ) );

		$this->assertWPError( $result );
		$this->assertFileExists( $victim );
	}

	public function test_copy_file_copies_from_uploads_and_keeps_the_original() {

		$contact = get_contactdata( self::factory()->contacts->create() );
		$inside  = $this->file_inside_uploads();

		$result = $contact->copy_file( $inside );

		$this->assertNotWPError( $result );
		$this->assertFileExists( $inside, 'the original is not moved' );
		$this->assertSame( 'inside', file_get_contents( $inside ) );
		$this->assertCount( 1, $contact->get_files() );
	}

	public function test_a_rejected_copy_leaves_no_temp_file_behind() {

		$contact = get_contactdata( self::factory()->contacts->create() );

		// A type the contact's folder doesn't allow, so the sideload itself is refused
		$inside = wp_normalize_path( wp_get_upload_dir()['basedir'] ) . '/gh-copy-file-' . wp_generate_password( 8, false ) . '.php';
		wp_mkdir_p( dirname( $inside ) );
		file_put_contents( $inside, '<?php // not allowed' );
		$this->created[] = $inside;

		// wp_tempnam() names its files after the original, with a .tmp extension
		$temp_files = function () {
			return glob( get_temp_dir() . 'gh-copy-file-*.tmp' ) ?: [];
		};

		$before = $temp_files();

		$result = $contact->copy_file( $inside );

		$this->assertWPError( $result );
		$this->assertFileExists( $inside, 'the original is not deleted' );
		$this->assertSame( $before, $temp_files() );
	}

	/* ---------------------------------------------------------------------
	 * is_copyable_url()
	 * ------------------------------------------------------------------- */

	public function test_only_urls_on_this_site_are_copyable_urls() {

		$this->assertTrue( is_copyable_url( home_url( '/wp-content/uploads/a.pdf' ) ) );
		$this->assertTrue( is_copyable_url( set_url_scheme( home_url( '/a.pdf' ), 'http' ) ) );

		$this->assertFalse( is_copyable_url( 'https://example.com/a.pdf' ), 'another host' );
		$this->assertFalse( is_copyable_url( $this->file_outside_uploads() ), 'a path that exists' );
		$this->assertFalse( is_copyable_url( ABSPATH . 'wp-config.php' ), 'a path' );
		$this->assertFalse( is_copyable_url( 'file://' . ABSPATH . 'wp-config.php' ), 'the file stream' );
		$this->assertFalse( is_copyable_url( '/wp-content/uploads/a.pdf' ), 'no host' );
		$this->assertFalse( is_copyable_url( [ home_url( '/a.pdf' ) ] ), 'not a string' );
		$this->assertFalse( is_copyable_url( null ) );
	}
}
