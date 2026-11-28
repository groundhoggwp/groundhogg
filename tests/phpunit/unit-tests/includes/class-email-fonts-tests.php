<?php

use Groundhogg\Abilities\Emails\Email_Block_Style;
use Groundhogg\Email_Fonts;

/**
 * Fonts uploaded for the email editor: the saved list, the @font-face rules emails get, and the font stacks the block
 * renderer accepts.
 */
class Email_Fonts_Tests extends GH_UnitTestCase {

	protected $files = [];

	public function setUp(): void {
		parent::setUp();
		delete_option( Email_Fonts::OPTION );
	}

	public function tearDown(): void {
		delete_option( Email_Fonts::OPTION );

		foreach ( $this->files as $file ) {
			if ( is_file( Email_Fonts::file_path( $file ) ) ) {
				wp_delete_file( Email_Fonts::file_path( $file ) );
			}
		}

		parent::tearDown();
	}

	/**
	 * Put a file in the fonts folder, like an upload would
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function font_file( string $name ): string {
		wp_mkdir_p( Email_Fonts::folder() );
		file_put_contents( Email_Fonts::file_path( $name ), 'wOF2' );
		$this->files[] = $name;

		return $name;
	}

	protected function save_inter() {
		update_option( Email_Fonts::OPTION, Email_Fonts::sanitize( [
			[
				'name'     => 'Inter',
				'fallback' => 'Helvetica, Arial, sans-serif',
				'files'    => [
					[ 'file' => $this->font_file( 'Inter-Regular.woff2' ), 'weight' => '400', 'style' => 'normal' ],
					[ 'file' => $this->font_file( 'Inter-BoldItalic.woff' ), 'weight' => '700', 'style' => 'italic' ],
				],
			],
		] ) );
	}

	public function test_guess_from_file_name() {
		$this->assertSame( [ 'name' => 'Open Sans', 'weight' => '700', 'style' => 'italic' ], Email_Fonts::guess_from_file_name( 'OpenSans-BoldItalic.woff2' ) );
		$this->assertSame( [ 'name' => 'Fira Sans', 'weight' => '600', 'style' => 'normal' ], Email_Fonts::guess_from_file_name( 'FiraSans-SemiBold.woff2' ) );
		$this->assertSame( [ 'name' => 'Fira Sans', 'weight' => '200', 'style' => 'italic' ], Email_Fonts::guess_from_file_name( 'FiraSans-ExtraLightItalic.ttf' ) );
		$this->assertSame( [ 'name' => 'Inter', 'weight' => '400', 'style' => 'normal' ], Email_Fonts::guess_from_file_name( 'Inter[wght].woff2' ) );
		$this->assertSame( [ 'name' => 'Lato', 'weight' => '300', 'style' => 'normal' ], Email_Fonts::guess_from_file_name( 'Lato_300.otf' ) );
		$this->assertSame( [ 'name' => 'Roboto', 'weight' => '400', 'style' => 'normal' ], Email_Fonts::guess_from_file_name( 'Roboto.woff2' ) );
	}

	/**
	 * The start of a font file with these tables, enough for its table directory
	 *
	 * @param string   $signature
	 * @param string[] $tags
	 *
	 * @return string
	 */
	protected function font_data( string $signature, array $tags ): string {

		switch ( $signature ) {
			case 'wOFF':
				$data = 'wOFF' . str_repeat( "\0", 8 ) . pack( 'n', count( $tags ) ) . str_repeat( "\0", 30 );
				foreach ( $tags as $tag ) {
					$data .= $tag . str_repeat( "\0", 16 );
				}

				return $data;
			case 'wOF2':
				$data = 'wOF2' . str_repeat( "\0", 8 ) . pack( 'n', count( $tags ) ) . str_repeat( "\0", 34 );
				foreach ( $tags as $tag ) {
					$known = array_search( $tag, Email_Fonts::WOFF2_TAGS, true );
					// a known table by number, others by tag, with a 2 byte length, and glyf and loca transformed
					$data .= $known === false ? chr( 0x3f ) . $tag : chr( $known );
					$data .= "\x81\x00";
					$data .= in_array( $tag, [ 'glyf', 'loca' ], true ) ? "\x05" : '';
				}

				return $data;
			default:
				$data = $signature . pack( 'n', count( $tags ) ) . str_repeat( "\0", 6 );
				foreach ( $tags as $tag ) {
					$data .= $tag . str_repeat( "\0", 12 );
				}

				return $data;
		}
	}

	public function test_table_tags() {

		$static   = [ 'GDEF', 'OS/2', 'cmap', 'glyf', 'head', 'loca', 'name' ];
		$variable = [ 'GDEF', 'HVAR', 'OS/2', 'STAT', 'cmap', 'fvar', 'glyf', 'gvar', 'head', 'loca', 'name' ];

		foreach ( [ "\x00\x01\x00\x00", 'OTTO', 'wOFF', 'wOF2' ] as $signature ) {
			$this->assertSame( $static, Email_Fonts::table_tags( $this->font_data( $signature, $static ) ), bin2hex( $signature ) );
			$this->assertSame( $variable, Email_Fonts::table_tags( $this->font_data( $signature, $variable ) ), bin2hex( $signature ) );
		}

		$this->assertSame( [], Email_Fonts::table_tags( '<?php echo "not a font";' ) );
		$this->assertSame( [], Email_Fonts::table_tags( 'wOF2' ) );
	}

	public function test_is_variable_font() {

		$this->font_file( 'Static.woff2' );
		$this->font_file( 'Variable.woff2' );

		file_put_contents( Email_Fonts::file_path( 'Static.woff2' ), $this->font_data( 'wOF2', [ 'cmap', 'glyf', 'head', 'loca' ] ) );
		file_put_contents( Email_Fonts::file_path( 'Variable.woff2' ), $this->font_data( 'wOF2', [ 'cmap', 'fvar', 'glyf', 'gvar', 'head', 'loca' ] ) );

		$this->assertFalse( Email_Fonts::is_variable_font( Email_Fonts::file_path( 'Static.woff2' ) ) );
		$this->assertTrue( Email_Fonts::is_variable_font( Email_Fonts::file_path( 'Variable.woff2' ) ) );
		$this->assertFalse( Email_Fonts::is_variable_font( Email_Fonts::file_path( 'Missing.woff2' ) ) );
	}

	public function test_variable_font_face() {

		update_option( Email_Fonts::OPTION, Email_Fonts::sanitize( [
			[
				'name'     => 'Inter',
				'fallback' => 'Helvetica, Arial, sans-serif',
				'files'    => [
					[ 'file' => $this->font_file( 'Inter-Variable.woff2' ), 'weight' => 'variable', 'style' => 'normal' ],
					[ 'file' => $this->font_file( 'Inter-Italic-Variable.woff2' ), 'weight' => 'variable', 'style' => 'italic' ],
				],
			],
		] ) );

		$css = Email_Fonts::font_face_css( Email_Fonts::get() );

		$this->assertSame( 2, substr_count( $css, 'font-weight: 100 900;' ) );
		$this->assertStringContainsString( 'font-weight: 100 900; font-style: italic;', $css );
	}

	public function test_sanitize() {

		$fonts = Email_Fonts::sanitize( [
			[
				// quotes and braces would break out of the CSS
				'name'     => "Brand'}; body{color:red",
				'fallback' => 'Comic Sans; color: red',
				'files'    => [
					[ 'file' => $this->font_file( 'Brand-Regular.woff2' ), 'weight' => '450', 'style' => 'oblique' ],
					[ 'file' => '../../wp-config.php', 'weight' => '400', 'style' => 'normal' ],
					[ 'file' => 'Missing.woff2', 'weight' => '700', 'style' => 'normal' ],
				],
			],
			// a built-in font's name is taken
			[ 'name' => 'Georgia', 'fallback' => 'Arial, sans-serif', 'files' => [] ],
			// so is one that's already used
			[ 'name' => 'brand  bodycolorred', 'fallback' => 'Arial, sans-serif', 'files' => [] ],
			[ 'name' => '', 'files' => [] ],
			'not a font',
		] );

		$this->assertSame( [
			[
				'id'       => 'brand-bodycolorred',
				'name'     => 'Brand bodycolorred',
				'fallback' => 'Arial, sans-serif',
				'files'    => [
					[ 'file' => 'Brand-Regular.woff2', 'weight' => '400', 'style' => 'normal' ],
				],
			],
		], $fonts );
	}

	public function test_names_are_css_identifiers() {

		// a word that starts with a digit isn't an identifier, so font-family: Exo 2, Arial would be dropped
		$this->assertSame( 'Exo-2', Email_Fonts::sanitize_name( 'Exo 2' ) );
		$this->assertSame( 'Source Sans-3', Email_Fonts::sanitize_name( 'Source Sans 3' ) );
		$this->assertSame( 'M PLUS-1p', Email_Fonts::sanitize_name( 'M PLUS 1p' ) );
		$this->assertSame( 'Arial-2', Email_Fonts::sanitize_name( 'Arial 2' ) );
		$this->assertSame( 'Exo--2', Email_Fonts::sanitize_name( 'Exo -2' ) );
		$this->assertSame( 'Font-8bit Wonder', Email_Fonts::sanitize_name( '8bit Wonder' ) );
		$this->assertSame( 'Font-8bit', Email_Fonts::sanitize_name( '-8bit' ) );
		$this->assertSame( 'Open Sans', Email_Fonts::sanitize_name( 'Open - Sans' ) );
		$this->assertSame( '', Email_Fonts::sanitize_name( '- --' ) );

		// so the stack is valid CSS, every family a run of identifiers
		$this->assertMatchesRegularExpression( '/^(-?[\p{L}_-][\p{L}\p{N}_-]*)( -?[\p{L}_-][\p{L}\p{N}_-]*)*$/u', Email_Fonts::sanitize_name( 'Baloo 2 3D 7' ) );
	}

	public function test_one_file_per_cleaned_weight() {

		$fonts = Email_Fonts::sanitize( [
			[
				'name'     => 'Inter',
				'fallback' => 'Arial, sans-serif',
				'files'    => [
					// bold isn't a weight the editor has, so it's 400 like the second
					[ 'file' => $this->font_file( 'Inter-A.woff2' ), 'weight' => 'bold', 'style' => 'normal' ],
					[ 'file' => $this->font_file( 'Inter-B.woff2' ), 'weight' => '400', 'style' => 'normal' ],
					[ 'file' => $this->font_file( 'Inter-C.woff2' ), 'weight' => '400', 'style' => 'oblique' ],
				],
			],
		] );

		$this->assertSame( [ [ 'file' => 'Inter-C.woff2', 'weight' => '400', 'style' => 'normal' ] ], $fonts[0]['files'] );
	}

	public function test_css_url() {
		$this->assertSame( 'https://cdn.example.com/f.woff2?v=1&x=2', Email_Fonts::css_url( 'https://cdn.example.com/f.woff2?v=1&x=2' ) );
		$this->assertSame( 'https://example.com/a%27%29%20b.woff2', Email_Fonts::css_url( "https://example.com/a') b.woff2" ) );
		$this->assertSame( '', Email_Fonts::css_url( 'javascript:alert(1)' ) );
	}

	public function test_font_faces_with_a_cdn_url() {

		add_filter( 'groundhogg/email_fonts/file_url', $filter = function ( $url, $file ) {
			return 'https://cdn.example.com/$1/' . $file . '?v=2&x=1';
		}, 10, 2 );

		// a font of its own, get() keeps the URLs it worked out for the same fonts
		update_option( Email_Fonts::OPTION, Email_Fonts::sanitize( [
			[ 'name' => 'Cdn Font', 'fallback' => 'Arial, sans-serif', 'files' => [ [ 'file' => $this->font_file( 'CdnFont.woff2' ), 'weight' => '400', 'style' => 'normal' ] ] ],
		] ) );

		$html = ( new Email_Fonts() )->add_font_faces( '<html><head></head><body><p style="font-family:Cdn Font">Hi</p></body></html>' );

		remove_filter( 'groundhogg/email_fonts/file_url', $filter );

		// as it is, not HTML-escaped, and $1 isn't read as a backreference
		$this->assertStringContainsString( "url('https://cdn.example.com/$1/CdnFont.woff2?v=2&x=1')", $html );
	}

	public function test_stacks_and_normalize_font() {

		$this->save_inter();

		$this->assertContains( 'Inter, Helvetica, Arial, sans-serif', Email_Fonts::stacks() );

		$this->assertSame( 'Inter, Helvetica, Arial, sans-serif', Email_Block_Style::normalize_font( '"Inter", sans-serif' ) );
		$this->assertSame( 'Inter, Helvetica, Arial, sans-serif', Email_Block_Style::normalize_font( 'inter' ) );

		// Built-in fonts are unchanged
		$this->assertSame( 'Georgia, serif', Email_Block_Style::normalize_font( 'Georgia' ) );
		$this->assertSame( 'Arial, sans-serif', Email_Block_Style::normalize_font( 'Unknown Font' ) );

		// Once it's deleted, the fallback is used
		delete_option( Email_Fonts::OPTION );

		$this->assertSame( 'Helvetica, Arial, sans-serif', Email_Block_Style::normalize_font( 'Inter, Helvetica, Arial, sans-serif' ) );
	}

	public function test_used_in() {

		$this->save_inter();

		$this->assertCount( 1, Email_Fonts::used_in( '<h1 style="font-family:Inter, Helvetica, Arial, sans-serif;">Hi</h1>' ) );
		$this->assertCount( 1, Email_Fonts::used_in( "<style>p { font-family: 'Inter', sans-serif }</style>" ) );
		$this->assertCount( 1, Email_Fonts::used_in( '<p style="font-family: &quot;Inter&quot;, sans-serif">Hi</p>' ) );

		// The word alone isn't a use
		$this->assertCount( 0, Email_Fonts::used_in( '<p style="font-family:Arial, sans-serif">Inter is a font</p>' ) );
		$this->assertCount( 0, Email_Fonts::used_in( '<p style="font-family:Interstate">Hi</p>' ) );
	}

	public function test_add_font_faces() {

		$this->save_inter();

		$html = ( new Email_Fonts() )->add_font_faces( '<html><head><title>x</title></head><body><p style="font-family:Inter, Helvetica, Arial, sans-serif">Hi</p></body></html>' );

		$this->assertStringContainsString( '<style id="email-fonts">@media screen {', $html );
		$this->assertStringContainsString( "font-family: 'Inter'; src: url('" . Email_Fonts::file_url( 'Inter-Regular.woff2' ) . "') format('woff2'); font-weight: 400; font-style: normal;", $html );
		$this->assertStringContainsString( "format('woff'); font-weight: 700; font-style: italic;", $html );
		$this->assertLessThan( strpos( $html, '</head>' ), strpos( $html, 'email-fonts' ) );

		// Not when the email doesn't use it
		$plain = '<html><head></head><body><p style="font-family:Arial, sans-serif">Hi</p></body></html>';

		$this->assertSame( $plain, ( new Email_Fonts() )->add_font_faces( $plain ) );
	}

	public function test_removed_files_are_deleted() {

		$this->save_inter();

		$this->assertFileExists( Email_Fonts::file_path( 'Inter-BoldItalic.woff' ) );

		$fonts             = get_option( Email_Fonts::OPTION );
		$fonts[0]['files'] = [ $fonts[0]['files'][0] ];

		update_option( Email_Fonts::OPTION, $fonts );

		$this->assertFileDoesNotExist( Email_Fonts::file_path( 'Inter-BoldItalic.woff' ) );
		$this->assertFileExists( Email_Fonts::file_path( 'Inter-Regular.woff2' ) );

		update_option( Email_Fonts::OPTION, [] );

		$this->assertFileDoesNotExist( Email_Fonts::file_path( 'Inter-Regular.woff2' ) );
	}

	public function test_folder_is_in_groundhogg_uploads() {

		$this->assertSame( \Groundhogg\files()->get_base_uploads_dir() . '/email-fonts', Email_Fonts::folder() );
		$this->assertSame( \Groundhogg\files()->get_base_uploads_url() . '/email-fonts/Inter%20Bold.woff2', Email_Fonts::file_url( 'Inter Bold.woff2' ) );
		$this->assertSame( Email_Fonts::folder() . '/Inter.woff2', Email_Fonts::file_path( 'Inter.woff2' ) );
	}

	public function test_make_folder_adds_htaccess() {

		Email_Fonts::make_folder();

		$htaccess = Email_Fonts::folder() . '/.htaccess';

		$expected = \Groundhogg\files()->public_htaccess( Email_Fonts::TYPES );

		$this->assertFileExists( \Groundhogg\files()->get_base_uploads_dir() . '/.htaccess', 'the parent stays private' );
		$this->assertSame( $expected, file_get_contents( $htaccess ) );

		// It's put back if it was changed
		file_put_contents( $htaccess, 'Deny from all' );
		Email_Fonts::make_folder();

		$contents = file_get_contents( $htaccess );

		$this->assertSame( $expected, $contents );

		// Only font files are let through, with the header mail apps need
		$this->assertStringContainsString( '<FilesMatch "\.(woff2|woff|ttf|otf)$">', $contents );
		$this->assertStringContainsString( 'AddType font/woff2 .woff2', $contents );
		$this->assertStringContainsString( 'Require all granted', $contents );
		$this->assertStringContainsString( 'Header set Access-Control-Allow-Origin "*"', $contents );
	}
}
