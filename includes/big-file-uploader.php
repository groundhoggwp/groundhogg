<?php

namespace Groundhogg;

class Big_File_Uploader {

	public function __construct() {
		add_action( 'wp_ajax_gh_pre_big_file_upload', [ $this, 'pre_ajax_upload' ] );
		add_action( 'wp_ajax_gh_big_file_upload_success', [ $this, 'ajax_upload_success' ] );
		add_action( 'wp_ajax_gh_big_file_upload', [ $this, 'ajax_upload_file' ] );
	}

	/**
	 * Decodes a base64 encoded chunk of data
	 *
	 * @param $data
	 *
	 * @return array|bool|false|string
	 */
	public function decode_chunk( $data ) {
		$data = explode( ';base64,', $data );

		if ( ! is_array( $data ) || ! isset( $data[1] ) ) {
			return false;
		}

		$data = base64_decode( $data[1] );
		if ( ! $data ) {
			return false;
		}

		return $data;
	}

	/**
	 * Gets the correct file path
	 *
	 * @return false|string
	 */
	public function get_file_path() {

		$location  = get_post_var( 'location', '' );
		$file_name = sanitize_file_name( get_post_var( 'file_name' ) );

		$allowed_types = [
			'csv' => 'text/csv'
		];

		$allowed_types = apply_filters( 'groundhogg/big_file_upload/allowed_types', $allowed_types, $location );
		$validate      = wp_check_filetype( $file_name, $allowed_types );

		if ( $validate['type'] === false ) {
			wp_send_json_error( new \WP_Error( 'invalid_file_type', 'File type is not allowed.' ) );

			return false;
		}

		$file_name = $validate['proper_filename'];

		switch ( $location ) {
			case 'imports':
				$file_path = files()->get_csv_imports_dir( $file_name, true );
				break;
			default:
				$file_path = files()->get_uploads_dir( '', $file_name, true );
				break;
		}

		return apply_filters( 'groundhogg/big_file_upload/file_path', $file_path, $location );
	}

	/**
	 * Rename the file in case it already exists
	 *
	 * @param $file_path
	 * @param $int
	 *
	 * @return array|string|string[]
	 */
	public function rename_file( $file_path, $int = 1 ) {

		$validate = wp_check_filetype( basename( $file_path ) );

		$ext = $validate['ext'];

		$file_path = preg_replace( "/-\d+\.{$ext}/", "-{$int}.{$ext}", $file_path );

		if ( file_exists( $file_path ) ) {
			return $this->rename_file( $file_path, $int + 1 );
		}

		return $file_path;
	}

	/**
	 * Do actions before uploading a new backup file!
	 */
	public function pre_ajax_upload() {
		// Nonce check
		// Logged in, permissions check
		if ( ! wp_verify_nonce( get_post_var( 'nonce' ), 'admin_ajax' ) || ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error();
		}

		$file_path = $this->get_file_path();

		// Delete the old backup file if one exists already
		if ( file_exists( $file_path ) ) {
			$file_path = $this->rename_file( $file_path );
		}

		$file_name = basename( $file_path );

		wp_send_json_success( [
			'file_name' => $file_name,
			'file_path' => $file_path
		] );
	}

	/**
	 * Upload the file chunck via an ajx request to bypass the upload_max_filesize limit.
	 */
	public function ajax_upload_file() {
		// Nonce check
		// Logged in, permissions check
		if ( ! wp_verify_nonce( get_post_var( 'nonce' ), 'admin_ajax' ) || ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error();
		}

		$file_path = $this->get_file_path();

		$file_data = $this->decode_chunk( $_POST['file_data'] );

		if ( false === $file_data ) {
			wp_send_json_error();
		}

		file_put_contents( $file_path, $file_data, FILE_APPEND );

		wp_send_json_success();
	}

	/**
	 * When the file is done uploading, set the transients and get the start URL of the jib.
	 */
	public function ajax_upload_success() {
		// Nonce check
		// Logged in, permissions check
		if ( ! wp_verify_nonce( get_post_var( 'nonce' ), 'admin_ajax' ) || ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error();
		}

		$location  = get_post_var( 'location' );
		$file_path = $this->get_file_path();

		switch ( $location ) {
			case 'imports';
				$url = admin_page_url( 'gh_tools', [
					'action' => 'map',
					'tab'    => 'import',
					'import' => basename( $file_path ),
				] );
				break;
		}

		wp_send_json_success( [ 'return_url' => $url ] );
	}
}
