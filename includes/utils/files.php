<?php

namespace Groundhogg;

use WP_Error;

class Files {

	/**
	 * Proxy for filesystem())
	 *
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 */
	public function __call( $name, $arguments ) {
		if ( method_exists( $this->filesystem(), $name ) ) {
			return call_user_func_array( [ $this->filesystem(), $name ], $arguments );
		}

		throw new \BadMethodCallException( sprintf( "Method %s does not exist.", esc_html( $name ) ) );
	}

	/**
	 * Create the uploads dir.
	 */
	public function mk_dir() {
		if ( wp_mkdir_p( $this->get_base_uploads_dir() ) ) {
			$this->add_htaccess();
		}
	}

	/**
	 * The .htaccess of the uploads folder, which denies everything, the way each Apache version reads it: Require for
	 * 2.4, Order and Deny for 2.2. A plain "Deny from all" is an error on 2.4 without mod_access_compat, which makes
	 * every request in the folder fail, the public files' too.
	 */
	const PRIVATE_HTACCESS = <<<'HTACCESS'
# Imports, exports and files uploaded for contacts are private
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
	Order Deny,Allow
	Deny from all
</IfModule>

HTACCESS;

	/**
	 * Create an .htaccess file for the uploads dir.
	 *
	 * It's written if it isn't there, or is still the "Deny from all" of earlier versions. One that was changed, maybe
	 * by the site's admin for their server, is left alone.
	 */
	public function add_htaccess() {

		$htaccess = $this->get_base_uploads_dir() . DIRECTORY_SEPARATOR . '.htaccess';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file
		$current = is_file( $htaccess ) ? trim( (string) file_get_contents( $htaccess ) ) : '';

		if ( $current === '' || $current === 'Deny from all' ) {
			$this->put( $htaccess, self::PRIVATE_HTACCESS, '' );
		}
	}

	/**
	 * Folders in the uploads folder whose files anyone can load, because mail apps load them, and the types of file
	 * that are public in each
	 *
	 * @return array[] folder => [ file extension => content type ]
	 */
	public function public_dirs(): array {

		/**
		 * Filter the folders in the uploads folder that have public files
		 *
		 * @param array[] $dirs folder => [ file extension => content type ]
		 */
		return apply_filters( 'groundhogg/files/public_dirs', [
			Email_Fonts::FOLDER           => Email_Fonts::TYPES,
			Email_Video_Thumbnail::FOLDER => Email_Video_Thumbnail::TYPES,
		] );
	}

	/**
	 * The .htaccess of a folder with public files. The uploads folder denies everything, so this lets files of the given
	 * types through, and only those. They get their content type, which some servers don't know, and
	 * Access-Control-Allow-Origin, which mail apps need to use fonts.
	 *
	 * Access is allowed the way each Apache version reads it: Require for 2.4, Order and Allow for 2.2 and for 2.4's
	 * mod_access_compat, which is what reads a parent folder's "Deny from all", like the one earlier versions wrote.
	 * Order Deny,Allow lets "Allow from all" win over that inherited Deny.
	 *
	 * @param string[] $types file extension => content type
	 *
	 * @return string
	 */
	public function public_htaccess( array $types ): string {

		$add_types  = '';
		$extensions = [];

		foreach ( $types as $extension => $type ) {
			$extension    = sanitize_key( $extension );
			$add_types    .= "\tAddType " . preg_replace( '/[^a-z0-9.+\/-]/', '', strtolower( $type ) ) . " .$extension\n";
			$extensions[] = $extension;
		}

		$extensions = implode( '|', $extensions );

		return <<<HTACCESS
# Mail apps load these files, so they are public, unlike the rest of the uploads folder
<IfModule mod_mime.c>
$add_types</IfModule>
<FilesMatch "\.($extensions)$">
	<IfModule mod_authz_core.c>
		Require all granted
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order Deny,Allow
		Allow from all
	</IfModule>
	<IfModule mod_access_compat.c>
		Order Deny,Allow
		Allow from all
	</IfModule>
	<IfModule mod_headers.c>
		Header set Access-Control-Allow-Origin "*"
	</IfModule>
</FilesMatch>

HTACCESS;
	}

	/**
	 * Make a folder in the uploads folder whose files of the given types are public, see public_htaccess(). The
	 * .htaccess is written again if it was changed. Only Apache and LiteSpeed read it, see nginx_rules().
	 *
	 * @param string $subdir the folder, one of public_dirs()
	 *
	 * @return string the folder's path, empty if it couldn't be made
	 */
	public function make_public_dir( string $subdir ): string {

		$types = $this->public_dirs()[ $subdir ] ?? [];
		$path  = $this->get_uploads_dir( $subdir );

		if ( ! $types || ! $path ) {
			return '';
		}

		if ( ! is_dir( $this->get_base_uploads_dir() ) ) {
			$this->mk_dir();
		}

		if ( ! wp_mkdir_p( $path ) ) {
			return '';
		}

		$htaccess = $path . '/.htaccess';
		$content  = $this->public_htaccess( $types );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file
		if ( ! is_file( $htaccess ) || file_get_contents( $htaccess ) !== $content ) {
			$this->put( $htaccess, $content );
		}

		return $path;
	}

	/**
	 * The nginx config that does what the .htaccess files do on Apache: deny the uploads folder, except the public files
	 *
	 * The folder is a ^~ location so other locations in the config, like one that caches images, can't serve its files.
	 *
	 * @return string
	 */
	public function nginx_rules(): string {

		$base = $this->server_path();

		// quoted, so a space in the path doesn't end the location
		$rules = "location ^~ \"$base/\" {\n\tdeny all;\n";

		foreach ( $this->public_dirs() as $subdir => $types ) {

			$extensions = implode( '|', array_map( 'sanitize_key', array_keys( $types ) ) );

			// a - outside [] needs no escaping, and wp\-content is harder to read, a " would end the quotes
			$path = str_replace( [ '\-', '"' ], [ '-', '\"' ], preg_quote( "$base/$subdir/" ) );

			$rules .= "\n\tlocation ~ \"^$path" . "[^/]+\.($extensions)$\" {\n";
			$rules .= "\t\tallow all;\n";
			$rules .= "\t\tadd_header Access-Control-Allow-Origin \"*\";\n";
			$rules .= "\t}\n";
		}

		return $rules . "}\n";
	}

	/**
	 * The path of the uploads folder that nginx matches locations against, from where it is on the server
	 *
	 * Not from its URL: on a multisite network in subdirectories a site's URLs start with its path, /shop/wp-content/...,
	 * which WordPress' nginx config rewrites to /wp-content/... before matching locations.
	 *
	 * @return string without a trailing slash
	 */
	public function server_path(): string {

		$dir  = wp_normalize_path( $this->get_base_uploads_dir() );
		$root = trailingslashit( wp_normalize_path( ABSPATH ) );

		if ( str_starts_with( $dir, $root ) ) {
			// where WordPress is, which is the network's main site on multisite
			$wordpress = (string) wp_parse_url( is_multisite() ? network_site_url( '/' ) : site_url( '/' ), PHP_URL_PATH );

			return untrailingslashit( '/' . trim( $wordpress, '/' ) ) . '/' . substr( $dir, strlen( $root ) );
		}

		// the content folder is somewhere else, so its URL is the best guess
		return untrailingslashit( (string) wp_parse_url( $this->get_base_uploads_url(), PHP_URL_PATH ) );
	}

	/**
	 * Put a test file in a folder of the uploads folder and ask the site for it, like a visitor would
	 *
	 * @param string $subdir    the folder in the uploads folder, '' for the uploads folder itself
	 * @param string $extension the test file's
	 *
	 * @return array|null the response's code, and whether it was the test file, or null if it couldn't be asked for
	 */
	protected function request_test_file( string $subdir, string $extension ) {

		$token = wp_generate_password( 32, false );
		$file  = 'access-check-' . wp_generate_password( 12, false ) . '.' . sanitize_key( $extension );
		$path  = $this->get_uploads_dir( $subdir, $file );

		if ( ! $path || ! $this->put( $path, $token ) ) {
			return null;
		}

		$response = wp_remote_get( $this->get_uploads_url( $subdir, $file ), [
			'timeout'     => 5,
			'redirection' => 0,
			// the same as WordPress' own loopback requests
			'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
		] );

		wp_delete_file( $path );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		return [
			'code'    => $code,
			'is_file' => $code === 200 && trim( wp_remote_retrieve_body( $response ) ) === $token,
		];
	}

	/**
	 * Whether anyone can download the files in the uploads folder, found by putting a file there and asking the site
	 * for it. The folder's .htaccess is what stops it, which servers that don't read .htaccess files, like nginx, ignore.
	 *
	 * @return bool|null null if it couldn't be checked
	 */
	public function is_private_dir_public() {

		$this->mk_dir();

		$response = $this->request_test_file( '', 'txt' );

		if ( ! $response ) {
			return null;
		}

		if ( $response['code'] === 200 ) {
			return $response['is_file'];
		}

		return in_array( $response['code'], [ 401, 403, 404 ], true ) ? false : null;
	}

	/**
	 * Whether mail apps can load the files in a public folder, by putting a file of its first type there and asking
	 * the site for it. The server may block it, like nginx configured to deny the whole uploads folder, or Apache
	 * that doesn't let the folder's .htaccess override its parent's.
	 *
	 * @param string $subdir one of public_dirs()
	 *
	 * @return bool|null null if it couldn't be checked
	 */
	public function can_load_public_dir( string $subdir ) {

		$types = $this->public_dirs()[ $subdir ] ?? [];

		if ( ! $types || ! $this->make_public_dir( $subdir ) ) {
			return null;
		}

		$response = $this->request_test_file( $subdir, (string) array_key_first( $types ) );

		if ( ! $response ) {
			return null;
		}

		if ( $response['is_file'] ) {
			return true;
		}

		// 500 too, an .htaccess the server can't read fails every request
		return in_array( $response['code'], [ 401, 403, 404, 500 ], true ) ? false : null;
	}

	/**
	 *
	 *
	 * @return \WP_Filesystem_Base
	 */
	public function filesystem() {

		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! $wp_filesystem ){
			WP_Filesystem();
		}

		return $wp_filesystem;
	}

	/**
	 * Get the contents of a file
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function get( string $path ) {
		return $this->filesystem()->get_contents( $path );
	}

	/**
	 * Wrapper for WP_Filesystem
	 *
	 * @param $path
	 * @param $content
	 * @param $mode
	 *
	 * @return bool
	 */
	public function put( $path, $content, $mode = false ) {
		return $this->filesystem()->put_contents( $path, $content, $mode );
	}

	/**
	 * Get the base path.
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function get_base( $type = 'basedir' ) {
		$base = 'groundhogg';

		$upload_dir = wp_get_upload_dir();

		$base = $upload_dir[ $type ] . DIRECTORY_SEPARATOR . $base;

		if ( is_multisite() ) {
			$base .= '/' . get_current_blog_id();
		}

		return wp_normalize_path( apply_filters( "groundhogg/files/uploads/{$type}", $base ) );
	}

	/**
	 * Delete all files in Groundhogg uploads directory.
	 *
	 * @return bool
	 */
	public function delete_all_files() {
		$base_dir = $this->get_base_uploads_dir();
		$this->delete_files( $base_dir );

		return true;
	}

	/**
	 * php delete function that deals with directories recursively
	 */
	public function delete_files( $target ) {
		if ( is_dir( $target ) ) {
			$files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned

			foreach ( $files as $file ) {
				$this->delete_files( $file );
			}

			$this->filesystem()->rmdir( $target );
		} else if ( is_file( $target ) ) {
			@wp_delete_file( $target );
		}
	}


	/**
	 * Get the base uploads path.
	 *
	 * @return string
	 */
	public function get_base_uploads_dir() {
		return $this->get_base( 'basedir' );
	}

	/**
	 * Get the base uploads path.
	 *
	 * @return string
	 */
	public function get_base_uploads_url() {
		return $this->get_base( 'baseurl' );
	}

	/**
	 * Protect against path traversal
	 *
	 * @param  string  $base
	 * @param  string  $subdir
	 * @param  string  $file_path
	 *
	 * @return false|string
	 */
	public function get_path( string $base, string $subdir = 'uploads', string $file_path = '' ) {

		$base      = rtrim( wp_normalize_path( $base ), '/' );
		$subdir    = trim( wp_normalize_path( $subdir ), '/' );
		$file_path = trim( wp_normalize_path( $file_path ), '/' );

		// Reject path traversal.
		$segments = array_merge( explode( '/', $file_path ), explode( '/', $subdir ) );

		if ( in_array( '..', $segments, true ) ) {
			return false;
		}

		$path = $base;

		if ( $subdir ) {
			$path .= '/' . $subdir;
		}

		if ( $file_path ) {
			$path .= '/' . $file_path;
		}

		return untrailingslashit( $path );
	}

	/**
	 * Generic function for mapping to uploads folder.
	 *
	 * @param string $subdir
	 * @param string $file_path
	 * @param bool   $create_folders
	 *
	 * @return string
	 */
	public function get_uploads_dir( $subdir = 'uploads', $file_path = '', $create_folders = false ) {
		$path = $this->get_path( $this->get_base_uploads_dir(), $subdir, $file_path );

		if ( $create_folders && $path ) {
			wp_mkdir_p( dirname( $path ) );
		}

		return $path;
	}

	/**
	 * Generic function for mapping to uploads folder.
	 *
	 * @param string $subdir
	 * @param string $file_path
	 *
	 * @return string
	 */
	public function get_uploads_url( $subdir = 'uploads', $file_path = '' ) {
		return $this->get_path( $this->get_base_uploads_url(), $subdir, $file_path );
	}

	/**
	 * @return string Get the CSV import URL.
	 */
	public function get_csv_imports_dir( $file_path = '', $create_folders = false ) {
		return $this->get_uploads_dir( 'imports', $file_path, $create_folders );
	}

	/**
	 * @return string Get the CSV import URL.
	 */
	public function get_csv_imports_url( $file_path = '' ) {
		return $this->get_uploads_url( 'imports', $file_path );
	}

	/**
	 * @return string Get the CSV import URL.
	 */
	public function get_contact_uploads_dir( $file_path = '', $create_folders = false ) {
		return $this->get_uploads_dir( 'uploads', $file_path, $create_folders );
	}

	/**
	 * @return string Get the CSV import URL.
	 */
	public function get_contact_uploads_url( $file_path = '' ) {
		return $this->get_uploads_url( 'uploads', $file_path );
	}

	/**
	 * @return string Get the CSV export URL.
	 */
	public function get_csv_exports_dir( $file_path = '', $create_folders = false ) {
		return $this->get_uploads_dir( 'exports', $file_path, $create_folders );
	}

	/**
	 * @return string Get the CSV export URL.
	 */
	public function get_csv_exports_url( $file_path = '' ) {
		return $this->get_uploads_url( 'exports', $file_path );
	}

	/**
	 * Get all the imported files available.
	 *
	 * @return array[]
	 */
	public function get_imports() {
		$data = [];

		if ( file_exists( $this->get_csv_imports_dir() ) ) {

			$scanned_directory = array_diff( scandir( $this->get_csv_imports_dir() ), [ '..', '.' ] );

			foreach ( $scanned_directory as $filename ) {

				$filepath = $this->get_csv_imports_dir( $filename );

				$file = [
					'file'      => $filename,
					'file_path' => $filepath,
					'file_url'  => file_access_url( '/imports/' . $filename, true ),
					'date'      => filemtime( $filepath ),
					'rows'      => count_csv_rows( $filepath ),
				];

				$data[] = $file;

			}
		}

		return $data;
	}

	function is_allowed_stream( $file ) {

		$allowed_streams = [
			'https:',
			'http:',
		];

	}

	/**
	 * Sideload a file
	 *
	 * @param $file               array from constructed to resemble something from $_FILES
	 * @param $allowed_mime_types array list of allowed mime types for this upload
	 * @param $folder             string the folder to upload to within the /wp-content/uploads/groundhogg directory
	 *
	 * @return array|string[]|WP_Error
	 *
	 * @see wp_check_filetype_and_ext()
	 * @see get_allowed_mime_types()
	 */
	function safe_file_sideload( &$file, $allowed_mime_types, $folder ) {

		$uploads_dir = wp_get_upload_dir();

		// given local path to a file, only allow sideloading within the uploads directory
		// protects against ../../ traversal attack
		if ( file_exists( $file['tmp_name'] ) && ! self::is_file_within_directory( $file['tmp_name'], $uploads_dir['basedir'] ) ) {
			return new WP_Error( 'nope', 'File upload not allowed.' );
		}

		return $this->safe_file_upload( $file, $allowed_mime_types, $folder, true );
	}

	/**
	 * Upload a file safely
	 *
	 * @param $file               array from $_FILES
	 * @param $allowed_mime_types array list of allowed mime types for this upload
	 * @param $folder             string the folder to upload to within the /wp-content/uploads/groundhogg directory
	 *
	 * @return array|string[]|WP_Error
	 *
	 * @see wp_check_filetype_and_ext()
	 * @see get_allowed_mime_types()
	 */
	function safe_file_upload( &$file, $allowed_mime_types, $folder, $sideload = false ) {

		if ( empty( $file ) ) {
			return new WP_Error( 'empty_file', 'No file was provided.' );
		}

		if ( ! function_exists( '_wp_handle_upload' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
		}

		// do some basic checks on the file content to test for malicious content
		if ( self::is_malicious( $file['tmp_name'], $file['name'] ) ) {
			return new WP_Error( 'file_upload_malicious', 'Potentially malicious upload detected.' );
		}

		$upload_overrides = [
			'test_form' => false,
			'test_size' => true,
			'test_type' => true,
			'mimes'     => $allowed_mime_types,
		];

		$path = $this->get_uploads_dir( $folder );
		$url  = $this->get_uploads_url( $folder );

		$change_upload_dir = fn( $uploads ) => array_merge( $uploads, [
			'path'    => $path,
			'url'     => $url,
			'subdir'  => basename( $path ),
			'basedir' => dirname( $path ),
			'baseurl' => dirname( $url ),
		] );

		add_filter( 'upload_dir', $change_upload_dir );

		$result = _wp_handle_upload(
			$file,
			$upload_overrides,
			null,
			$sideload ? 'wp_handle_sideload' : 'wp_handle_upload'
		);

		remove_filter( 'upload_dir', $change_upload_dir );

		if ( isset( $result['error'] ) ) {
			return new WP_Error( 'file_upload_failed', $result['error'] );
		}

		return $result;
	}

	/**
	 * Upload a file to the Groundhogg file directory
	 *
	 * @param        $file array
	 * @param string $where
	 *
	 * @return array|bool|WP_Error
	 */
	function upload( &$file, $where = 'imports' ) {
		return $this->safe_file_upload( $file, get_allowed_mime_types(), $where );
	}

	/**
	 * Check if a file path is strictly inside a base directory.
	 *
	 * @param string $file_path Absolute path to the file.
	 * @param string $base_dir  Absolute base directory to restrict access to.
	 *
	 * @return bool True if the file is inside the base directory, false otherwise.
	 */
	static public function is_file_within_directory( string $file_path, string $base_dir ): bool {
		$file_path_real = realpath( $file_path );
		$base_dir_real  = realpath( $base_dir );

		if ( ! $file_path_real || ! $base_dir_real ) {
			return false;
		}

		$file_path_real = wp_normalize_path( $file_path_real );
		$base_dir_real  = wp_normalize_path( rtrim( $base_dir_real, '/' ) );

		return str_starts_with( $file_path_real, $base_dir_real . '/' );
	}

	/**
	 * Basic check for potentially malicious file content.
	 *
	 * @param string $filepath Path to the file on disk.
	 *
	 * @return bool True if the file appears malicious, false otherwise.
	 */
	static public function is_malicious( string $filepath, string $filename ): bool {

		// Fail safe: unreadable or missing file
		if ( ! file_exists( $filepath ) || ! is_readable( $filepath ) ) {
			return true;
		}

		// Block known dangerous filenames
		$basename          = strtolower( basename( $filename ) );
		$blocked_filenames = [
			'.htaccess',
			'.user.ini',
			'php.ini',
			'web.config',
			'.env',
		];

		if ( in_array( $basename, $blocked_filenames, true ) ) {
			return true;
		}

		// Read the first part of the file
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- not a remote file
		$bytes = @file_get_contents( $filepath, false, null, 0, 2048 );
		if ( $bytes === false ) {
			return true;
		}

		$bytes = strtolower( $bytes );

		// Look for dangerous patterns
		$danger_signatures = [
			'<?php',               // Executable PHP
			'__halt_compiler()',  // PHAR stub
			'phar://',            // PHAR stream reference
			'base64_decode',      // Obfuscation
			'eval(',              // Dynamic execution
			'shell_exec',         // Command execution
			'passthru',           // Another dangerous system function
		];

		foreach ( $danger_signatures as $needle ) {
			if ( strpos( $bytes, $needle ) !== false ) {
				return true;
			}
		}

		return false;
	}

}
