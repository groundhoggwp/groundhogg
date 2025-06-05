<?php

namespace Groundhogg\background;

use Groundhogg\Contact_Query;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\get_array_var;
use function Groundhogg\notices;

class Update_Contacts_Last_Id extends Update_Contacts {

	protected int $last_id;

	public function __construct( array $query, array $data ) {

		unset( $query['order'] );
		unset( $query['orderby'] );

		$this->last_id = 0;
		parent::__construct( $query, $data );
	}

	public function process(): bool {

		$query = new Contact_Query( $this->query );
		$query->setFoundRows( false )
		      ->setLimit( self::BATCH_LIMIT )
		      ->setOrderby( [ 'ID', 'ASC' ] )
		      ->where()->greaterThan( 'ID', $this->last_id );

		$contacts = $query->query( null, true );

		// No more contacts to add to update
		if ( empty( $contacts ) ) {
			$this->batch --;

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

			$this->last_id = $contact->ID;

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
