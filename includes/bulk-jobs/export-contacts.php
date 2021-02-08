<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use function Groundhogg\encrypt;
use function Groundhogg\export_field;
use function Groundhogg\export_header_pretty_name;
use function Groundhogg\file_access_url;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_var;
use function Groundhogg\is_a_contact;
use function Groundhogg\key_to_words;
use function Groundhogg\multi_implode;
use Groundhogg\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Export_Contacts extends Bulk_Job {

	protected $fp;
	protected $file_name;
	protected $file_path;
	protected $headers;

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

		$contacts = $query->query( $args );
		$ids      = wp_list_pluck( $contacts, 'ID' );

		return $ids;
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
		return min( 500, intval( ini_get( 'max_input_vars' ) ) );
	}

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 */
	protected function process_item( $item ) {

		if ( ! current_user_can( 'export_contacts' ) ) {
			return;
		}

		$line = [];

		$contact = get_contactdata( absint( $item ) );

		if ( ! is_a_contact( $contact ) ) {
			return;
		}

		foreach ( $this->headers as $header ) {
			$line[] = export_field( $contact, $header );
		}

		fputcsv( $this->fp, $line );
	}

	/**
	 * Get the args for the job.
	 *
	 * @return void
	 */
	protected function pre_loop() {

		$headers     = get_transient( 'gh_export_headers' );
		$header_type = get_transient( 'gh_export_header_type' );

		$file_name = Plugin::$instance->settings->get_transient( 'gh_export_file' );

		$fp = false;

		if ( ! $file_name ) {

			// randomize the file path to prevent direct access.
			$file_name = sanitize_file_name( md5( encrypt( current_time( 'mysql' ) ) ) . '.csv' ); //todo

			// get the full path.
			$file_path = Plugin::$instance->utils->files->get_csv_exports_dir( $file_name, true );
			Plugin::$instance->settings->set_transient( 'gh_export_file', $file_name, HOUR_IN_SECONDS );

			//write the headers to the export.
			$fp = fopen( $file_path, "w" );

			if ( $header_type === 'pretty' ){
				$file_headers = array_map( function ( $header ){
					return export_header_pretty_name( $header );
				}, $headers );
			} else {
				$file_headers = $headers;
			}

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