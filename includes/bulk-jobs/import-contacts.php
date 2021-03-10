<?php

namespace Groundhogg\Bulk_Jobs;

use function Groundhogg\admin_page_url;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_db;
use function Groundhogg\get_items_from_csv;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\get_url_var;
use function Groundhogg\guided_setup_finished;
use function Groundhogg\is_a_contact;
use function Groundhogg\isset_not_empty;
use function Groundhogg\recount_tag_contacts_count;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Import_Contacts extends Bulk_Job {

	protected $field_map = [];
	protected $import_tags = [];
	protected $compliance = [];

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
		$file_path = wp_normalize_path( Plugin::$instance->utils->files->get_csv_imports_dir( $file_name ) );

		return get_items_from_csv( $file_path );
	}

	/**
	 * Get the maximum number of items which can be processed at a time.
	 *
	 * @param $max int
	 * @param $items array
	 *
	 * @return int
	 */
	public function max_items( $max, $items ) {
		$item   = array_shift( $items );
		$fields = count( array_keys( $item ) );

		$max       = intval( ini_get( 'max_input_vars' ) );
		$max_items = floor( $max / $fields );

		$max_override = absint( get_url_var( 'max_items' ) );

		if ( $max_override > 0 ) {
			return $max_override;
		}

		return min( $max_items, 100 );
	}

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function process_item( $item ) {

		if ( isset_not_empty( $this->compliance, 'is_confirmed' ) ) {
			$item['optin_status'] = Preferences::CONFIRMED;
		}

		if ( isset_not_empty( $this->compliance, 'gdpr_consent' ) ) {
			$item['gdpr_consent'] = 'yes';
		}

		if ( isset_not_empty( $this->compliance, 'marketing_consent' ) ) {
			$item['marketing_consent'] = 'yes';
		}

		$contact = generate_contact_with_map( $item, $this->field_map );

		if ( is_a_contact( $contact ) ) {
			$contact->apply_tag( $this->import_tags );
		}
	}

	/**
	 * Do stuff before the loop
	 *
	 * @return void
	 */
	protected function pre_loop() {
		$this->field_map   = get_transient( 'gh_import_map' );
		$this->import_tags = get_transient( 'gh_import_tags' );
		$this->compliance  = get_transient( 'gh_import_compliance' );

		if ( isset_not_empty( $this->compliance, 'is_confirmed' ) ) {
			$this->field_map['optin_status'] = 'optin_status';
		}

		if ( isset_not_empty( $this->compliance, 'gdpr_consent' ) ) {
			$this->field_map['gdpr_consent'] = 'gdpr_consent';
		}

		if ( isset_not_empty( $this->compliance, 'marketing_consent' ) ) {
			$this->field_map['marketing_consent'] = 'marketing_consent';
		}

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
		Plugin::$instance->settings->delete_transient( 'gh_import_compliance' );

		recount_tag_contacts_count();
	}

	/**
	 * Display the total import count.
	 *
	 * @return string
	 */
	protected function get_finished_notice() {

		$total_contacts_imported = get_db( 'contacts' )->count( [
			'tags_include'           => $this->import_tags,
			'tags_include_needs_all' => 1,
		] );

		return sprintf( _n( '%s contact imported!', '%s contacts imported!', $total_contacts_imported, 'groundhogg' ), number_format_i18n( $total_contacts_imported ) );
	}

	/**
	 * Get the return URL
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_page_url( 'gh_contacts', [
			'tags_include'           => $this->import_tags,
			'tags_include_needs_all' => 1,
		] );
	}
}