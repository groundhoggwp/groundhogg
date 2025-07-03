<?php

namespace Groundhogg\Api\V4;

use Groundhogg\Plugin;
use WP_REST_Request;
use WP_REST_Server;
use function Groundhogg\file_access_url;
use function Groundhogg\files;
use function Groundhogg\get_csv_file_info;

class Files_Api extends Base_Api {

	/**
	 * Register the relevant REST routes
	 *
	 * @return mixed
	 */
	public function register_routes() {

		register_rest_route( self::NAME_SPACE, "/files/imports", [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_imports' ],
				'permission_callback' => [ $this, 'create_imports_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_imports' ],
				'permission_callback' => [ $this, 'read_imports_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_imports' ],
				'permission_callback' => [ $this, 'delete_imports_permissions_callback' ]
			],
		] );

		register_rest_route( self::NAME_SPACE, "/files/exports", [
//			[
//				'methods'             => WP_REST_Server::CREATABLE,
//				'callback'            => [ $this, 'create_exports' ],
//				'permission_callback' => [ $this, 'create_exports_permissions_callback' ]
//			],
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read_exports' ],
				'permission_callback' => [ $this, 'read_exports_permissions_callback' ]
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_exports' ],
				'permission_callback' => [ $this, 'delete_exports_permissions_callback' ]
			],
		] );
	}

	/**
	 * Upload an import file to the server
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function create_imports( \WP_REST_Request $request ) {

		if ( empty( $_FILES ) ) {
			return self::ERROR_422();
		}

		$uploaded = [];

		foreach ( $_FILES as $FILE ) {

			$result = files()->safe_file_upload( $FILE, [
				'csv' => 'text/csv'
			], 'imports' );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$uploaded[] = get_csv_file_info( $result[ 'file' ] );
		}


		return self::SUCCESS_RESPONSE( [
			'items'       => $uploaded,
			'total_items' => count( $uploaded )
		] );
	}

	/**
	 * Read import files from API
	 *
	 * @return \WP_REST_Response
	 */
	public function read_imports( \WP_REST_Request $request ) {

		$data = [];

		if ( file_exists( files()->get_csv_imports_dir() ) ) {

			$scanned_directory = array_diff( scandir( files()->get_csv_imports_dir() ), [ '..', '.' ] );

			foreach ( $scanned_directory as $filename ) {
				$filepath = files()->get_csv_imports_dir( $filename );
				$data[] = get_csv_file_info( $filepath );
			}
		}

		$limit   = absint( $request->get_param( 'limit' ) ) ?: 25 ;
		$offset  = absint( $request->get_param( 'offset' ) ) ?: 0 ;

		return self::SUCCESS_RESPONSE( [
			'items'       => array_slice( $data ,$offset , $limit ),
			'total_items' => count( $data )
		] );
	}

	/**
	 * Delete any given imports
	 * expects filename only
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function delete_imports( \WP_REST_Request $request ) {

		$files_to_delete = $request->get_json_params();

		foreach ( $files_to_delete as $file ){
			unlink( files()->get_csv_imports_dir( $file ) );
		}

		return self::SUCCESS_RESPONSE();
	}

	/**
	 * List of export
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function read_exports(WP_REST_Request $request ) {
		$data = [];

		if ( file_exists( files()->get_csv_exports_dir() ) ) {

			$scanned_directory = array_diff( scandir( files()->get_csv_exports_dir() ), [ '..', '.' ] );

			foreach ( $scanned_directory as $filename ) {
				$filepath = files()->get_csv_exports_dir( $filename );
				$data[] = get_csv_file_info( $filepath );
			}
		}

		$limit   = absint( $request->get_param( 'limit' ) ) ?: 25 ;
		$offset  = absint( $request->get_param( 'offset' ) ) ?: 0 ;

		return self::SUCCESS_RESPONSE( [
			'items'       => array_slice($data,$offset , $limit ),
			'total_items' => count( $data )
		] );
	}

	/**
	 * Dete any exports from the server, expects file names only
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function delete_exports( \WP_REST_Request $request ) {
		$files_to_delete = $request->get_json_params();

		foreach ( $files_to_delete as $file ){
			unlink( files()->get_csv_exports_dir( $file ) );
		}

		return self::SUCCESS_RESPONSE();
	}

	public function create_imports_permissions_callback() {
		return current_user_can( 'import_contacts' );
	}

	public function read_imports_permissions_callback() {
		return current_user_can( 'import_contacts' );
	}

	public function delete_imports_permissions_callback() {
		return current_user_can( 'import_contacts' );
	}

	public function create_exports_permissions_callback() {
		return current_user_can( 'export_contacts' );
	}

	public function read_exports_permissions_callback() {
		return current_user_can( 'export_contacts' );
	}

	public function delete_exports_permissions_callback() {
		return current_user_can( 'export_contacts' );
	}


}