<?php

namespace Groundhogg\Bulk_Jobs;

use function Groundhogg\admin_page_url;
use function Groundhogg\get_array_var;
use function Groundhogg\get_items_from_csv;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\get_url_var;
use function Groundhogg\guided_setup_finished;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Import_Contacts_Exp extends Bulk_Job {

	protected $field_map = [];
	protected $import_tags = [];
	protected $records = [];
	protected $confirm_contacts = false;

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'gh_import_contacts';
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {
		if ( ! current_user_can( 'import_contacts' ) ) {
			return $items;
		}

		$file_name = sanitize_file_name( get_url_var( 'import' ) );

		set_transient( 'gh_import_file_name', $file_name, WEEK_IN_SECONDS );

		$file_path = wp_normalize_path( Plugin::$instance->utils->files->get_csv_imports_dir( $file_name ) );

		$items = get_items_from_csv( $file_path );

		$total_items = count( $items );

		$return = [];

		for ( $i = 0; $i < $total_items; $i ++ ) {
			$return[] = $i;
		}

		return $return;
	}

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 */
	protected function process_item( $item ) {
		$record  = get_array_var( $this->records, $item );
		$contact = \Groundhogg\generate_contact_with_map( $record, $this->field_map );

		if ( $contact ) {
			$contact->apply_tag( $this->import_tags );

			if ( $this->confirm_contacts ) {
				$contact->change_marketing_preference( Preferences::CONFIRMED );
			}
		}
	}

	/**
	 * Do stuff before the loop
	 *
	 * @return void
	 */
	protected function pre_loop() {
		$file_name = get_transient( 'gh_import_file_name' );
		$file_path = wp_normalize_path( Plugin::$instance->utils->files->get_csv_imports_dir( $file_name ) );

		$this->records = get_items_from_csv( $file_path );

		$this->field_map        = Plugin::$instance->settings->get_transient( 'gh_import_map' );
		$this->import_tags      = wp_parse_id_list( Plugin::$instance->settings->get_transient( 'gh_import_tags' ) );
		$this->confirm_contacts = Plugin::$instance->settings->get_transient( 'gh_import_confirm_contacts' );
	}

	/**
	 * do stuff after the loop
	 *
	 * @return void
	 */
	protected function post_loop() {
	}

	/**
	 * Cleanup any options/transients/notices after the bulk job has been processed.
	 *
	 * @return void
	 */
	protected function clean_up() {
		Plugin::$instance->settings->delete_transient( 'gh_import_map' );
		Plugin::$instance->settings->delete_transient( 'gh_import_tags' );
		Plugin::$instance->settings->delete_transient( 'gh_import_confirm_contacts' );
		Plugin::$instance->settings->delete_transient( 'gh_import_file_name' );
	}

	/**
	 * Get the return URL
	 *
	 * @return string
	 */
	protected function get_return_url() {
		$url = admin_page_url( 'gh_contacts' );

		// Return to guided setup if it's not yet complete.
		if ( ! guided_setup_finished() ) {
			$url = admin_page_url( 'gh_guided_setup', [ 'step' => 4 ] );
		}

		return $url;
	}
}