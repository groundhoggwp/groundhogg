<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Contact_Query;
use Groundhogg\Plugin;
use function Groundhogg\encrypt;
use function Groundhogg\export_field;
use function Groundhogg\export_header_pretty_name;
use function Groundhogg\file_access_url;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_var;
use function Groundhogg\is_a_contact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Export_Contacts extends Bulk_Job {

	protected $fp;
	protected $file_name;
	protected $file_path;
	protected $headers;
	protected $query;

	const LIMIT = 500;

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'gh_export_contacts';
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {
		if ( ! current_user_can( 'export_contacts' ) ) {
			return $items;
		}

		$query = new Contact_Query();
		$args  = get_request_var( 'query' );

		// Backwards compat
		if ( empty( $args ) ) {
			$args = get_request_query();
		}

		$num_contacts = $query->count( $args );

		$num_requests = floor( $num_contacts / self::LIMIT );

		return range( 0, $num_requests );
	}

	/**
	 * Get the maximum number of items which can be processed at a time.
	 *
	 * @param $max   int
	 * @param $items array
	 *
	 * @return int
	 */
	public function max_items( $max, $items ) {
		return 1;
	}

	/**
	 * Process an item
	 *
	 * @param $batch int
	 *
	 * @return void
	 */
	protected function process_item( $batch ) {

		if ( ! current_user_can( 'export_contacts' ) ) {
			return;
		}

		$query_args = array_merge( [
			'limit'  => self::LIMIT,
			'offset' => $batch * self::LIMIT,
		], $this->query );

		$query    = new Contact_Query( $query_args );
		$contacts = $query->query( null, true );

		foreach ( $contacts as $contact ) {
			$line = [];

			if ( ! is_a_contact( $contact ) ) {
				return;
			}

			foreach ( $this->headers as $header ) {
				$line[] = export_field( $contact, $header );
			}

			fputcsv( $this->fp, $line );

			$this->_completed();
		}
	}

	/**
	 * Get the args for the job.
	 *
	 * @return void
	 */
	protected function pre_loop() {

		$headers     = get_transient( 'gh_export_headers' );
		$query       = get_transient( 'gh_export_query' ) ?: [];
		$header_type = get_transient( 'gh_export_header_type' );
		$file_name   = get_transient( 'gh_export_file' );

		$fp = false;

		if ( ! $file_name ) {

			// randomize the file path to prevent direct access.
			$file_name = sanitize_file_name( md5( encrypt( current_time( 'mysql' ) ) ) . '.csv' ); //todo

			// get the full path.
			$file_path = Plugin::$instance->utils->files->get_csv_exports_dir( $file_name, true );
			Plugin::$instance->settings->set_transient( 'gh_export_file', $file_name, HOUR_IN_SECONDS );

			//write the headers to the export.
			$fp = fopen( $file_path, "w" );

			$file_headers = array_map( function ( $header ) use ( $header_type ) {
				return export_header_pretty_name( $header, $header_type );
			}, $headers );

			fputcsv( $fp, $file_headers );
		}

		// If we have the file name then open the file before we move on.
		if ( ! $fp ) {
			$file_path = Plugin::$instance->utils->files->get_csv_exports_dir( $file_name, true );
			$fp        = fopen( $file_path, "a" );
		}

		$this->fp        = $fp;
		$this->file_name = $file_name;
		$this->headers   = $headers;
		$this->query     = $query;
	}

	/**
	 * do stuff after the loop
	 *
	 * @return void
	 */
	protected function post_loop() {
		fclose( $this->fp );
	}

	/**
	 * Cleanup any options/transients/notices after the bulk job has been processed.
	 *
	 * @return void
	 */
	protected function clean_up() {
		Plugin::$instance->settings->delete_transient( 'gh_export_file' );
	}

	/**
	 * Get the return URL
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_url( 'admin.php?page=gh_contacts' );
	}

	/**
	 * Get the download link.
	 *
	 * @return string
	 */
	protected function get_finished_notice() {
		$file_url = file_access_url( '/exports/' . $this->file_name, true );

		return sprintf( _x( 'Export file created. %s', 'notice', 'groundhogg' ), "&nbsp;&nbsp;&nbsp;<a class='button button-primary' href='$file_url'>Download Now</a>" );
	}
}
