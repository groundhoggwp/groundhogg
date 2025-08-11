<?php

namespace Groundhogg\Classes\Traits;

use WP_Error;
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
	 * Return the list of allowed mimes for this object
	 *
	 * @return array
	 */
	public function get_allowed_mime_types() {

		$allowed_mimes = get_allowed_mime_types();

		return apply_filters( "groundhogg/{$this->get_object_type()}/allowed_mimes", $allowed_mimes );
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

		$folder = $this->get_uploads_folder_subdir() . DIRECTORY_SEPARATOR . $this->get_upload_folder_basename();
		$result = files()->safe_file_upload( $file, $this->get_allowed_mime_types(), $folder );

		return $result;
	}

	/**
	 * Copy a file given an uploaded URL
	 *
	 * @param string $url_or_path path or url of asset to upload
	 * @param bool $delete_tmp whether to delete the temp file after
	 *
	 * @return array|string[]|\WP_Error
	 */
	public function copy_file( $url_or_path, $delete_tmp = true ) {

		if ( ! function_exists( 'download_url' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
		}

		if ( ! is_copyable_file( $url_or_path ) ) {
			return new WP_Error( 'not_copyable', 'This file can\'t be uploaded.' );
		}

		$file = [
			'name'     => wp_basename( $url_or_path ),
			// Original filename on the user's system
			'tmp_name' => file_exists( $url_or_path ) ? $url_or_path : download_url( $url_or_path ),
			// Temporary filename on the server
		];

		$folder = $this->get_uploads_folder_subdir() . DIRECTORY_SEPARATOR . $this->get_upload_folder_basename();

		$result = files()->safe_file_sideload( $file, $this->get_allowed_mime_types(), $folder );

		if ( is_wp_error( $result ) ) {
			wp_delete_file( $file['tmp_name'] );
		}

		return $result;
	}


	/**
	 * Get the basename of the path
	 *
	 * @return string
	 */
	public function get_upload_folder_basename() {
		return md5( encrypt( $this->get_id() ) );
	}

	public function get_uploads_folder_subdir() {
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
				wp_delete_file( $file['path'] );
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
		if ( ! is_dir( $uploads_dir['path'] ) ) {
			wp_mkdir_p( $uploads_dir['path'] );
		}

		// Move any files to this object's uploads folder.
		foreach ( $other->get_files() as $file ) {

			$file_path = $file['path'];
			$file_name = $file['name'];

			files()->filesystem()->move( $file_path, $uploads_dir['path'] . '/' . $file_name );
		}

		return parent::merge( $other );
	}

}
