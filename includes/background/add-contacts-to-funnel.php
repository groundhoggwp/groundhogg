<?php

namespace Groundhogg\Background;

use Groundhogg\Contact_Query;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\notices;

class Add_Contacts_To_Funnel extends Complete_Benchmark {

	public function get_title() {
		/* translators: 1: number of contacts, 2: step title, 3: funnel title */
		return sprintf( esc_html__( 'Adding %1$s contacts to %2$s in %3$s!', 'groundhogg' ), bold_it( _nf( $this->contacts ) ), bold_it( $this->step->get_title() ), bold_it( $this->step->get_funnel_title() ) );
	}

	public function process(): bool {

		$offset = $this->batch * self::BATCH_LIMIT;

		$query = new Contact_Query( array_merge( $this->query, [
			'offset'     => $offset,
			'limit'      => self::BATCH_LIMIT,
			'found_rows' => true,
		] ) );

		$contacts = $query->query( null, true );

		// No more contacts to add to the funnel
		if ( empty( $contacts ) ) {

			$count   = $query->get_found_rows();
			/* translators: 1: number of contacts, 2: funnel title */
			$message = sprintf( esc_html__( '%1$s contacts have been added to %2$s!', 'groundhogg' ), bold_it( _nf( $count ) ), bold_it( $this->step->get_funnel()->get_title() ) );
			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			return true;
		}

		$args = is_array( $this->args ) ? $this->args : [];

		foreach ( $contacts as $contact ) {
			$this->step->enqueue( $contact, true, $args );
		}

		$this->batch ++;

		return false;
	}
}
