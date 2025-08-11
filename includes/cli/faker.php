<?php

namespace Groundhogg\Cli;

use Groundhogg\Contact_Query;
use Groundhogg\Email;
use Groundhogg\Funnel;
use Groundhogg\Utils\DateTimeHelper;
use function WP_CLI\Utils\make_progress_bar;

/**
 * Fake data
 *
 * ## EXAMPLES
 *
 *     # Reset contact dates
 *     $ wp groundhogg-faker reset-dates 180
 *     Success: All contact creation dates have been reset
 *
 *     # Fake a broadcast
 *     $ wp groundhogg-faker broadcast 1234 '30 days ago' --tags_include=1
 *     Success: Created fake broadcast.
 *
 *     # Fake funnel activity
 *     $ wp groundhogg-faker funnel 4321 '30 days ago' --include=1
 *     Success: Created fake activity and events for funnel.
 */
class Faker {

	/**
	 * Reset the date_created of contacts to be within the given range.
	 *
	 * ## OPTIONS
	 *
	 * <days>
	 * : How far back to reset the dates
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-faker reset-dates 90
	 *
	 * @subcommand reset-dates
	 */
	function reset_dates( $args ) {

		$days = absint( $args[0] );

		$base_date  = new DateTimeHelper( 'yesterday 9:00:00' );
		$start_date = new DateTimeHelper( $days . ' days ago' );

		$query     = new Contact_Query( [
			'orderby'    => 'ID',
			'order' => 'ASC',
			'found_rows' => true,
		] );

		$contacts  = $query->query( null, true );
		$progress  = make_progress_bar( 'Resetting contact dates...', $query->found_items );

		$avg_records_per_day = ceil( $query->found_items / $days );
		$records_per_day     = $avg_records_per_day * 2;
		$daily_modifier      = $records_per_day / $days;

		while ( $base_date > $start_date && ! empty( $contacts ) ) {

			$num_records = wp_rand( $avg_records_per_day, ceil( $records_per_day ) );

			$eod = ( clone $base_date )->modify( '9:00 pm' );
			$sod = ( clone $eod )->modify( '6:00 am' );

			$times = [];

			for ( $i = 0; $i < $num_records; $i ++ ) {
				$times[] = wp_rand( $sod->getTimestamp(), $eod->getTimestamp() );
			}

			rsort( $times );

			foreach ( $times as $timestamp ) {

				$contact = array_pop( $contacts );

				if ( ! $contact ) {
					break 2;
				}

				$date = new DateTimeHelper( $timestamp );

				$contact->update( [
					'date_created'              => $date->ymdhis(),
					'date_optin_status_changed' => $date->ymdhis(),
				] );

				$progress->tick();
			}

			if ( $records_per_day > 5 ) {
				$records_per_day -= $daily_modifier;
			}

			$base_date->modify( '-1 day' );

			if ( ! empty( $contacts ) && $base_date < $start_date ) {
				$start_date->modify( '-1 day' );
			}
		}

		$progress->finish();

		\WP_CLI::success( 'All contact creation dates have been reset' );
	}

	/**
	 * Fakes funnel activity
	 *
	 * ## OPTIONS
	 *
	 * <funnel>
	 * : ID of the funnel
	 *
	 * [<modifier>]
	 * : Modify when the funnel journey should start based on the contact's date_created. Must be strtotime friendly.
	 *
	 * [--<field>=<value>]
	 * : Query parameters to select contacts
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-faker funnel 1234 14 --search=FooBar
	 *
	 * @when after_wp_load
	 */
	function funnel( $args, $assoc_args ) {

		$query     = $assoc_args;
		$funnel_id = $args[0];
		$modifier = $args[1] ?? '';

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			\WP_CLI::error( 'The given funnel does not exist.' );
		}

		\Groundhogg\Faker::funnel_journeys( $funnel, $query, $modifier );

		\WP_CLI::success( sprintf( 'Generated activity and events for %s', $funnel->get_title() ) );
	}

	/**
	 * Fakes funnel activity
	 *
	 * ## OPTIONS
	 *
	 * <email>
	 * : ID of the email
	 *
	 * [<date>]
	 * : the base time the broadcast was sent. Unix timestamp or an English textual datetime description compatible with `strtotime()`
	 *
	 * [--<field>=<value>]
	 * : Query parameters to select contacts
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg-faker broadcast 1234 '30 days ago' --search=FooBar
	 *
	 * @when after_wp_load
	 */
	function broadcast( $args, $assoc_args ) {

		$query    = $assoc_args;
		$email_id = absint( $args[0] );
		$date     = $args[1];

		$date  = new DateTimeHelper( $date );
		$email = new Email( $email_id );

		if ( ! $email->exists() ) {
			\WP_CLI::error( 'The given email does not exist.' );
		}

		if ( ! $date->isPast() ) {
			\WP_CLI::error( 'The date must be in the past.' );
		}

		\Groundhogg\Faker::broadcast( $email, $query, $date );

		\WP_CLI::success( sprintf( 'Generated activity and events for %s', $email->get_title() ) );
	}

}
