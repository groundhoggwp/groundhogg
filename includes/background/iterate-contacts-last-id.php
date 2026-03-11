<?php

namespace Groundhogg\Background;

use Groundhogg\Contact_Query;
use function Groundhogg\_nf;
use function Groundhogg\percentage;

class Iterate_Contacts_Last_Id extends Task {

	protected array $query;
	protected int $last_id;
	protected int $items;
	protected int $batch;
	protected int $batchsize;
	protected mixed $callback;
	protected string $display;

	public function __construct(
		array $query,
		callable $callback,
		string $display,
		int $batchsize = 100
	) {

		unset( $query['order'] );
		unset( $query['orderby'] );

		if ( ! is_callable( $callback ) ) {
			throw new \InvalidArgumentException( 'Callback must be callable' );
		}

		$this->callback = $callback;
		$this->query    = $query;
		$this->display  = $display;
		$this->batchsize = $batchsize;
		$this->items    = ( new Contact_Query( $this->query ) )->count();
	}

	public function can_run() {
		return true;
	}

	/**
	 * Title of the task
	 *
	 * @return string
	 */
	public function get_title() {
		return sprintf( $this->display, _nf( $this->items ) );
	}

	public function process() {

		$query = new Contact_Query( $this->query );
		$query->setOrderby( [ 'ID', 'ASC' ] )
		      ->setFoundRows( false )
		      ->setLimit( $this->batchsize );
		$query->where()->greaterThan( 'ID', $this->last_id );
		$contacts = $query->query( null, true );

		if ( empty( $contacts ) ) {
			return true;
		}

		foreach ( $contacts as $contact ) {
			$this->last_id = $contact->ID;

			call_user_func( $this->callback, $contact );
		}

		$this->batch ++;

		return false;
	}

	public function get_progress() {
		return percentage( $this->items, $this->batch * $this->batchsize  );
	}

	public function get_batches_remaining() {
		return floor( $this->items / $this->batchsize  ) - $this->batch;
	}
}
