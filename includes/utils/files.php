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
	 * @param bool $create_folders
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
		$path = untrailingslashit( sprintf( "%s/%s/%s", $this->get_base_uploads_url(), $subdir, $file_path ) );

		return $path;
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
					'rows'      => count( file( $filepath, FILE_SKIP_EMPTY_LINES ) ) - 1,
				];

				$data[] = $file;

			}
		}

		return $data;
	}

	/**
	 * @var array
	 */
	protected $uploads_path = [];

	/**
	 * Change the default upload directory
	 *
	 * @param $param
	 *
	 * @return mixed
	 */
	public function files_upload_dir( $param ) {
		$param['path']   = $this->uploads_path['path'];
		$param['url']    = $this->uploads_path['url'];
		$param['subdir'] = $this->uploads_path['subdir'];

		return $param;
	}

	/**
	 * Initialize the base upload path
	 *
	 * @param string $where
	 */
	private function set_uploads_path( $where='imports' ) {
		$this->uploads_path['subdir'] = Plugin::$instance->utils->files->get_base_uploads_dir();
		$this->uploads_path['path']   = Plugin::$instance->utils->files->get_uploads_dir( $where, '', true );
		$this->uploads_path['url']    = Plugin::$instance->utils->files->get_uploads_dir( $where, '', true );
	}

	/**
	 * Upload a file to the Groundhogg file directory
	 *
	 * @param        $file array
	 * @param string $where
	 *
	 * @return array|bool|WP_Error
	 */
	function upload( &$file, $where='imports' ) {
		$upload_overrides = array( 'test_form' => false );

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
		}

		$this->set_uploads_path( $where );

		add_filter( 'upload_dir', array( $this, 'files_upload_dir' ) );
		$mfile = wp_handle_upload( $file, $upload_overrides );
		remove_filter( 'upload_dir', array( $this, 'files_upload_dir' ) );

		if ( isset( $mfile['error'] ) ) {

			if ( empty( $mfile['error'] ) ) {
				$mfile['error'] = _x( 'Could not upload file.', 'error', 'groundhogg' );
			}

			return new WP_Error( 'BAD_UPLOAD', $mfile['error'] );
		}

		return $mfile;
	}

}
