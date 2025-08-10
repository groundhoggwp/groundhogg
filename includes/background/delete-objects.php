<?php

namespace Groundhogg\background;

use Groundhogg\Contact_Query;
use Groundhogg\DB\Query\Table_Query;
use function Groundhogg\_nf;
use function Groundhogg\bold_it;
use function Groundhogg\db;
use function Groundhogg\notices;
use function Groundhogg\percentage;

class Delete_Objects extends Task {

	protected string $table;
	protected array $query;
	protected int $user_id;
	protected int $items;
	const BATCH_LIMIT = 100;

	public function __construct( string $table, array $query_args ) {
		$this->table   = $table;
		$this->query   = $query_args;
		$this->user_id = get_current_user_id();

		$query = new Table_Query( $table );
		$query->set_query_params( $query_args );
		$this->items = $query->count();
	}

	public function get_batches_remaining() {
		return floor( $this->count_remaining() / self::BATCH_LIMIT ) ;
	}

	/**
	 * Delete the contacts
	 *
	 * @return string
	 */
	public function get_title() {
		return sprintf( 'Delete %s items from %s', bold_it( _nf( $this->items ) ), $this->table );
	}

	public function count_remaining() {
		$query = new Table_Query( $this->table );
		$query->set_query_params( $this->query );
		return $query->count();
	}

	/**
	 * @throws \Groundhogg\DB\Query\FilterException
	 * @return float|int
	 */
	public function get_progress() {
		$query = new Table_Query( $this->table );
		$query->set_query_params( $this->query );

		return percentage( $this->items, $this->items - $this->count_remaining() );
	}

	/**
	 * Check if the user can delete these items
	 *
	 * @return bool
	 */
	public function can_run() {
		$table = $this->table;
		$type = db()->$table->get_object_type();
		// Don't run for empty queries maybe?
		return ! empty( $this->query ) && ! empty( $this->table ) && user_can( $this->user_id, "delete_{$type}s" );
	}

	/**
	 * Delete the contacts
	 *
	 * @return bool true if no more contacts, false otherwise
	 */
	public function process(): bool {

		$query = new Table_Query( $this->table );
		$query->set_query_params( $this->query )->setLimit( self::BATCH_LIMIT )->setFoundRows( false );

		$items = $query->get_objects();

		// No more contacts to delete
		if ( empty( $items ) ) {
			/* translators: %s: the number of items deleted */
			$message = sprintf( __( '%s items have been deleted!', 'groundhogg' ), bold_it( _nf( $this->items ) ) );
			notices()->add_user_notice( $message, 'success', true, $this->user_id );
			return true;
		}

		foreach ( $items as $item ) {
			$type = $item->_get_object_type();

			if ( ! user_can( $this->user_id, "delete_$type", $item ) ){
				continue;
			}

			$item->delete();
		}

		return false;
	}
}
