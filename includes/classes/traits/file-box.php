<?php

namespace Groundhogg\Classes\Traits;

use function Groundhogg\convert_to_local_time;
use function Groundhogg\encrypt;
use function Groundhogg\file_access_url;
use function Groundhogg\files;
use function Groundhogg\get_date_time_format;
use function Groundhogg\get_hostname;
use function Groundhogg\is_copyable_file;
use function Groundhogg\isset_not_empty;

trait File_Box {

	protected $upload_paths;

	/**
	 * @no_access Do not access
	 *
	 * @param $dirs
	 *
	 * @return mixed
	 */
	public function map_upload( $dirs ) {
		$dirs['path']   = $this->upload_paths['path'];
		$dirs['url']    = $this->upload_paths['url'];
		$dirs['subdir'] = $this->upload_paths['subdir'];

		return $dirs;
	}

	/**
	 * Upload a file
	 *
	 * Usage: $contact->upload_file( $_FILES[ 'file_name' ] )
	 *
	 * @param $file
	 *
	 * @return array|\WP_Error
	 */
	public function upload_file( &$file ) {

		if ( ! isset_not_empty( $file, 'name' ) ) {
			return new \WP_Error( 'invalid_file_name', __( 'Invalid file name.', 'groundhogg' ) );
		}

		$file['name'] = sanitize_file_name( $file['name'] );

		$upload_overrides = array( 'test_form' => false );

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
		}

		$this->get_uploads_folder();
		add_filter( 'upload_dir', [ $this, 'map_upload' ] );
		$mfile = wp_handle_upload( $file, $upload_overrides );
		remove_filter( 'upload_dir', [ $this, 'map_upload' ] );

		if ( isset_not_empty( $mfile, 'error' ) ) {
			return new \WP_Error( 'bad_upload', __( 'Unable to upload file.', 'groundhogg' ) );
		}

		return $mfile;
	}

	/**
	 * Copy a file given an uploaded URL
	 *
	 * @param string $url        url of the file to copy
	 * @param bool   $delete_tmp whether to delete the tgemp file after
	 *
	 * @return bool
	 */
	public function copy_file( $url_or_path, $delete_tmp = true ) {

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
		}

		// File cannot be copied
		if ( ! is_copyable_file( $url_or_path ) ) {
			return false;
		}

		// path given
		if ( file_exists( $url_or_path ) ) {
			$tmp_file = $url_or_path;
			// if using path, force false
			$delete_tmp = false;
		} else {

			// We should only be doing this for files which are already uploaded to the server,
			// We should reject urls for sites that aren't this one
			if ( get_hostname( $url_or_path ) !== get_hostname() ) {
				return false;
			}

			add_filter( 'https_local_ssl_verify', '__return_false' );
			add_filter( 'https_ssl_verify', '__return_false' );

			$tmp_file = download_url( $url_or_path, 300, false );

			if ( is_wp_error( $tmp_file ) ) {
				return false;
			}
		}

		if ( ! is_dir( $this->get_uploads_folder() ['path'] ) ) {
			mkdir( $this->get_uploads_folder() ['path'] );
		}
		try {
			copy( $tmp_file, $this->get_uploads_folder()['path'] . '/' . basename( $url_or_path ) );
		} catch ( \Exception $e ) {
		}

		if ( $delete_tmp ) {
			@unlink( $tmp_file );
		}

		return true;
	}


	/**
	 * Get the basename of the path
	 *
	 * @return string
	 */
	public function get_upload_folder_basename() {
		return md5( encrypt( $this->get_id() ) );
	}

	public function get_uploads_folder_subdir(){
		return $this->get_object_type() . 's';
	}

	/**
	 * get the upload folder for this contact
	 */
	public function get_uploads_folder() {
		$paths = [
			'subdir' => sprintf( '/groundhogg/%s/%s', $this->get_uploads_folder_subdir(), $this->get_upload_folder_basename() ),
			'path'   => files()->get_uploads_dir( $this->get_uploads_folder_subdir(), $this->get_upload_folder_basename() ),
			'url'    => files()->get_uploads_url( $this->get_uploads_folder_subdir(), $this->get_upload_folder_basename() )
		];

		$this->upload_paths = $paths;

		return $paths;
	}

	/**
	 * Get a list of associated files.
	 */
	public function get_files() {
		$data = [];

		$uploads_dir = $this->get_uploads_folder();

		if ( file_exists( $uploads_dir['path'] ) ) {

			$scanned_directory = array_diff( scandir( $uploads_dir['path'] ), [ '..', '.' ] );

			foreach ( $scanned_directory as $filename ) {
				$filepath = $uploads_dir['path'] . '/' . $filename;
				$file     = [
					'name'          => $filename,
					'path'          => $filepath,
					'url'           => file_access_url( sprintf( '%s/%s/%s', $this->get_uploads_folder_subdir(), $this->get_upload_folder_basename(), $filename ) ),
					'date_modified' => date_i18n( get_date_time_format(), convert_to_local_time( filectime( $filepath ) ) ),
				];

				// For capabilities
				if ( current_user_can( 'view_' . $this->get_object_type(), $this ) ) {
					$file['url'] = add_query_arg( 'id', $this->ID, $file['url'] );
				}

				$data[] = $file;

			}
		}

		return $data;
	}

	/**
	 * Delete a file
	 *
	 * @param $file_name string
	 */
	public function delete_file( $file_name ) {
		$file_name = basename( $file_name );
		foreach ( $this->get_files() as $file ) {
			if ( $file_name === $file['name'] ) {
				unlink( $file['path'] );
			}
		}
	}

	/**
	 * Handles moving files to the new object
	 *
	 * @param $other Base_Object
	 *
	 * @return bool
	 */
	public function merge( $other ) {

		// Dont merge with itself
		// Dont merge with objects of a different type
		if ( $other->get_id() === $this->get_id() || $other->get_object_type() !== $this->get_object_type() ) {
			return false;
		}

		$uploads_dir = $this->get_uploads_folder();

		// Might have to create the directory
		if ( ! is_dir( $uploads_dir['path'] ) ){
			wp_mkdir_p( $uploads_dir['path'] );
		}

		// Move any files to this object's uploads folder.
		foreach ( $other->get_files() as $file ) {

			$file_path = $file['path'];
			$file_name = $file['name'];

			rename( $file_path, $uploads_dir['path'] . '/' . $file_name );
		}

		return parent::merge( $other );
	}

}
