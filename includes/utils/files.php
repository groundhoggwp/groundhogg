<?php

namespace Groundhogg;

use WP_Error;

class Files {

	/**
	 * Create the uploads dir.
	 */
	public function mk_dir() {
		if ( wp_mkdir_p( $this->get_base_uploads_dir() ) ) {
			$this->add_htaccess();
		}
	}

	/**
	 * Create an .htaccess file for the uploads dir.
	 */
	public function add_htaccess() {
		$htaccess_content = "Deny from all";
		$base_url         = $this->get_base_uploads_dir();
		file_put_contents( $base_url . DIRECTORY_SEPARATOR . '.htaccess', $htaccess_content );
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

			@rmdir( $target );
		} else if ( is_file( $target ) ) {
			@unlink( $target );
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
	 * Generic function for mapping to uploads folder.
	 *
	 * @param string $subdir
	 * @param string $file_path
	 * @param bool   $create_folders
	 *
	 * @return string
	 */
	public function get_uploads_dir( $subdir = 'uploads', $file_path = '', $create_folders = false ) {
		$path = untrailingslashit( wp_normalize_path( sprintf( "%s/%s/%s", $this->get_base_uploads_dir(), $subdir, $file_path ) ) );

		if ( $create_folders ) {
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
		return untrailingslashit( sprintf( "%s/%s/%s", $this->get_base_uploads_url(), $subdir, $file_path ) );
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
		$bytes = file_get_contents( $filepath, false, null, 0, 2048 );
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
