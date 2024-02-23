<?php

namespace Groundhogg\Background;

use Groundhogg\Broadcast;
use function Groundhogg\bold_it;
use function Groundhogg\notices;

class Schedule_Broadcast extends Task {

	protected Broadcast $broadcast;
	protected int $broadcast_id;

	public function __construct( int $broadcast_id ) {
		$this->broadcast_id = $broadcast_id;
	}

	public function get_title(){
		return sprintf( 'Schedule broadcast %s', bold_it( $this->broadcast->get_title() ) );
	}

	public function can_run() {
		return $this->broadcast->exists() && $this->broadcast->is_pending();
	}

	/**
	 * @return bool true when broadcast is fully scheduled
	 */
	public function process(): bool {
		$this->broadcast->enqueue_batch();

		if ( $this->broadcast->is_scheduled() ) {

			$message = sprintf( __( 'Your broadcast %s has been fully scheduled!', 'groundhogg' ), bold_it( $this->broadcast->get_title() ) );

			notices()->add_user_notice( $message, 'success', true, $this->broadcast->get_scheduled_by_id() );

			return true;
		}

		return false;
	}

	public function get_progress(){
		return $this->broadcast->get_percent_scheduled();
	}

	public function __unserialize( array $data ): void {
		parent::__unserialize( $data );

		$this->broadcast = new Broadcast( $this->broadcast_id );
	}

	public function __serialize(): array {
		return [
			'broadcast_id' => $this->broadcast_id
		];
	}
}
