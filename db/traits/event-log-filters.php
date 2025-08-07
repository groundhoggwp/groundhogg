<?php

namespace Groundhogg\DB\Traits;

use Groundhogg\Broadcast;
use Groundhogg\Contact_Query;
use Groundhogg\DB\Query\Where;
use Groundhogg\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

trait Event_Log_Filters {

	protected function maybe_register_filters() {

		parent::maybe_register_filters();

		$this->query_filters->register( 'funnel', function ( $filter, $where ) {
			$filter = wp_parse_args( $filter, [
				'funnel_id' => false,
				'step_id'   => false,
			] );

			if ( $filter['funnel_id'] ) {
				$where->equals( 'funnel_id', absint( $filter['funnel_id'] ) );
			}

			if ( $filter['step_id'] ) {
				$where->equals( 'step_id', absint( $filter['step_id'] ) );
			}

			$where->equals( 'event_type', Event::FUNNEL );
		} );

		$this->query_filters->register( 'broadcast', function ( $filter, $where ) {
			$filter = wp_parse_args( $filter, [
				'broadcast_id' => false,
			] );

			if ( $filter['broadcast_id'] ) {
				$where->equals( 'step_id', absint( $filter['broadcast_id'] ) );
			}

			$where->equals( 'event_type', Event::BROADCAST );
			$where->equals( 'funnel_id', Broadcast::FUNNEL_ID );
		} );

		$this->query_filters->register( 'contacts', function ( $filter, Where $where ) {
			$filter = wp_parse_args( $filter, [
				'contacts' => [],
			] );

			$where->in( 'contact_id', wp_parse_id_list( $filter['contacts'] ) );
		} );

		$this->query_filters->register( 'contact_query', function ( $filter, Where $where ) {

			$filter = wp_parse_args( $filter, [
				'include_filters' => [],
				'exclude_filters' => [],
			] );

			// Nothing is being queried
			if ( empty( $filter['include_filters'] ) && empty( $filter['exclude_filters'] ) ) {
				return;
			}

			$query = new Contact_Query( [
				'include_filters' => $filter['include_filters'],
				'exclude_filters' => $filter['exclude_filters'],
			] );

			$query->setSelect( 'ID' );

			$where->in( 'contact_id', $query );
		} );
	}

}
