<?php

namespace Groundhogg\Background;

use Groundhogg\Contact_Query;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\get_array_var;
use function Groundhogg\notices;
use function Groundhogg\percentage;

class Update_Contacts extends Task {

	protected array $query;
	protected array $data;
	protected int $user_id;
	protected int $batch;
	protected int $contacts;

	const BATCH_LIMIT = 100;

	public function __construct( array $query, array $data ) {
		$this->query   = $query;
		$this->data    = $data;
		$this->user_id = get_current_user_id();

		$query          = new Contact_Query( $query );
		$this->contacts = $query->count();
		$this->batch    = floor( $this->contacts / self::BATCH_LIMIT );
	}

	public function get_title(){
		return sprintf( 'Update %s contacts', bold_it( _nf( $this->contacts ) ) );
	}

	public function get_progress(){
		$total_batches = floor( $this->contacts / self::BATCH_LIMIT );
		return percentage( $this->contacts, ( $total_batches - $this->batch ) * self::BATCH_LIMIT );
	}

	public function can_run() {
		return user_can( $this->user_id, 'edit_contacts' ) && $this->batch >= 0 && ! empty( $this->data );
	}

	public function process(): bool {
		$offset = $this->batch * self::BATCH_LIMIT;

		$query = new Contact_Query( array_merge( $this->query, [
			'offset'     => $offset,
			'limit'      => self::BATCH_LIMIT,
			'found_rows' => false,
		] ) );

		$contacts = $query->query( null, true );

		// No more contacts to add to update
		if ( empty( $contacts ) ) {
			$this->batch--;
			return false;
		}

		$data        = get_array_var( $this->data, 'data', [] );
		$meta        = get_array_var( $this->data, 'meta', [] );
		$add_tags    = get_array_var( $this->data, 'add_tags', [] );
		$remove_tags = get_array_var( $this->data, 'remove_tags', [] );

		unset( $data['email'] );
		unset( $data['user_id'] );

		// No changes to make really.
		if ( empty( $data ) && empty( $meta ) && empty( $add_tags ) && empty( $remove_tags ) ) {
			return true;
		}

		foreach ( $contacts as $contact ) {

			if ( ! user_can( $this->user_id, 'edit_contact', $contact ) ) {
				continue;
			}

			$contact->update( $data );

			// If the current object supports meta data...
			if ( ! empty( $meta ) && is_array( $meta ) ) {
				$contact->update_meta( $meta );
			}

			$contact->apply_tag( $add_tags );
			$contact->remove_tag( $remove_tags );
		}

		if ( $this->batch === 0 ) {

			$message = sprintf( __( '%s contacts have been updated!', 'groundhogg' ), bold_it( _nf( $this->contacts ) ) );
			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			return true;
		}

		$this->batch --;

		return false;
	}
}
