<?php

use Groundhogg\Contact;
use Groundhogg\Rewrites;
use Groundhogg\Utils;
use function Groundhogg\files;
use function Groundhogg\get_contactdata;

/**
 * Before 4.7.2 encrypt() used base64 and not base64url, which changed the name of the folder a contact's files are in.
 * The files are moved to the folder the contact has now when they're asked for.
 *
 * @see \Groundhogg\Classes\Traits\File_Box::maybe_move_legacy_upload_folder()
 * @see Rewrites::current_upload_folder_path()
 */
class Legacy_Contact_Files_Tests extends GH_UnitTestCase {

	protected $folders = [];

	public function tearDown(): void {

		foreach ( $this->folders as $folder ) {
			$this->remove_folder( $folder );
		}

		unset( $_REQUEST['id'] );
		parent::tearDown();
	}

	protected function remove_folder( $folder ) {

		if ( ! is_dir( $folder ) ) {
			return;
		}

		foreach ( array_diff( scandir( $folder ), [ '.', '..' ] ) as $name ) {
			@unlink( $folder . '/' . $name );
		}

		@rmdir( $folder );
	}

	/**
	 * A contact with an email that has a different folder name than it did before 4.7.2, they're only the same for some
	 *
	 * @return Contact
	 */
	protected function contact(): Contact {

		for ( $i = 0; $i < 50; $i ++ ) {

			$contact = get_contactdata( self::factory()->contacts->create( [ 'email' => 'legacy-files-' . uniqid() . $i . '@example.com' ] ) );

			if ( $contact->get_legacy_upload_folder_basename() ) {
				return $contact;
			}
		}

		$this->markTestSkipped( 'Could not make a contact with a folder that changed' );
	}

	protected function folder( Contact $contact, string $basename ): string {
		$folder          = files()->get_uploads_dir( 'uploads', $basename );
		$this->folders[] = $folder;

		return $folder;
	}

	protected function legacy_file( Contact $contact, string $name = 'old.txt', string $content = 'old' ): string {

		$folder = $this->folder( $contact, $contact->get_legacy_upload_folder_basename() );
		wp_mkdir_p( $folder );
		file_put_contents( $folder . '/' . $name, $content );

		return $folder;
	}

	public function test_legacy_folder_is_not_the_current_folder() {

		$contact = $this->contact();

		$this->assertNotEquals( $contact->get_upload_folder_basename(), $contact->get_legacy_upload_folder_basename() );
		$this->assertMatchesRegularExpression( '/^[0-9a-f]{32}$/', $contact->get_legacy_upload_folder_basename() );
	}

	/**
	 * What encrypt() did in 4.7.1 and earlier, copied from that version
	 */
	protected function encrypt_before_4_7_2( $string, $key, $iv ) {

		if ( ctype_xdigit( $key ) ) {
			$key = hex2bin( $key );
			$iv  = hex2bin( $iv );
		}

		$key = substr( hash( 'sha256', $key ), 0, 32 );
		$iv  = substr( hash( 'sha256', $iv ), 0, 16 );

		return base64_encode( openssl_encrypt( $string, 'AES-256-CBC', $key, 0, $iv ) );
	}

	public function test_legacy_folder_is_the_one_4_7_1_used() {

		$key = Utils::get_secret_key();
		$iv  = Utils::get_secret_iv();

		foreach ( [ 'ab', 'adrian', 'a-longer-address' ] as $local ) {

			$id = self::factory()->contacts->create( [ 'email' => $local . '-' . uniqid() . '@example.com' ] );
			$this->assertNotEmpty( $id, $local );
			$contact = new Contact( $id );
			$old     = md5( $this->encrypt_before_4_7_2( $contact->get_email(), $key, $iv ) );

			$this->assertEquals( $old, $contact->get_legacy_upload_folder_basename() );
			$this->assertNotEquals( $old, $contact->get_upload_folder_basename() );
		}
	}

	public function test_legacy_folder_is_the_one_4_7_1_used_with_custom_keys() {

		$original = [ Utils::$secret_key, Utils::$secret_iv ];
		$contact  = get_contactdata( self::factory()->contacts->create( [ 'email' => 'custom-keys-' . uniqid() . '@example.com' ] ) );

		// a hex key and iv, and ones that aren't hex at all, like a defined secret might be
		$pairs = [
			[ bin2hex( 'sixteen byte key' ), bin2hex( 'sixteen byte iv!!' ) ],
			[ 'a plain text secret key', 'a plain text iv' ],
		];

		foreach ( $pairs as [ $key, $iv ] ) {

			Utils::$secret_key = $key;
			Utils::$secret_iv  = $iv;

			$old = md5( $this->encrypt_before_4_7_2( $contact->get_email(), $key, $iv ) );

			$this->assertEquals( $old, $contact->get_legacy_upload_folder_basename(), "key: $key" );
		}

		[ Utils::$secret_key, Utils::$secret_iv ] = $original;
	}

	public function test_files_in_the_legacy_folder_are_listed_and_moved() {

		$contact = $this->contact();
		$legacy  = $this->legacy_file( $contact );
		$current = $this->folder( $contact, $contact->get_upload_folder_basename() );

		$files = $contact->get_files();

		$this->assertCount( 1, $files );
		$this->assertEquals( 'old.txt', $files[0]['name'] );
		$this->assertStringContainsString( $contact->get_upload_folder_basename(), $files[0]['url'] );
		$this->assertFileExists( $current . '/old.txt' );
		$this->assertDirectoryDoesNotExist( $legacy );
	}

	public function test_nothing_is_created_for_a_contact_without_legacy_files() {

		$contact = $this->contact();
		$legacy  = $this->folder( $contact, $contact->get_legacy_upload_folder_basename() );

		$this->assertSame( [], $contact->get_files() );
		$this->assertDirectoryDoesNotExist( $legacy );
	}

	public function test_files_in_both_folders_are_kept() {

		$contact = $this->contact();
		$this->legacy_file( $contact, 'same.txt', 'legacy' );
		$this->legacy_file( $contact, 'only-old.txt', 'legacy' );

		$current = $this->folder( $contact, $contact->get_upload_folder_basename() );
		wp_mkdir_p( $current );
		file_put_contents( $current . '/same.txt', 'current' );

		$names = array_column( $contact->get_files(), 'name' );

		$this->assertCount( 3, $names );
		$this->assertContains( 'same.txt', $names );
		$this->assertContains( 'only-old.txt', $names );
		$this->assertEquals( 'current', file_get_contents( $current . '/same.txt' ) );
		$this->assertDirectoryDoesNotExist( files()->get_uploads_dir( 'uploads', $contact->get_legacy_upload_folder_basename() ) );
	}

	public function test_files_follow_a_changed_email() {

		$contact = $this->contact();
		$this->legacy_file( $contact );

		$new_email = 'legacy-moved-' . uniqid() . '@example.com';
		$contact->update( [ 'email' => $new_email ] );

		$contact = new Contact( $contact->get_id() );
		$this->assertEquals( $new_email, $contact->get_email() );

		$this->folder( $contact, $contact->get_upload_folder_basename() );
		$this->assertEquals( [ 'old.txt' ], array_column( $contact->get_files(), 'name' ) );
	}

	public function test_a_link_to_the_legacy_folder_goes_to_the_current_one() {

		$contact = $this->contact();
		$this->legacy_file( $contact );
		$current = $this->folder( $contact, $contact->get_upload_folder_basename() );

		$_REQUEST['id'] = $contact->get_id();

		$path = Rewrites::current_upload_folder_path( 'uploads/' . $contact->get_legacy_upload_folder_basename() . '/old.txt' );

		$this->assertEquals( 'uploads/' . $contact->get_upload_folder_basename() . '/old.txt', $path );
		$this->assertFileExists( $current . '/old.txt' );

		// it keeps working after the files have been moved
		$this->assertEquals( $path, Rewrites::current_upload_folder_path( 'uploads/' . $contact->get_legacy_upload_folder_basename() . '/old.txt' ) );
	}

	public function test_a_link_for_another_contact_is_left_alone() {

		$contact = $this->contact();
		$other   = $this->contact();
		$this->legacy_file( $contact );

		$_REQUEST['id'] = $other->get_id();

		$path = 'uploads/' . $contact->get_legacy_upload_folder_basename() . '/old.txt';

		$this->assertEquals( $path, Rewrites::current_upload_folder_path( $path ) );
		$this->assertFileExists( files()->get_uploads_dir( 'uploads', $contact->get_legacy_upload_folder_basename() ) . '/old.txt' );
	}

	public function test_paths_that_are_not_contact_files_are_left_alone() {

		$_REQUEST['id'] = self::factory()->contacts->create( [ 'email' => 'legacy-' . uniqid() . '@example.com' ] );

		foreach ( [ 'exports/file.csv', 'uploads/abc', 'uploads/abc/def/file.txt', '' ] as $path ) {
			$this->assertEquals( $path, Rewrites::current_upload_folder_path( $path ) );
		}
	}
}
