<?php

namespace Groundhogg\Background;

use Groundhogg\Contact_Query;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\notices;

class Add_Contacts_To_Funnel extends Complete_Benchmark {

	public function get_title(){
		return sprintf( 'Adding %s contacts to %s in %s', bold_it( _nf( $this->contacts ) ), bold_it( $this->step->get_title() ), bold_it( $this->step->get_funnel_title() ) );
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
			$message = sprintf( __( '%s contacts have been added to %s!', 'groundhogg' ), bold_it( _nf( $count ) ), bold_it( $this->step->get_funnel()->get_title() ) );
			notices()->add_user_notice( $message, 'success', true, $this->user_id );

			return true;
		}

		foreach ( $contacts as $contact ) {
			$this->step->enqueue( $contact );
		}

		$this->batch ++;

		return false;
	}
}
