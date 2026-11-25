<?php

namespace Groundhogg;

use Groundhogg\Abilities\Emails\Email_Block_Style;
use WP_Error;
use WP_Font_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Font files uploaded for the email editor.
 *
 * A font is a family name, a fallback font from the editor's own list, and its files: one for each weight and style. A
 * variable font file has every weight in it, so its weight is "variable" and its @font-face covers 100 to 900.
 * Blocks use it like the built-in fonts, as a stack "Name, fallback..." in font-family, and an email that uses it gets
 * @font-face rules for its files in the <head>.
 *
 * The files go in the email-fonts folder of Groundhogg's uploads folder, not the media library. They are never loaded
 * from a third party, so opening an email doesn't tell anyone else who opened it.
 *
 * Groundhogg's uploads folder isn't public, its .htaccess denies everything, but mail apps have to load the fonts. So
 * the email-fonts folder has its own .htaccess that lets font files through, and sends Access-Control-Allow-Origin
 * with them because mail apps load them from another origin, see Files::make_public_dir(). That works on Apache and
 * LiteSpeed; nginx ignores .htaccess files, so it has to be in the server's config there, see Files::nginx_rules().
 *
 * Only some mail apps show web fonts (Apple Mail, iOS Mail, Thunderbird, Samsung Mail); Gmail, Yahoo and Outlook on
 * Windows show the fallback. The @font-face rules are in @media screen, because Outlook on Windows ignores those, and it
 * shows Times New Roman instead of the fallback when it sees one.
 */
class Email_Fonts {

	const OPTION = 'gh_email_editor_custom_fonts';

	/**
	 * The folder in Groundhogg's uploads folder the files go in
	 */
	const FOLDER = 'email-fonts';

	/**
	 * The files that are public in the folder, by extension => content type
	 */
	const TYPES = [
		'woff2' => 'font/woff2',
		'woff'  => 'font/woff',
		'ttf'   => 'font/ttf',
		'otf'   => 'font/otf',
	];

	const WEIGHTS = [ '100', '200', '300', '400', '500', '600', '700', '800', '900' ];

	/**
	 * The weight of a variable font file, which has all of them
	 */
	const VARIABLE = 'variable';

	/**
	 * The tables a WOFF2 file refers to by number instead of by tag, in order
	 */
	const WOFF2_TAGS = [
		'cmap', 'head', 'hhea', 'hmtx', 'maxp', 'name', 'OS/2', 'post', 'cvt ', 'fpgm', 'glyf', 'loca', 'prep', 'CFF ',
		'VORG', 'EBDT', 'EBLC', 'gasp', 'hdmx', 'kern', 'LTSH', 'PCLT', 'VDMX', 'vhea', 'vmtx', 'BASE', 'GDEF', 'GPOS',
		'GSUB', 'EBSC', 'JSTF', 'MATH', 'CBDT', 'CBLC', 'COLR', 'CPAL', 'SVG ', 'sbix', 'acnt', 'avar', 'bdat', 'bloc',
		'bsln', 'cvar', 'fdsc', 'feat', 'fmtx', 'fvar', 'gvar', 'hsty', 'just', 'lcar', 'mort', 'morx', 'opbd', 'prop',
		'trak', 'Zapf', 'Silf', 'Glat', 'Gloc', 'Feat', 'Sill',
	];

	const STYLES = [ 'normal', 'italic' ];

	/**
	 * The format() of each file extension in @font-face
	 */
	const FORMATS = [
		'woff2' => 'woff2',
		'woff'  => 'woff',
		'ttf'   => 'truetype',
		'otf'   => 'opentype',
	];

	/**
	 * Words in a font file's name and the weight they mean, the longest first, so "semibold" isn't read as "bold"
	 */
	const WEIGHT_NAMES = [
		'extralight' => '200',
		'ultralight' => '200',
		'extrabold'  => '800',
		'ultrabold'  => '800',
		'semibold'   => '600',
		'demibold'   => '600',
		'regular'    => '400',
		'normal'     => '400',
		'medium'     => '500',
		'light'      => '300',
		'black'      => '900',
		'heavy'      => '900',
		'thin'       => '100',
		'bold'       => '700',
	];

	public function __construct() {
		add_filter( 'groundhogg/email/the_content', [ $this, 'add_font_faces' ] );
		add_action( 'update_option_' . self::OPTION, [ $this, 'delete_removed_files' ], 10, 2 );
	}

	/**
	 * The uploaded fonts, cleaned up
	 *
	 * @return array[] each has id, name, fallback and files, which have file, url, weight and style
	 */
	public static function get(): array {

		$fonts = get_option( self::OPTION, [] );

		if ( ! is_array( $fonts ) || empty( $fonts ) ) {
			return [];
		}

		// Every email that's sent looks at these, and sanitizing checks the files exist
		static $cache = [];

		$key = md5( wp_json_encode( $fonts ) );

		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		return $cache[ $key ] = array_map( function ( $font ) {
			$font['files'] = array_map( function ( $file ) {
				$file['url'] = self::file_url( $file['file'] );

				return $file;
			}, $font['files'] );

			return $font;
		}, self::sanitize( $fonts ) );
	}

	/**
	 * The font-family stack of an uploaded font
	 *
	 * @param array $font
	 *
	 * @return string
	 */
	public static function stack( array $font ): string {
		return $font['name'] . ', ' . $font['fallback'];
	}

	/**
	 * All the font stacks an email can use, the editor's built-in ones first
	 *
	 * @return string[]
	 */
	public static function stacks(): array {
		return array_merge( Email_Block_Style::FONTS, array_map( [ __CLASS__, 'stack' ], self::get() ) );
	}

	/**
	 * The id of a font family, like fontId() in the editor
	 *
	 * @param string $font
	 *
	 * @return string
	 */
	public static function font_id( string $font ): string {
		$first = explode( ',', str_replace( [ '"', "'", '&quot;' ], '', $font ) )[0];

		return str_replace( ' ', '-', strtolower( trim( $first ) ) );
	}

	/**
	 * The URL of an uploaded font file
	 *
	 * @param string $file the file's name in the fonts folder
	 *
	 * @return string
	 */
	public static function file_url( string $file ): string {

		$url = files()->get_uploads_url( self::FOLDER ) . '/' . rawurlencode( $file );

		/**
		 * Filter the URL of an uploaded email font file, to serve it from a CDN for example
		 *
		 * @param string $url
		 * @param string $file the file's name in the fonts folder
		 */
		return apply_filters( 'groundhogg/email_fonts/file_url', $url, $file );
	}

	/**
	 * The path of an uploaded font file
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public static function file_path( string $file ): string {
		return wp_normalize_path( self::folder() . '/' . $file );
	}

	/**
	 * The path of the folder the files are in
	 *
	 * @return string
	 */
	public static function folder(): string {
		return files()->get_uploads_dir( self::FOLDER );
	}

	/**
	 * Make the folder, and Groundhogg's uploads folder if it isn't there yet, with their .htaccess files
	 *
	 * @return void
	 */
	public static function make_folder() {
		files()->make_public_dir( self::FOLDER );
	}

	/**
	 * Sanitize the list of uploaded fonts, as saved by the editor
	 *
	 * - Names keep only letters, numbers, spaces and hyphens, so they never need quotes or escaping in CSS.
	 * - A font with the name of a built-in font, or another font's, is dropped.
	 * - The fallback is one of the built-in fonts.
	 * - Files are font files in the fonts folder, one per weight and style. The weight is 100 to 900, or variable.
	 *
	 * @param mixed $fonts
	 *
	 * @return array[]
	 */
	public static function sanitize( $fonts ): array {

		if ( ! is_array( $fonts ) ) {
			return [];
		}

		$taken = array_map( [ __CLASS__, 'font_id' ], Email_Block_Style::FONTS );
		$clean = [];

		foreach ( $fonts as $font ) {

			if ( ! is_array( $font ) ) {
				continue;
			}

			$name = self::sanitize_name( (string) ( $font['name'] ?? '' ) );
			$id   = self::font_id( $name );

			if ( ! $name || in_array( $id, $taken, true ) ) {
				continue;
			}

			$taken[] = $id;

			$fallback = (string) ( $font['fallback'] ?? '' );

			if ( ! in_array( $fallback, Email_Block_Style::FONTS, true ) ) {
				$fallback = 'Arial, sans-serif';
			}

			$files = [];

			foreach ( (array) ( $font['files'] ?? [] ) as $file ) {

				if ( ! is_array( $file ) ) {
					continue;
				}

				$name_on_disk = self::sanitize_file( (string) ( $file['file'] ?? '' ) );

				if ( ! $name_on_disk ) {
					continue;
				}

				$weight = (string) ( $file['weight'] ?? '400' );
				$style  = (string) ( $file['style'] ?? 'normal' );

				$files[ $weight . $style ] = [
					'file'   => $name_on_disk,
					'weight' => in_array( $weight, self::WEIGHTS, true ) || $weight === self::VARIABLE ? $weight : '400',
					'style'  => in_array( $style, self::STYLES, true ) ? $style : 'normal',
				];
			}

			$clean[] = [
				'id'       => $id,
				'name'     => $name,
				'fallback' => $fallback,
				'files'    => array_values( $files ),
			];
		}

		return $clean;
	}

	/**
	 * A font's name with only letters, numbers, spaces and hyphens
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public static function sanitize_name( string $name ): string {
		$name = preg_replace( '/[^\p{L}\p{N} \-]/u', '', $name );
		$name = trim( preg_replace( '/\s+/', ' ', (string) $name ) );

		return mb_substr( $name, 0, 60 );
	}

	/**
	 * A font file's name, if it's a font file in the fonts folder
	 *
	 * @param string $file
	 *
	 * @return string|false
	 */
	public static function sanitize_file( string $file ) {

		$file = sanitize_file_name( wp_basename( $file ) );
		$ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );

		if ( ! $file || ! isset( self::FORMATS[ $ext ] ) || ! is_file( self::file_path( $file ) ) ) {
			return false;
		}

		return $file;
	}

	/**
	 * Upload a font file to the fonts folder
	 *
	 * @param array $file from $_FILES
	 *
	 * @return array|WP_Error the file's name, URL, and the name, weight and style its file name suggests
	 */
	public static function upload( array $file ) {

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		self::make_folder();

		$mimes = WP_Font_Utils::get_allowed_font_mime_types();

		// Worked out before the filter is added, finding Groundhogg's uploads folder runs the upload_dir filter too
		$path = self::folder();
		$url  = files()->get_uploads_url( self::FOLDER );

		$to_folder = function ( $uploads ) use ( $path, $url ) {
			return array_merge( $uploads, [
				'path'    => $path,
				'url'     => $url,
				'subdir'  => '',
				'basedir' => $path,
				'baseurl' => $url,
				'error'   => false,
			] );
		};

		add_filter( 'upload_mimes', [ WP_Font_Utils::class, 'get_allowed_font_mime_types' ] );
		add_filter( 'upload_dir', $to_folder );

		$uploaded = wp_handle_upload( $file, [
			'test_form' => false,
			'mimes'     => $mimes,
		] );

		remove_filter( 'upload_dir', $to_folder );
		remove_filter( 'upload_mimes', [ WP_Font_Utils::class, 'get_allowed_font_mime_types' ] );

		if ( isset( $uploaded['error'] ) ) {
			return new WP_Error( 'font_upload_failed', $uploaded['error'], [ 'status' => 400 ] );
		}

		$basename = wp_basename( $uploaded['file'] );
		$guess    = self::guess_from_file_name( (string) ( $file['name'] ?? $basename ) );

		if ( self::is_variable_font( $uploaded['file'] ) ) {
			$guess['weight'] = self::VARIABLE;
		}

		return array_merge( [
			'file' => $basename,
			'url'  => self::file_url( $basename ),
		], $guess );
	}

	/**
	 * Fonts the site has already, which can be added to the uploaded fonts without uploading their files again: the
	 * theme's fonts from theme.json (not its style variations', which aren't in use), fonts installed with the Font
	 * Library in the Site Editor, and any a plugin adds with the groundhogg/email_fonts/site_fonts filter.
	 *
	 * Only font files on this site are used, never ones loaded from elsewhere, like Google Fonts. A font with the name of
	 * a built-in font or one that's uploaded already isn't offered.
	 *
	 * @return array[] each has id, name, fallback, source (theme, library or site) and files, which have path, url,
	 *                 weight and style
	 */
	public static function site_fonts(): array {

		$families = [];

		$add_family = function ( $stack, $faces, string $source ) use ( &$families ) {

			$name = self::sanitize_name( trim( explode( ',', (string) $stack )[0], " \"'" ) );
			$id   = self::font_id( $name );

			if ( ! $name || ! is_array( $faces ) ) {
				return;
			}

			if ( ! isset( $families[ $id ] ) ) {
				$families[ $id ] = [
					'id'       => $id,
					'name'     => $name,
					'fallback' => self::fallback_for( (string) $stack ),
					'source'   => $source,
					'files'    => [],
				];
			}

			foreach ( $faces as $face ) {

				if ( ! is_array( $face ) ) {
					continue;
				}

				$file = self::local_font_file( (array) ( $face['src'] ?? [] ) );

				if ( ! $file ) {
					continue;
				}

				$weight = self::weight_of( $face['fontWeight'] ?? $face['font-weight'] ?? '400' );
				$style  = in_array( strtolower( (string) ( $face['fontStyle'] ?? $face['font-style'] ?? '' ) ), [ 'italic', 'oblique' ], true ) ? 'italic' : 'normal';

				// the first file of each weight and style
				if ( ! isset( $families[ $id ]['files'][ $weight . $style ] ) ) {
					$families[ $id ]['files'][ $weight . $style ] = array_merge( $file, [ 'weight' => $weight, 'style' => $style ] );
				}
			}
		};

		// The theme's, and the Font Library's that are active, which are saved in the site's global styles
		if ( function_exists( 'wp_get_global_settings' ) ) {
			foreach ( (array) wp_get_global_settings( [ 'typography', 'fontFamilies' ] ) as $origin => $definitions ) {
				foreach ( (array) $definitions as $definition ) {
					$add_family( $definition['fontFamily'] ?? '', $definition['fontFace'] ?? [], $origin === 'custom' ? 'library' : 'theme' );
				}
			}
		}

		// Installed with the Font Library, active or not
		if ( post_type_exists( 'wp_font_family' ) ) {

			$library = get_posts( [
				'post_type'   => 'wp_font_family',
				'post_status' => 'publish',
				'numberposts' => 100,
			] );

			foreach ( $library as $family ) {

				$settings = json_decode( $family->post_content, true );
				$faces    = array_map( function ( $face ) {
					return json_decode( $face->post_content, true );
				}, get_children( [ 'post_parent' => $family->ID, 'post_type' => 'wp_font_face', 'post_status' => 'publish' ] ) );

				$add_family( $settings['fontFamily'] ?? $family->post_title, $faces, 'library' );
			}
		}

		/**
		 * Filter the fonts the site has already, which can be added as uploaded email fonts
		 *
		 * @param array[] $families font id => id, name, fallback, source and files, which have path (on this server),
		 *                          url, weight (100 to 900, or variable) and style (normal or italic)
		 */
		$families = apply_filters( 'groundhogg/email_fonts/site_fonts', $families );

		$taken = array_merge(
			array_map( [ __CLASS__, 'font_id' ], Email_Block_Style::FONTS ),
			array_column( self::get(), 'id' )
		);

		$fonts = [];

		foreach ( $families as $family ) {

			if ( empty( $family['files'] ) || in_array( $family['id'], $taken, true ) ) {
				continue;
			}

			$family['files'] = array_values( $family['files'] );

			// lightest first, variable before them all, and normal before italic
			usort( $family['files'], function ( $a, $b ) {
				$order = function ( $file ) {
					return ( $file['weight'] === self::VARIABLE ? '000' : $file['weight'] ) . ( $file['style'] === 'italic' ? 1 : 0 );
				};

				return strcmp( $order( $a ), $order( $b ) );
			} );

			$fonts[] = $family;
		}

		return $fonts;
	}

	/**
	 * The fonts the site has already, for the editor, without where their files are on the server
	 *
	 * @return array[]
	 */
	public static function site_fonts_for_editor(): array {
		return array_map( function ( $font ) {
			$font['files'] = array_map( function ( $file ) {
				unset( $file['path'] );

				return $file;
			}, $font['files'] );

			return $font;
		}, self::site_fonts() );
	}

	/**
	 * Add a font the site has already to the uploaded fonts, by copying its files into the fonts folder
	 *
	 * The files are copied, rather than used where they are, because mail apps need the CORS header the fonts folder
	 * has, and so emails keep working if the theme changes or the font is removed from the Font Library.
	 *
	 * @param string $id       the font's id, from site_fonts(), or its name
	 * @param string $fallback one of the editor's fonts, Email_Block_Style::FONTS, instead of the one guessed
	 *
	 * @return array|WP_Error the uploaded font, like get() has it
	 */
	public static function add_site_font( string $id, string $fallback = '' ) {

		$font = current( wp_list_filter( self::site_fonts(), [ 'id' => self::font_id( $id ) ] ) );

		if ( ! $font ) {
			return new WP_Error( 'font_not_found', __( 'That font isn\'t on this site, or it\'s been added already.', 'groundhogg' ), [ 'status' => 404 ] );
		}

		self::make_folder();

		$files = [];

		foreach ( $font['files'] as $file ) {

			$name = wp_unique_filename( self::folder(), sanitize_file_name( wp_basename( $file['path'] ) ) );

			if ( ! files()->copy( $file['path'], self::file_path( $name ) ) ) {
				continue;
			}

			$files[] = [
				'file'   => $name,
				'weight' => self::is_variable_font( self::file_path( $name ) ) ? self::VARIABLE : $file['weight'],
				'style'  => $file['style'],
			];
		}

		if ( empty( $files ) ) {
			return new WP_Error( 'font_not_copied', __( 'Couldn\'t copy the font\'s files into the uploads folder. Check that it\'s writable.', 'groundhogg' ), [ 'status' => 500 ] );
		}

		$fonts   = self::sanitize( get_option( self::OPTION, [] ) );
		$fonts[] = [
			'name'     => $font['name'],
			'fallback' => in_array( $fallback, Email_Block_Style::FONTS, true ) ? $fallback : $font['fallback'],
			'files'    => $files,
		];

		update_option( self::OPTION, self::sanitize( $fonts ) );

		$added = current( wp_list_filter( self::get(), [ 'id' => $font['id'] ] ) );

		return $added ?: new WP_Error( 'font_not_added', __( 'Couldn\'t add the font.', 'groundhogg' ), [ 'status' => 500 ] );
	}

	/**
	 * The editor's fallback font for a font-family stack, by its generic family
	 *
	 * @param string $stack like "Inter", sans-serif
	 *
	 * @return string
	 */
	protected static function fallback_for( string $stack ): string {

		$generics = array_map( function ( $font ) {
			return strtolower( trim( $font, " \"'" ) );
		}, explode( ',', $stack ) );

		if ( in_array( 'monospace', $generics, true ) ) {
			return 'Courier New, Courier, monospace';
		}

		if ( in_array( 'serif', $generics, true ) ) {
			return 'Georgia, serif';
		}

		if ( in_array( 'cursive', $generics, true ) ) {
			return 'Comic Sans MS, Comic Sans, cursive';
		}

		return 'Helvetica, Arial, sans-serif';
	}

	/**
	 * A font-weight from theme.json or the Font Library as one of WEIGHTS, or variable for a range like "100 900"
	 *
	 * @param mixed $weight
	 *
	 * @return string
	 */
	protected static function weight_of( $weight ): string {

		$weight = strtolower( trim( (string) $weight ) );

		if ( preg_match( '/^\d+\s+\d+$/', $weight ) ) {
			return self::VARIABLE;
		}

		if ( $weight === 'bold' ) {
			return '700';
		}

		if ( ! is_numeric( $weight ) ) {
			return '400';
		}

		return (string) ( max( 1, min( 9, (int) round( (float) $weight / 100 ) ) ) * 100 );
	}

	/**
	 * The best of a font face's files that's on this site: woff2 first, it's the smallest
	 *
	 * @param string[] $sources URLs, or file:./ paths in the theme
	 *
	 * @return array|false path and url
	 */
	protected static function local_font_file( array $sources ) {

		$found = [];

		foreach ( $sources as $src ) {

			if ( ! is_string( $src ) || ! $src ) {
				continue;
			}

			if ( str_starts_with( $src, 'file:./' ) ) {
				$relative = substr( $src, 7 );
				$path     = get_theme_file_path( $relative );
				$url      = get_theme_file_uri( $relative );
			} else {
				$url  = $src;
				$path = self::url_to_path( $src );
			}

			$ext = strtolower( pathinfo( (string) $path, PATHINFO_EXTENSION ) );

			if ( ! $path || ! isset( self::FORMATS[ $ext ] ) || ! is_file( $path ) ) {
				continue;
			}

			$found[ $ext ] = [
				'path' => wp_normalize_path( $path ),
				'url'  => $url,
			];
		}

		foreach ( array_keys( self::FORMATS ) as $ext ) {
			if ( isset( $found[ $ext ] ) ) {
				return $found[ $ext ];
			}
		}

		return false;
	}

	/**
	 * Where a URL on this site is on the server, if it's in the content folder or the WordPress folder
	 *
	 * @param string $url
	 *
	 * @return string|false
	 */
	protected static function url_to_path( string $url ) {

		$parts = wp_parse_url( $url );

		if ( empty( $parts['path'] ) ) {
			return false;
		}

		$host = strtolower( $parts['host'] ?? '' );
		$path = rawurldecode( $parts['path'] );

		$roots = [
			content_url() => WP_CONTENT_DIR,
			site_url()    => ABSPATH,
		];

		foreach ( $roots as $root_url => $root_path ) {

			$root = wp_parse_url( $root_url );

			if ( $host && $host !== strtolower( $root['host'] ?? '' ) ) {
				continue;
			}

			$prefix = trailingslashit( $root['path'] ?? '/' );

			if ( ! str_starts_with( $path, $prefix ) ) {
				continue;
			}

			$file      = realpath( trailingslashit( $root_path ) . substr( $path, strlen( $prefix ) ) );
			$real_root = realpath( $root_path );

			// realpath() resolves any ../ so this catches a path that leaves the folder
			if ( $file && $real_root && str_starts_with( wp_normalize_path( $file ), trailingslashit( wp_normalize_path( $real_root ) ) ) ) {
				return wp_normalize_path( $file );
			}
		}

		return false;
	}

	/**
	 * Whether a font file is a variable font, one with all its weights in it, which is a font with an fvar table
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public static function is_variable_font( string $path ): bool {

		// The list of tables is at the start of the file
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file
		$data = is_file( $path ) ? file_get_contents( $path, false, null, 0, 8192 ) : false;

		return $data && in_array( 'fvar', self::table_tags( $data ), true );
	}

	/**
	 * The tags of the tables in a TrueType, OpenType, WOFF or WOFF2 font, from its table directory
	 *
	 * @param string $data the start of the font file
	 *
	 * @return string[]
	 */
	public static function table_tags( string $data ): array {

		$length = strlen( $data );

		if ( $length < 14 ) {
			return [];
		}

		$signature = substr( $data, 0, 4 );
		$tags      = [];

		switch ( $signature ) {

			// TrueType and OpenType, the number of tables and then 16 byte records that start with the tag
			case "\x00\x01\x00\x00":
			case 'OTTO':
			case 'true':
				$count = unpack( 'n', substr( $data, 4, 2 ) )[1];

				for ( $i = 0; $i < $count && 12 + $i * 16 + 4 <= $length; $i ++ ) {
					$tags[] = substr( $data, 12 + $i * 16, 4 );
				}

				break;

			// WOFF, a 44 byte header and then 20 byte records that start with the tag
			case 'wOFF':
				$count = unpack( 'n', substr( $data, 12, 2 ) )[1];

				for ( $i = 0; $i < $count && 44 + $i * 20 + 4 <= $length; $i ++ ) {
					$tags[] = substr( $data, 44 + $i * 20, 4 );
				}

				break;

			// WOFF2, a 48 byte header and then records of different lengths
			case 'wOF2':
				$count  = unpack( 'n', substr( $data, 12, 2 ) )[1];
				$offset = 48;

				for ( $i = 0; $i < $count && $offset < $length; $i ++ ) {

					$flags = ord( $data[ $offset ++ ] );

					// The low 6 bits are a known table's number, or 63 when the tag follows
					if ( ( $flags & 0x3f ) === 0x3f ) {
						$tag    = substr( $data, $offset, 4 );
						$offset += 4;
					} else {
						$tag = self::WOFF2_TAGS[ $flags & 0x3f ] ?? '';
					}

					$tags[] = $tag;

					// The table's length, then its transformed length if it's transformed. glyf and loca are, unless
					// their transform is 3, other tables are when their transform isn't 0.
					$transform = $flags >> 6;
					self::skip_base128( $data, $offset );

					if ( in_array( $tag, [ 'glyf', 'loca' ], true ) ? $transform !== 3 : $transform !== 0 ) {
						self::skip_base128( $data, $offset );
					}
				}

				break;
		}

		return $tags;
	}

	/**
	 * Move past a WOFF2 UIntBase128 number, 1 to 5 bytes where the high bit means more follow
	 *
	 * @param string $data
	 * @param int    $offset
	 *
	 * @return void
	 */
	protected static function skip_base128( string $data, int &$offset ) {
		for ( $i = 0; $i < 5 && $offset < strlen( $data ); $i ++ ) {
			if ( ! ( ord( $data[ $offset ++ ] ) & 0x80 ) ) {
				return;
			}
		}
	}

	/**
	 * The family name, weight and style a font file's name suggests, like "Open Sans", 700 and italic for
	 * OpenSans-BoldItalic.woff2
	 *
	 * @param string $file_name
	 *
	 * @return array
	 */
	public static function guess_from_file_name( string $file_name ): array {

		$base = pathinfo( $file_name, PATHINFO_FILENAME );

		// The family is before the first hyphen or underscore, OpenSans-Bold or Inter_18pt-Regular
		$parts  = preg_split( '/[-_]/', $base, 2 );
		$family = $parts[0];
		$rest   = strtolower( $parts[1] ?? '' );

		// Variable fonts, like Inter[wght].woff2 or InterVariable
		$family = preg_replace( '/\[.*$|variable$/i', '', $family );

		// Split camel case, OpenSans to Open Sans
		$family = preg_replace( '/([a-z])([A-Z])/', '$1 $2', $family );

		$style  = preg_match( '/italic|oblique/', $rest ) ? 'italic' : 'normal';
		$weight = '400';

		if ( preg_match( '/(?<![0-9])([1-9]00)(?![0-9])/', $rest, $match ) ) {
			$weight = $match[1];
		} else {
			$words = str_replace( [ ' ', '-', '_' ], '', $rest );
			foreach ( self::WEIGHT_NAMES as $word => $value ) {
				if ( str_contains( $words, $word ) ) {
					$weight = $value;
					break;
				}
			}
		}

		return [
			'name'   => self::sanitize_name( $family ),
			'weight' => $weight,
			'style'  => $style,
		];
	}

	/**
	 * The uploaded fonts a piece of HTML or CSS uses in a font-family
	 *
	 * @param string $html
	 *
	 * @return array[]
	 */
	public static function used_in( string $html ): array {

		$fonts = self::get();

		if ( empty( $fonts ) || ! preg_match_all( '/font-family\s*:\s*([^;}"]+)/i', $html, $matches ) ) {
			return [];
		}

		$families = [];

		foreach ( $matches[1] as $value ) {
			foreach ( explode( ',', $value ) as $family ) {
				$families[ self::font_id( $family ) ] = true;
			}
		}

		return array_values( array_filter( $fonts, function ( $font ) use ( $families ) {
			return isset( $families[ $font['id'] ] ) && ! empty( $font['files'] );
		} ) );
	}

	/**
	 * The @font-face rules for some fonts, in @media screen so Outlook on Windows skips them
	 *
	 * @param array[] $fonts
	 *
	 * @return string
	 */
	public static function font_face_css( array $fonts ): string {

		$rules = [];

		foreach ( $fonts as $font ) {
			foreach ( $font['files'] as $file ) {

				$ext = strtolower( pathinfo( $file['file'], PATHINFO_EXTENSION ) );

				$rules[] = sprintf(
					"@font-face { font-family: '%s'; src: url('%s') format('%s'); font-weight: %s; font-style: %s; font-display: swap; }",
					$font['name'],
					esc_url( $file['url'] ),
					self::FORMATS[ $ext ] ?? 'woff2',
					$file['weight'] === self::VARIABLE ? '100 900' : $file['weight'],
					$file['style']
				);
			}
		}

		if ( empty( $rules ) ) {
			return '';
		}

		return "@media screen {\n" . implode( "\n", $rules ) . "\n}";
	}

	/**
	 * Add the @font-face rules for the uploaded fonts an email uses to its <head>
	 *
	 * @param string $content the email's HTML
	 *
	 * @return string
	 */
	public function add_font_faces( $content ) {

		if ( ! is_string( $content ) || stripos( $content, '</head>' ) === false ) {
			return $content;
		}

		$css = self::font_face_css( self::used_in( $content ) );

		if ( ! $css ) {
			return $content;
		}

		$style = '<style id="email-fonts">' . $css . '</style>';

		return preg_replace( '/<\/head>/i', $style . '</head>', $content, 1 );
	}

	/**
	 * Delete the files of fonts that were removed
	 *
	 * @param mixed $old_value
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function delete_removed_files( $old_value, $value ) {

		$files_of = function ( $fonts ) {
			$files = [];
			foreach ( is_array( $fonts ) ? $fonts : [] as $font ) {
				foreach ( (array) ( $font['files'] ?? [] ) as $file ) {
					if ( is_array( $file ) && ! empty( $file['file'] ) ) {
						$files[] = sanitize_file_name( wp_basename( (string) $file['file'] ) );
					}
				}
			}

			return $files;
		};

		$removed = array_diff( $files_of( $old_value ), $files_of( $value ) );

		foreach ( $removed as $file ) {

			$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );

			if ( isset( self::FORMATS[ $ext ] ) && is_file( self::file_path( $file ) ) ) {
				wp_delete_file( self::file_path( $file ) );
			}
		}
	}
}
