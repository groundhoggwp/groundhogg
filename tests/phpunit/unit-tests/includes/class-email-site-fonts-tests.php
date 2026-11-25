<?php

use Groundhogg\Email_Fonts;

/**
 * Offering the fonts the site has already, from theme.json and the Font Library, as uploaded email fonts
 */
class Email_Site_Fonts_Tests extends GH_UnitTestCase {

	/**
	 * Font files made for a test, deleted after it
	 *
	 * @var string[]
	 */
	protected $paths = [];

	/**
	 * The theme.json fontFamilies added for a test
	 *
	 * @var array
	 */
	protected $families = [];

	public function setUp(): void {
		parent::setUp();

		delete_option( Email_Fonts::OPTION );

		add_filter( 'wp_theme_json_data_theme', [ $this, 'add_theme_fonts' ] );
	}

	public function tearDown(): void {

		remove_filter( 'wp_theme_json_data_theme', [ $this, 'add_theme_fonts' ] );
		$this->clean_theme_json();

		// deleting the fonts deletes their copies in the fonts folder
		update_option( Email_Fonts::OPTION, [] );
		delete_option( Email_Fonts::OPTION );

		foreach ( $this->paths as $path ) {
			if ( is_file( $path ) ) {
				wp_delete_file( $path );
			}
		}

		parent::tearDown();
	}

	/**
	 * @param WP_Theme_JSON_Data $theme_json
	 *
	 * @return WP_Theme_JSON_Data
	 */
	public function add_theme_fonts( $theme_json ) {

		if ( empty( $this->families ) ) {
			return $theme_json;
		}

		return $theme_json->update_with( [
			'version'  => 3,
			'settings' => [
				'typography' => [
					'fontFamilies' => $this->families,
				],
			],
		] );
	}

	protected function clean_theme_json() {
		WP_Theme_JSON_Resolver::clean_cached_data();

		if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
			wp_clean_theme_json_cache();
		}
	}

	/**
	 * Fonts for the theme's theme.json
	 *
	 * @param array $families
	 *
	 * @return void
	 */
	protected function theme_fonts( array $families ) {
		$this->families = $families;
		$this->clean_theme_json();
	}

	/**
	 * Put a font file in the uploads folder, where a theme or plugin might have it
	 *
	 * @param string $name
	 * @param string $data
	 *
	 * @return string its URL
	 */
	protected function site_file( string $name, string $data = 'wOF2' ): string {

		$uploads = wp_get_upload_dir();
		$dir     = $uploads['basedir'] . '/test-site-fonts';

		wp_mkdir_p( $dir );
		file_put_contents( $dir . '/' . $name, $data );

		$this->paths[] = $dir . '/' . $name;

		return $uploads['baseurl'] . '/test-site-fonts/' . $name;
	}

	/**
	 * The start of a variable WOFF2 file, enough for its table directory
	 *
	 * @return string
	 */
	protected function variable_font_data(): string {
		$data = 'wOF2' . str_repeat( "\0", 8 ) . pack( 'n', 1 ) . str_repeat( "\0", 34 );

		return $data . chr( array_search( 'fvar', Email_Fonts::WOFF2_TAGS, true ) ) . "\x81\x00";
	}

	protected function find( string $id ) {
		return current( wp_list_filter( Email_Fonts::site_fonts(), [ 'id' => $id ] ) );
	}

	public function test_theme_fonts_are_found() {

		$this->theme_fonts( [
			[
				'name'       => 'Lora',
				'slug'       => 'lora',
				'fontFamily' => '"Lora", serif',
				'fontFace'   => [
					[ 'fontFamily' => 'Lora', 'fontWeight' => '400', 'fontStyle' => 'normal', 'src' => [ $this->site_file( 'Lora-Regular.ttf' ), $this->site_file( 'Lora-Regular.woff2' ) ] ],
					[ 'fontFamily' => 'Lora', 'fontWeight' => 'bold', 'fontStyle' => 'oblique', 'src' => [ $this->site_file( 'Lora-BoldItalic.woff' ) ] ],
					[ 'fontFamily' => 'Lora', 'fontWeight' => '650', 'fontStyle' => 'normal', 'src' => [ $this->site_file( 'Lora-SemiBold.woff2' ) ] ],
				],
			],
			[
				'name'       => 'Inter',
				'slug'       => 'inter',
				'fontFamily' => 'Inter, sans-serif',
				'fontFace'   => [
					[ 'fontFamily' => 'Inter', 'fontWeight' => '300 900', 'fontStyle' => 'normal', 'src' => [ $this->site_file( 'Inter-Variable.woff2' ) ] ],
				],
			],
		] );

		$lora = $this->find( 'lora' );

		$this->assertSame( 'Lora', $lora['name'] );
		$this->assertSame( 'Georgia, serif', $lora['fallback'] );
		$this->assertSame( 'theme', $lora['source'] );

		$uploads = wp_get_upload_dir();

		$this->assertSame( [
			// woff2 is used over ttf
			[ 'path' => wp_normalize_path( $uploads['basedir'] . '/test-site-fonts/Lora-Regular.woff2' ), 'url' => $uploads['baseurl'] . '/test-site-fonts/Lora-Regular.woff2', 'weight' => '400', 'style' => 'normal' ],
			[ 'path' => wp_normalize_path( $uploads['basedir'] . '/test-site-fonts/Lora-SemiBold.woff2' ), 'url' => $uploads['baseurl'] . '/test-site-fonts/Lora-SemiBold.woff2', 'weight' => '700', 'style' => 'normal' ],
			[ 'path' => wp_normalize_path( $uploads['basedir'] . '/test-site-fonts/Lora-BoldItalic.woff' ), 'url' => $uploads['baseurl'] . '/test-site-fonts/Lora-BoldItalic.woff', 'weight' => '700', 'style' => 'italic' ],
		], $lora['files'] );

		$inter = $this->find( 'inter' );

		$this->assertSame( 'Helvetica, Arial, sans-serif', $inter['fallback'] );
		$this->assertSame( 'variable', $inter['files'][0]['weight'] );

		// the editor doesn't get where the files are
		$for_editor = current( wp_list_filter( Email_Fonts::site_fonts_for_editor(), [ 'id' => 'lora' ] ) );
		$this->assertArrayNotHasKey( 'path', $for_editor['files'][0] );
		$this->assertSame( $lora['files'][0]['url'], $for_editor['files'][0]['url'] );
	}

	public function test_only_files_on_this_site_are_used() {

		$uploads = wp_get_upload_dir();

		$this->theme_fonts( [
			[
				'name'       => 'Remote',
				'slug'       => 'remote',
				'fontFamily' => 'Remote, sans-serif',
				'fontFace'   => [
					// another site
					[ 'fontFamily' => 'Remote', 'fontWeight' => '400', 'src' => [ 'https://fonts.gstatic.com/s/remote/v1/Remote-Regular.woff2' ] ],
					// out of the content folder
					[ 'fontFamily' => 'Remote', 'fontWeight' => '700', 'src' => [ $uploads['baseurl'] . '/../../wp-config.php' ] ],
					// not a font
					[ 'fontFamily' => 'Remote', 'fontWeight' => '800', 'src' => [ $this->site_file( 'not-a-font.php', '<?php' ) ] ],
					// not there
					[ 'fontFamily' => 'Remote', 'fontWeight' => '900', 'src' => [ $uploads['baseurl'] . '/test-site-fonts/missing.woff2' ] ],
				],
			],
			[
				'name'       => 'No Faces',
				'slug'       => 'no-faces',
				'fontFamily' => 'system-ui, sans-serif',
			],
		] );

		$this->assertFalse( $this->find( 'remote' ) );
		$this->assertFalse( $this->find( 'system-ui' ) );
	}

	public function test_built_in_and_uploaded_fonts_are_not_offered() {

		$this->theme_fonts( [
			[
				'name'       => 'Georgia',
				'slug'       => 'georgia',
				'fontFamily' => 'Georgia, serif',
				'fontFace'   => [ [ 'fontFamily' => 'Georgia', 'fontWeight' => '400', 'src' => [ $this->site_file( 'Georgia.woff2' ) ] ] ],
			],
			[
				'name'       => 'Lora',
				'slug'       => 'lora',
				'fontFamily' => 'Lora, serif',
				'fontFace'   => [ [ 'fontFamily' => 'Lora', 'fontWeight' => '400', 'src' => [ $this->site_file( 'Lora-Regular.woff2' ) ] ] ],
			],
		] );

		$this->assertFalse( $this->find( 'georgia' ) );
		$this->assertNotFalse( $this->find( 'lora' ) );

		Email_Fonts::make_folder();
		file_put_contents( Email_Fonts::file_path( 'Lora-Mine.woff2' ), 'wOF2' );

		update_option( Email_Fonts::OPTION, Email_Fonts::sanitize( [
			[ 'name' => 'Lora', 'fallback' => 'Georgia, serif', 'files' => [ [ 'file' => 'Lora-Mine.woff2', 'weight' => '400', 'style' => 'normal' ] ] ],
		] ) );

		$this->assertFalse( $this->find( 'lora' ) );
	}

	public function test_add_site_font() {

		$this->theme_fonts( [
			[
				'name'       => 'Lora',
				'slug'       => 'lora',
				'fontFamily' => 'Lora, serif',
				'fontFace'   => [
					// declared as one weight, but the file has them all
					[ 'fontFamily' => 'Lora', 'fontWeight' => '400', 'fontStyle' => 'normal', 'src' => [ $this->site_file( 'Lora.woff2', $this->variable_font_data() ) ] ],
					[ 'fontFamily' => 'Lora', 'fontWeight' => '400', 'fontStyle' => 'italic', 'src' => [ $this->site_file( 'Lora-Italic.woff2' ) ] ],
				],
			],
		] );

		// a file with the same name is in the fonts folder already
		Email_Fonts::make_folder();
		file_put_contents( Email_Fonts::file_path( 'Lora.woff2' ), 'other' );
		$this->paths[] = Email_Fonts::file_path( 'Lora.woff2' );

		$added = Email_Fonts::add_site_font( 'lora' );

		$this->assertNotWPError( $added );
		$this->assertSame( 'Lora', $added['name'] );
		$this->assertSame( 'Georgia, serif', $added['fallback'] );

		$this->assertSame( [
			[ 'file' => 'Lora-1.woff2', 'weight' => 'variable', 'style' => 'normal', 'url' => Email_Fonts::file_url( 'Lora-1.woff2' ) ],
			[ 'file' => 'Lora-Italic.woff2', 'weight' => '400', 'style' => 'italic', 'url' => Email_Fonts::file_url( 'Lora-Italic.woff2' ) ],
		], $added['files'] );

		$this->assertSame( 'other', file_get_contents( Email_Fonts::file_path( 'Lora.woff2' ) ), 'not overwritten' );
		$this->assertSame( $this->variable_font_data(), file_get_contents( Email_Fonts::file_path( 'Lora-1.woff2' ) ) );

		$this->assertSame( [ $added ], Email_Fonts::get() );
		$this->assertFalse( $this->find( 'lora' ), 'not offered once it is added' );

		$again = Email_Fonts::add_site_font( 'lora' );

		$this->assertWPError( $again );
		$this->assertSame( 'font_not_found', $again->get_error_code() );
	}

	public function test_font_library_fonts_are_found() {

		if ( ! post_type_exists( 'wp_font_family' ) ) {
			$this->markTestSkipped( 'No Font Library' );
		}

		$family = self::factory()->post->create( [
			'post_type'    => 'wp_font_family',
			'post_status'  => 'publish',
			'post_title'   => 'Fira Code',
			'post_name'    => 'fira-code',
			// slashed like the Font Library saves it, wp_insert_post() unslashes
			'post_content' => wp_slash( wp_json_encode( [ 'name' => 'Fira Code', 'slug' => 'fira-code', 'fontFamily' => '"Fira Code", monospace' ] ) ),
		] );

		self::factory()->post->create( [
			'post_type'    => 'wp_font_face',
			'post_status'  => 'publish',
			'post_parent'  => $family,
			'post_content' => wp_slash( wp_json_encode( [ 'fontFamily' => '"Fira Code"', 'fontWeight' => '500', 'fontStyle' => 'normal', 'src' => $this->site_file( 'FiraCode-Medium.woff2' ) ] ) ),
		] );

		$font = $this->find( 'fira-code' );

		$this->assertSame( 'Fira Code', $font['name'] );
		$this->assertSame( 'library', $font['source'] );
		$this->assertSame( 'Courier New, Courier, monospace', $font['fallback'] );
		$this->assertSame( '500', $font['files'][0]['weight'] );
	}

	protected function lora_theme_font() {
		$this->theme_fonts( [
			[
				'name'       => 'Lora',
				'slug'       => 'lora',
				'fontFamily' => 'Lora',
				'fontFace'   => [
					[ 'fontFamily' => 'Lora', 'fontWeight' => '400', 'src' => [ $this->site_file( 'Lora-Regular.woff2' ) ] ],
					[ 'fontFamily' => 'Lora', 'fontWeight' => '400', 'fontStyle' => 'italic', 'src' => [ $this->site_file( 'Lora-Italic.woff2' ) ] ],
				],
			],
		] );
	}

	public function test_email_guide_lists_site_fonts() {

		$this->lora_theme_font();

		$summary = ( new \Groundhogg\Abilities\Emails\Email_Block_Renderer() )->design_summary();

		$this->assertStringContainsString( 'which emails can\'t use until `add-site-font` adds them: "Lora" (400, 400 italic).', $summary );

		Email_Fonts::add_site_font( 'lora' );

		$summary = ( new \Groundhogg\Abilities\Emails\Email_Block_Renderer() )->design_summary();

		$this->assertStringNotContainsString( 'add-site-font', $summary );
		$this->assertStringContainsString( '"Lora" (falls back to', $summary );
	}

	public function test_add_site_font_ability() {

		if ( ! function_exists( 'wp_get_ability' ) || ! wp_get_ability( 'groundhogg/add-site-font' ) ) {
			$this->markTestSkipped( 'Abilities are not registered.' );
		}

		$this->lora_theme_font();

		$ability = wp_get_ability( 'groundhogg/add-site-font' );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'marketer' ] ) );
		$this->assertWPError( $ability->execute( [ 'font' => 'Lora' ] ) );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$missing = $ability->execute( [ 'font' => 'Merriweather' ] );
		$this->assertWPError( $missing );
		$this->assertStringContainsString( 'These can be added: Lora.', $missing->get_error_message() );

		// by name, with the fallback the assistant chose
		$added = $ability->execute( [ 'font' => 'Lora', 'fallback' => 'Georgia, serif' ] );

		$this->assertSame( [
			'name'     => 'Lora',
			'fallback' => 'Georgia, serif',
			'files'    => [
				[ 'weight' => '400', 'style' => 'normal', 'url' => Email_Fonts::file_url( 'Lora-Regular.woff2' ) ],
				[ 'weight' => '400', 'style' => 'italic', 'url' => Email_Fonts::file_url( 'Lora-Italic.woff2' ) ],
			],
		], $added );

		// emails can use it now
		$this->assertSame( 'Lora, Georgia, serif', \Groundhogg\Abilities\Emails\Email_Block_Style::normalize_font( 'Lora' ) );

		$again = $ability->execute( [ 'font' => 'Lora' ] );
		$this->assertWPError( $again );
		$this->assertStringContainsString( 'There\'s no font on this site to add.', $again->get_error_message() );

		// a fallback that isn't one of the editor's fonts is refused by the schema
		$this->assertWPError( $ability->execute( [ 'font' => 'Lora', 'fallback' => 'Comic Neue' ] ) );
	}

	public function test_rest_route() {

		$this->theme_fonts( [
			[
				'name'       => 'Lora',
				'slug'       => 'lora',
				'fontFamily' => 'Lora, serif',
				'fontFace'   => [ [ 'fontFamily' => 'Lora', 'fontWeight' => '400', 'src' => [ $this->site_file( 'Lora-Regular.woff2' ) ] ] ],
			],
		] );

		$request = new WP_REST_Request( 'POST', '/gh/v4/emails/fonts/site' );
		$request->set_param( 'id', 'lora' );

		// an editor can't change the site's settings
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'marketer' ] ) );
		$this->assertSame( 403, rest_do_request( $request )->get_status() );

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$response = rest_do_request( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'Lora', $response->get_data()['item']['name'] );

		$this->assertSame( 404, rest_do_request( $request )->get_status() );
	}
}
