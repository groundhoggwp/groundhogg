<?php

use Groundhogg\Email;
use Groundhogg\Email_Fonts;
use Groundhogg\Email_Video_Thumbnail;
use function Groundhogg\files;

/**
 * Folders in the uploads folder with files mail apps load, the nginx rules for them, moving the video block's
 * thumbnails into Groundhogg's uploads folder, and the Site Health check that private files are protected.
 */
class Public_Files_Tests extends GH_UnitTestCase {

	protected $paths = [];

	public function tearDown(): void {

		foreach ( array_reverse( $this->paths ) as $path ) {
			if ( is_file( $path ) ) {
				wp_delete_file( $path );
			} else if ( is_dir( $path ) ) {
				@rmdir( $path );
			}
		}

		remove_all_filters( 'pre_http_request' );

		parent::tearDown();
	}

	public function test_public_dirs() {

		$dirs = files()->public_dirs();

		$this->assertSame( Email_Fonts::TYPES, $dirs['email-fonts'] );
		$this->assertSame( [ 'jpg' => 'image/jpeg' ], $dirs['email-video-thumbnails'] );
	}

	public function test_thumbnails_folder_lets_only_jpgs_through() {

		$path = files()->make_public_dir( Email_Video_Thumbnail::FOLDER );

		$this->assertSame( files()->get_uploads_dir( 'email-video-thumbnails' ), $path );

		$contents = file_get_contents( $path . '/.htaccess' );

		$this->assertStringContainsString( '<FilesMatch "\.(jpg)$">', $contents );
		$this->assertStringContainsString( 'AddType image/jpeg .jpg', $contents );
		$this->assertStringNotContainsString( 'woff', $contents );

		// a folder that isn't public isn't made
		$this->assertSame( '', files()->make_public_dir( 'exports' ) );
	}

	public function test_nginx_rules() {

		$base  = wp_parse_url( files()->get_base_uploads_url(), PHP_URL_PATH );
		$rules = files()->nginx_rules();

		$this->assertStringStartsWith( "location ^~ $base/ {\n\tdeny all;\n", $rules );
		$this->assertStringContainsString( "\tlocation ~ ^$base/email-fonts/[^/]+\.(woff2|woff|ttf|otf)$ {\n\t\tallow all;\n\t\tadd_header Access-Control-Allow-Origin \"*\";\n\t}\n", $rules );
		$this->assertStringContainsString( "\tlocation ~ ^$base/email-video-thumbnails/[^/]+\.(jpg)$ {\n", $rules );
		$this->assertStringEndsWith( "\t}\n}\n", $rules );
	}

	public function test_thumbnails_are_in_groundhogg_uploads() {

		$dir = Email_Video_Thumbnail::output_dir();

		$this->assertSame( files()->get_base_uploads_dir() . '/email-video-thumbnails', $dir['path'] );
		$this->assertSame( files()->get_base_uploads_url() . '/email-video-thumbnails', $dir['url'] );
	}

	public function test_copy_legacy_images() {

		$uploads = wp_get_upload_dir();
		$old_dir = $uploads['basedir'] . '/email-video-thumbnails';
		$old_url = $uploads['baseurl'] . '/email-video-thumbnails';
		$new     = Email_Video_Thumbnail::output_dir();
		$file    = 'youtube-test1234567-abcdef123456.jpg';

		wp_mkdir_p( $old_dir );
		file_put_contents( $old_dir . '/' . $file, 'jpeg' );

		$this->paths = [ $old_dir, $old_dir . '/' . $file, $new['path'] . '/' . $file ];

		// saved before the site moved to https
		$http_url = str_replace( 'https://', 'http://', $old_url );

		$email = new Email( [
			'title'      => 'video',
			'subject'    => 'video',
			'content'    => '<a href="https://youtu.be/x"><img src="' . $old_url . '/' . $file . '"></a><img src="' . $http_url . '/' . $file . '">',
			'plain_text' => '![video](' . $old_url . '/' . $file . ')',
		] );

		$other = new Email( [ 'title' => 'other', 'subject' => 'other', 'content' => '<p>nothing</p>' ] );

		Email_Video_Thumbnail::copy_legacy_images();

		$this->assertFileExists( $new['path'] . '/' . $file );
		$this->assertFileExists( $old_dir . '/' . $file, 'kept for emails that were sent' );
		$this->assertFileExists( $new['path'] . '/.htaccess' );

		$email = new Email( $email->get_id() );

		$this->assertSame( '<a href="https://youtu.be/x"><img src="' . $new['url'] . '/' . $file . '"></a><img src="' . str_replace( 'https://', 'http://', $new['url'] ) . '/' . $file . '">', $email->get_content() );
		$this->assertSame( '![video](' . $new['url'] . '/' . $file . ')', $email->plain_text );
		$this->assertSame( '<p>nothing</p>', ( new Email( $other->get_id() ) )->get_content() );

		// running it again changes nothing
		Email_Video_Thumbnail::copy_legacy_images();

		$this->assertSame( '![video](' . $new['url'] . '/' . $file . ')', ( new Email( $email->get_id() ) )->plain_text );
	}

	public function test_copy_legacy_images_without_old_folder() {

		$uploads = wp_get_upload_dir();

		$this->assertDirectoryDoesNotExist( $uploads['basedir'] . '/email-video-thumbnails' );

		Email_Video_Thumbnail::copy_legacy_images();

		$this->assertDirectoryDoesNotExist( $uploads['basedir'] . '/email-video-thumbnails' );
	}

	/**
	 * Answer requests for files in the uploads folder like a server would
	 *
	 * @param bool $public whether it serves them or denies them
	 *
	 * @return void
	 */
	protected function serve_uploads( bool $public ) {
		add_filter( 'pre_http_request', function ( $response, $args, $url ) use ( $public ) {

			$path = str_replace( files()->get_base_uploads_url(), files()->get_base_uploads_dir(), $url );

			if ( ! $public ) {
				return [ 'response' => [ 'code' => 403, 'message' => 'Forbidden' ], 'body' => 'Forbidden', 'headers' => [], 'cookies' => [] ];
			}

			return [ 'response' => [ 'code' => 200, 'message' => 'OK' ], 'body' => file_get_contents( $path ), 'headers' => [], 'cookies' => [] ];
		}, 10, 3 );
	}

	public function test_private_dir_is_public() {

		$this->serve_uploads( true );

		$this->assertTrue( files()->is_private_dir_public() );
		$this->assertEmpty( glob( files()->get_base_uploads_dir() . '/access-check-*' ), 'the test file is deleted' );

		$result = Groundhogg\Plugin::instance()->site_health->test_private_files();

		$this->assertSame( 'critical', $result['status'] );
		$this->assertStringContainsString( esc_html( files()->nginx_rules() ), $result['description'] );
	}

	public function test_private_dir_is_private() {

		$this->serve_uploads( false );

		$this->assertFalse( files()->is_private_dir_public() );
		$this->assertSame( 'good', Groundhogg\Plugin::instance()->site_health->test_private_files()['status'] );
	}

	public function test_private_dir_cant_be_checked() {

		add_filter( 'pre_http_request', function () {
			return new WP_Error( 'http_request_failed', 'cURL error 7' );
		} );

		$this->assertNull( files()->is_private_dir_public() );
		$this->assertSame( 'recommended', Groundhogg\Plugin::instance()->site_health->test_private_files()['status'] );
	}

	public function test_a_page_instead_of_the_file_isnt_public() {

		add_filter( 'pre_http_request', function () {
			return [ 'response' => [ 'code' => 200, 'message' => 'OK' ], 'body' => '<html>Not found</html>', 'headers' => [], 'cookies' => [] ];
		} );

		$this->assertFalse( files()->is_private_dir_public() );
	}
}
