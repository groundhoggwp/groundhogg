<?php

namespace Groundhogg;

class Event_Queue_Item extends Event {

	public function __construct( $identifier_or_args = 0, $field = 'ID' ) {
		parent::__construct( $identifier_or_args, 'event_queue', $field );
	}

}
