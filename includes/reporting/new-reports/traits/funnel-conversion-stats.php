<?php

namespace Groundhogg\Reporting\New_Reports\Traits;

use Groundhogg\DB\Query\Query;
use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Event;
use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\get_object_ids;

trait Funnel_Conversion_Stats{

	/**
	 * Count the number of conversions based where the conversion step is being tracked
	 *
	 * @param Funnel $funnel
	 * @param int    $start
	 * @param int    $end
	 *
	 * @return int|mixed|string|null
	 */
	public function get_funnel_conversions( Funnel $funnel, int $start, int $end ) {

		$conversion_steps = $funnel->get_conversion_steps();

		if ( empty( $conversion_steps ) ) {
			return 0;
		}

		$starting_steps = array_filter( $conversion_steps, function ( Step $step ){
			return $step->is_starting();
		} );

		$inner_steps = array_filter( $conversion_steps, function ( Step $step ){
			return $step->is_inner();
		} );

		$conversions = 0;

		// If the conversion benchmark is also a starting benchmark, don't count the first event within the time range
		if ( ! empty( $starting_steps ) ) {

			$eventQuery = new Table_Query( 'events' );
			$eventQuery->setSelect( ['COUNT(ID)', 'total_events'], 'contact_id' )
			           ->setGroupby('contact_id')
			           ->where()
			           ->lessThanEqualTo( 'time', $end )
			           ->greaterThanEqualTo( 'time', $start )
			           ->equals( 'status', Event::COMPLETE )
			           ->equals( 'event_type', Event::FUNNEL )
			           ->equals( 'funnel_id', $funnel->get_id() )
			           ->in( 'step_id', get_object_ids( $starting_steps ) );

			$parentQuery = new Query( $eventQuery, 'events' );
			$parentQuery->setSelect( 'COUNT(contact_id)' )
			            ->where()
			            ->greaterThanEqualTo( 'total_events', 2 );

			$conversions += $parentQuery->get_var();
		}

		if ( ! empty( $inner_steps ) ) {
			$eventQuery = new Table_Query( 'events' );
			$eventQuery->setSelect( 'COUNT(DISTINCT(contact_id))' )
			           ->where()
			           ->lessThanEqualTo( 'time', $end )
			           ->greaterThanEqualTo( 'time', $start )
			           ->equals( 'status', Event::COMPLETE )
			           ->equals( 'event_type', Event::FUNNEL )
			           ->equals( 'funnel_id', $funnel->get_id() )
			           ->in( 'step_id', get_object_ids( $inner_steps ) );

			$conversions += $eventQuery->get_var();
		}

		return $conversions;

	}

}
