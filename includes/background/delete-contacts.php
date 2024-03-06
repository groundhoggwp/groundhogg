<?php

namespace Groundhogg\Background;

use Groundhogg\Contact_Query;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\notices;
use function Groundhogg\percentage;

class Delete_Contacts extends Task {

	protected array $query;
	protected int $user_id;
	protected int $contacts;
	const BATCH_LIMIT = 100;

	public function __construct( array $query ) {
		$this->query = $query;
		$this->user_id = get_current_user_id();

		$query          = new Contact_Query( $query );
		$this->contacts = $query->count();
	}

	/**
	 * Delete the contacts
	 *
	 * @return string
	 */
	public function get_title() {
		return sprintf( 'Delete %s contacts', bold_it( _nf( $this->contacts ) ) );
	}

	public function get_progress() {

		$query = new Contact_Query( $this->query );
		$left  = $query->count();

		return percentage( $this->contacts, $this->contacts - $left );
	}

	public function can_run() {
		// Don't run for empty queries maybe?
		return ! empty( $this->query ) && user_can( $this->user_id, 'delete_contacts' );
	}

	/**
	 * Delete the contacts
	 *
	 * @return bool true if no more contacts, false otherwise
	 */
	public function process(): bool {

		$query = new Contact_Query( array_merge( $this->query, [
			'limit'      => self::BATCH_LIMIT,
			'found_rows' => false,
		] ) );

		$contacts = $query->query( null, true );

		// No more contacts to delete
		if ( empty( $contacts ) ) {
			$message = sprintf( __( '%s contacts have been deleted!', 'groundhogg' ), bold_it( _nf( $this->contacts ) ) );
			notices()->add_user_notice( $message, 'success', true, $this->user_id );
			return true;
		}

		foreach ( $contacts as $contact ) {

			if ( ! user_can( $this->user_id, 'delete_contact', $contact ) ){
				continue;
			}

			$contact->delete();
		}

		return false;
	}
}
