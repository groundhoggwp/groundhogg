<?php

namespace Groundhogg\Background;

use function Groundhogg\percentage;

class Iterate_Over_List extends Task {

	protected array $list;
	protected int $items; // count($list)
	protected mixed $callback;
	protected int $batchsize;
	protected int $batch = 0;
	protected string $display; // what's shown in the table itself

	public function __construct( string $display, array $list, callable $callback, int $batchsize = 100 ) {
		$this->list = $list;
		$this->items = count( $list );
		$this->callback = $callback;
		$this->batchsize = $batchsize;
		$this->display = $display;
	}

	public function can_run() {
		return true;
	}

	public function get_progress() {
		return percentage( $this->items, $this->batch * $this->batchsize );
	}

	public function get_title() {
		return $this->display;
	}

	public function get_batches_remaining() {
		return floor( $this->items / $this->batchsize ) - $this->batch;
	}

	public function process() {

		// take the batch size of items of the top of the list since there is no point in preserving them
		$items = array_splice( $this->list, 0, $this->batchsize );

		if ( empty( $items ) ) {
			return true;
		}

		// send the whole batch
		call_user_func( $this->callback, $items );

		$this->batch++;

		return false;
	}
}
