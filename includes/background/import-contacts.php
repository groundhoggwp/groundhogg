<?php

namespace Groundhogg\Background;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\Preferences;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\code_it;
use function Groundhogg\contact_filters_link;
use function Groundhogg\count_csv_rows;
use function Groundhogg\files;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_array_var;
use function Groundhogg\get_csv_delimiter;
use function Groundhogg\is_a_contact;
use function Groundhogg\isset_not_empty;
use function Groundhogg\notices;
use function Groundhogg\percentage;
use function Groundhogg\track_activity;
use function Groundhogg\white_labeled_name;

class Import_Contacts extends Task {

	protected int $batch;
	protected int $user_id;
	protected string $fileName;
	protected string $filePath;
	protected array $settings;
	protected string $delimiter;
	protected array $headers;
	protected int $rows = 0;

	protected ?\SplFileObject $file;

	const BATCH_LIMIT = 100;

	public function __construct( string $file, array $settings, int $batch = 0 ) {
		$this->fileName = $file;
		$this->settings = $settings;
		$this->user_id  = get_current_user_id();
		$this->batch    = $batch;
	}

	public function get_rows(){
		if ( ! $this->rows ){
			$this->rows = count_csv_rows( wp_normalize_path( files()->get_csv_imports_dir( $this->fileName ) ) );
		}

		return $this->rows;
	}

	public function get_progress() {
		return percentage( $this->get_rows(), $this->batch * self::BATCH_LIMIT );
	}

	/**
	 * Title of the task
	 *
	 * @return string
	 */
	public function get_title(){
		return sprintf( 'Import %s rows from %s', bold_it( _nf( $this->get_rows() ) ), bold_it( $this->fileName ) );
	}

	/**
	 * Only runs once at the beginning of the task
	 *
	 * @return bool
	 */
	public function can_run() {
		$this->filePath = wp_normalize_path( files()->get_csv_imports_dir( $this->fileName ) );

		if ( ! file_exists( $this->filePath ) ) {
			return false;
		}

		$this->advance();

		return user_can( $this->user_id, 'import_contacts' );
	}

	/**
	 * Advance the file to where we need to be
	 *
	 * Seek does not work with cells that contain "\n" !!!!
	 *
	 * @return void
	 */
	protected function advance() {

		$this->delimiter = get_csv_delimiter( $this->filePath ) ?: ',';
		$this->file      = new \SplFileObject( $this->filePath, 'r' );

		// Advance past the headers
		$this->headers = $this->file->fgetcsv( $this->delimiter );
		$offset        = $this->batch * self::BATCH_LIMIT;

		if ( $offset === 0 ) {
			return;
		}

		while ( ! $this->file->eof() && $offset > 0 ) {
			// Advance row
			$this->file->fgets();
			$offset --;
		}
	}

	public function stop() {
		$this->file = null;
	}

	public function __serialize(): array {
		return [
			'fileName' => $this->fileName,
			'batch'    => $this->batch,
			'user_id'  => $this->user_id,
			'settings' => $this->settings,
		];
	}

	/**
	 * Get items from the csv
	 *
	 * @return array
	 */
	protected function get_items_from_csv() {

		$data         = [];
		$header_count = count( $this->headers );

		while ( ! $this->file->eof() && count( $data ) < self::BATCH_LIMIT ) {

			$row = $this->file->fgetcsv( $this->delimiter );

			if ( count( $row ) > $header_count ) {
				$row = array_slice( $row, 0, $header_count );
			} else if ( count( $row ) < $header_count ) {
				$row = array_pad( $row, $header_count, '' );
			}

			$data[] = array_combine( $this->headers, $row );
		}

		return $data;

	}

	/**
	 * Process the items
	 *
	 * @return bool
	 */
	public function process(): bool {

		$items = $this->get_items_from_csv();
		$map   = get_array_var( $this->settings, 'field_map' );
		$tags  = get_array_var( $this->settings, 'tags' );

		if ( empty( $items ) ) {

			$filters = [
				[
					[
						'type'     => 'tags',
						'compare'  => 'includes',
						'compare2' => 'all',
						'tags'     => $tags
					]
				]
			];

			$query = new Contact_Query( [
				'filters' => $filters
			] );

			$contacts_link = contact_filters_link( _nf( $query->count() ), $filters );

			$message = sprintf( __( '%s contacts have been imported from %s!', 'groundhogg' ), bold_it( $contacts_link ), code_it( $this->fileName ) );

			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			$subject = sprintf( __( '[%s] Contacts imported!' ), white_labeled_name() );

			wp_mail( get_userdata( $this->user_id )->user_email, $subject, wpautop( $message ), [
				'Content-Type: text/html'
			] );

			return true;
		}

		foreach ( $items as $item ) {

			if ( isset_not_empty( $this->settings, 'is_confirmed' ) ) {
				$item['optin_status'] = Preferences::CONFIRMED;
				$map['optin_status']  = 'optin_status';
			}

			if ( isset_not_empty( $this->settings, 'gdpr_consent' ) ) {
				$item['gdpr_consent'] = 'yes';
				$map['gdpr_consent']  = 'gdpr_consent';
			}

			if ( isset_not_empty( $this->settings, 'marketing_consent' ) ) {
				$item['marketing_consent'] = 'yes';
				$map['marketing_consent']  = 'marketing_consent';
			}

			try {
				$contact = generate_contact_with_map( $item, $map );
			} catch ( \Exception $e ) {
				continue;
			}

			if ( is_a_contact( $contact ) ) {

				$contact->apply_tag( $tags );

				track_activity( $contact, 'imported', [], [
					'file' => $this->fileName,
					'user' => $this->user_id,
				] );

				/**
				 * Whenever a contact is imported
				 * Should be performant!
				 *
				 * @param $contact Contact
				 */
				do_action( 'groundhogg/contact/imported', $contact );
			}
		}

		$this->batch ++;

		return false;
	}
}
