<?php

namespace Groundhogg\background;

use Groundhogg\Contact_Query;
use function Groundhogg\_nf;
use function Groundhogg\array_filter_by_keys;
use function Groundhogg\array_splice_keys;
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

		// batching
		$batching_args = wp_parse_args( array_splice_keys( $args, [
			'batching',
			'batch_interval',
			'batch_interval_length',
			'batch_amount',
			'batch_delay'
		] ), [
			'batching'              => false,
			'batch_interval'        => 'days',
			'batch_interval_length' => 1,
			'batch_amount'          => 100,
			'batch_delay'           => 0
		] );

		extract( $batching_args );

		foreach ( $contacts as $i => $contact ) {

			$num_scheduled = $i + ( $this->batch * self::BATCH_LIMIT );

			if ( $batching && $num_scheduled > 0 && $num_scheduled % $batch_amount === 0 ) {
				$batch_delay = strtotime( "+$batch_interval_length $batch_interval", $batch_delay );
			}

			/**
			 * This will add the calculated batch delay to the determined event time.
			 *
			 * @param array $event
			 *
			 * @return array
			 */
			$add_batch_delay_filter = function ( array $event ) use ( $batch_delay ){
				$event['time'] = $event['time'] + $batch_delay;
				return $event;
			};

			if ( $batching && $batch_delay ){
				add_filter( 'groundhogg/step/enqueue/event', $add_batch_delay_filter );
			}

			$this->last_id = $contact->ID;
			$this->step->enqueue( $contact, true, $args );

			if ( $batching && $batch_delay ){
				$this->args['batch_delay'] = $batch_delay;
				remove_filter( 'groundhogg/step/enqueue/event', $add_batch_delay_filter );
			}
		}

		$this->batch ++;

		return false;
	}
}
