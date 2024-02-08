<?php

namespace Groundhogg\background;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use Groundhogg\Preferences;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\code_it;
use function Groundhogg\contact_filters_link;
use function Groundhogg\files;
use function Groundhogg\generate_contact_with_map;
use function Groundhogg\get_array_var;
use function Groundhogg\get_items_from_csv;
use function Groundhogg\is_a_contact;
use function Groundhogg\isset_not_empty;
use function Groundhogg\notices;
use function Groundhogg\white_labeled_name;

class Import_Contacts extends Task {

	protected int $batch;
	protected int $user_id;
	protected string $fileName;
	protected string $filePath;
	protected array $settings;

	protected \SplFileObject $file;

	const BATCH_LIMIT = 100;

	public function __construct( string $file, array $settings, int $batch = 0 ) {
		$this->fileName = $file;
		$this->settings = $settings;
		$this->user_id  = get_current_user_id();
		$this->batch    = $batch;
	}

	/**
	 * @return bool
	 */
	public function can_run() {
		$this->filePath = wp_normalize_path( files()->get_csv_imports_dir( $this->fileName ) );

		return file_exists( $this->filePath ) && user_can( $this->user_id, 'import_contacts' );
	}

	/**
	 * @return bool
	 */
	public function process(): bool {

		$offset = $this->batch * self::BATCH_LIMIT;


		$items = get_items_from_csv( $this->filePath, self::BATCH_LIMIT, $offset );

		$map  = get_array_var( $this->settings, 'field_map' );
		$tags = get_array_var( $this->settings, 'tags' );

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
