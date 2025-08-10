<?php

namespace Groundhogg\background;

use Groundhogg\Contact_Query;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\notices;

class Add_Contacts_To_Funnel_Last_Id extends Complete_Benchmark_Last_Id {

	public function get_title() {
		/* translators: 1: number of contacts, 2: step title, 3: funnel title */
		return sprintf( esc_html__( 'Adding %1$s contacts to %2$s in %3$s!', 'groundhogg' ), bold_it( _nf( $this->contacts ) ), bold_it( $this->step->get_title() ), bold_it( $this->step->get_funnel_title() ) );
	}

	public function process(): bool {

		$query = new Contact_Query( $this->query );
		$query->setOrderby( [ 'ID', 'ASC' ] )
		      ->setFoundRows( false )
		      ->setLimit( self::BATCH_LIMIT )
		      ->where()->greaterThan( 'ID', $this->last_id );

		$contacts = $query->query( null, true );

		// No more contacts to add to the funnel
		if ( empty( $contacts ) ) {

			/* translators: 1: number of contacts, 2: funnel title */
			$message = sprintf( esc_html__( '%1$s contacts have been added to %2$s!', 'groundhogg' ), bold_it( _nf( $this->contacts ) ), bold_it( $this->step->get_funnel()->get_title() ) );
			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			return true;
		}

		$args = is_array( $this->args ) ? $this->args : [];

		foreach ( $contacts as $contact ) {
			$this->last_id = $contact->ID;
			$this->step->enqueue( $contact, true, $args );
		}

		$this->batch ++;

		return false;
	}
}
