<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Contact;
use Groundhogg\Preferences;
use function Groundhogg\admin_page_url;
use function Groundhogg\count_csv_rows;
use function Groundhogg\files;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_db;
use function Groundhogg\get_items_from_csv;
use function Groundhogg\get_url_var;
use function Groundhogg\is_a_contact;
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Import_Contacts extends Bulk_Job {

	protected $field_map = [];
	protected $import_tags = [];
	protected $compliance = [];
	protected $file_path = '';
	const LIMIT = 500;

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
		$file_path = wp_normalize_path( files()->get_csv_imports_dir( $file_name ) );

		set_transient( 'gh_import_file_path', $file_path, DAY_IN_SECONDS );

		// -1 because headers
		$num_rows     = count_csv_rows( $file_path );
		$num_requests = floor( $num_rows / self::LIMIT );

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
	 * @throws \Exception
	 *
	 * @param $item mixed
	 *
	 * @return void
	 */
	protected function process_item( $item ) {

		$offset = absint( $item ) * self::LIMIT;

		$items = get_items_from_csv( $this->file_path, self::LIMIT, $offset );

		foreach ( $items as $item ) {

			if ( isset_not_empty( $this->compliance, 'is_confirmed' ) ) {
				$item['optin_status'] = Preferences::CONFIRMED;
			}

			if ( isset_not_empty( $this->compliance, 'gdpr_consent' ) ) {
				$item['gdpr_consent'] = 'yes';
			}

			if ( isset_not_empty( $this->compliance, 'marketing_consent' ) ) {
				$item['marketing_consent'] = 'yes';
			}

			$contact = generate_contact_with_map( $item, $this->field_map, [
				'type' => 'import',
				'name' => basename( $this->file_path ),
			] );

			if ( is_a_contact( $contact ) ) {

				$contact->apply_tag( $this->import_tags );

				/**
				 * Whenever a contact is imported
				 *
				 * @param $contact Contact
				 */
				do_action( 'groundhogg/contact/imported', $contact );

				$this->_completed();

			} else {

				$this->_skipped();
			}
		}

	}


	protected function get_log_message( $completed, $time, $skipped = 0 ) {

		if ( $skipped > 0 ) {
			return sprintf( 'Imported %s contacts in %s seconds. Skipped %d rows.',
				$completed,
				$time,
				$skipped,
			);
		}

		return sprintf( 'Imported %s contacts in %s seconds.',
			$completed,
			$time,
		);
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
		$this->file_path   = get_transient( 'gh_import_file_path' );

		if ( isset_not_empty( $this->compliance, 'is_confirmed' ) ) {
			$this->field_map['optin_status'] = 'optin_status';
		}

		if ( isset_not_empty( $this->compliance, 'gdpr_consent' ) ) {
			$this->field_map['gdpr_consent'] = 'gdpr_consent';
		}

		if ( isset_not_empty( $this->compliance, 'marketing_consent' ) ) {
			$this->field_map['marketing_consent'] = 'marketing_consent';
		}

		do_action( 'groundhogg/import_contacts/pre_loop' );
	}

	/**
	 * do stuff after the loop
	 *
	 * @return void
	 */
	protected function post_loop() {
		do_action( 'groundhogg/import_contacts/post_loop' );
	}

	/**
	 * Cleanup any options/transients/notices after the bulk job has been processed.
	 *
	 * @return void
	 */
	protected function clean_up() {
		delete_transient( 'gh_import_map' );
		delete_transient( 'gh_import_tags' );
		delete_transient( 'gh_import_compliance' );
		delete_transient( 'gh_import_file_path' );

		do_action( 'groundhogg/import_contacts/clean_up' );
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
