<?php

namespace Groundhogg\Queue;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Supports_Errors;
use Groundhogg\Utils\Limits;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_object_ids;
use function Groundhogg\is_a_contact;

class Process_Contact_Events extends Supports_Errors {

	protected $contact_ids = [];

	public function __construct( $contacts ) {

		if ( empty( $contacts ) ) {
			return;
		}

		if ( ! is_array( $contacts ) ) {
			$contacts = [ get_contactdata( $contacts ) ];
		}

		$contacts = array_filter( $contacts, function ( $contact ) {
			return is_a_contact( $contact );
		} );

		$this->contact_ids = array_unique( get_object_ids( $contacts ) );

		add_action( 'groundhogg/queue/event_store/claim_events', [ $this, 'add_contact_filter_clause' ] );
		add_action( 'groundhogg/event/failed', [ $this, 'catch_event_errors' ], 10, 2 );

		Limits::set_max_execution_time( 5 );

		\Groundhogg\event_queue()->run_queue();

		remove_action( 'groundhogg/queue/event_store/claim_events', [ $this, 'add_contact_filter_clause' ] );
		remove_action( 'groundhogg/event/failed', [ $this, 'catch_event_errors' ] );
	}

	public function add_contact_filter_clause( Table_Query &$query ) {
		$query->where()->in( 'contact_id', wp_parse_id_list( $this->contact_ids ) );
	}

	public function catch_event_errors( $event, $error ) {
		$this->add_error( $error );
	}

}
