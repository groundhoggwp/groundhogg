<?php

namespace Groundhogg\background;

use Groundhogg\Contact_Query;

class Complete_Benchmark_Last_Id extends Complete_Benchmark {

	protected int $last_id;

	/**
	 * @param int   $step_id
	 * @param array $query_args
	 * @param int   $batch
	 */
	public function __construct( int $step_id, array $query_args, int $batch, array $args = [] ) {
		$this->last_id = 0;

		unset( $query_args['order'] );
		unset( $query_args['orderby'] );

		parent::__construct( $step_id, $query_args, $batch, $args );
	}

	public function process(): bool {

		$query = new Contact_Query( $this->query );
		$query->setOrderby( [ 'ID', 'ASC' ] )
		      ->setFoundRows( false )
		      ->setLimit( self::BATCH_LIMIT )
		      ->where()->greaterThan( 'ID', $this->last_id );

		$contacts = $query->query( null, true );
		$args     = is_array( $this->args ) ? $this->args : [];

		// No more contacts to add to the funnel
		if ( empty( $contacts ) ) {
			return true;
		}

		foreach ( $contacts as $contact ) {
			$this->last_id = $contact->ID;
			$this->step->benchmark_enqueue( $contact, $args );
		}

		$this->batch ++;

		return false;
	}

	public function __serialize(): array {
		return [
			'step_id'  => $this->step_id,
			'query'    => $this->query,
			'batch'    => $this->batch,
			'user_id'  => $this->user_id,
			'contacts' => $this->contacts,
			'args'     => $this->args,
			'last_id'  => $this->last_id,
		];
	}
}
