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
 *     # Fake a broadcast
 *     $ wp groundhogg/faker broadcast 1234 '30 days ago' --tags_include=1
 *     Success: Created fake broadcast.
 *
 *     # Fake funnel activity
 *     $ wp groundhogg/faker funnel 4321 '30 days ago' --include=1
 *     Success: Created fake activity and events for funnel.
 */
class Faker {

	/**
	 * Reset the date_created of contacts to be within the last few months.
	 * Prefer 6:00 am to 9:00 pm
	 *
	 * @when after_wp_load
	 */
	function reset_dates() {

		$base_date = new DateTimeHelper( 'yesterday 9:00 pm' );
		$query     = new Contact_Query( [
			'orderby'    => 'ID',
			'order'      => 'DESC',
			'found_rows' => true,
		] );
		$contacts  = $query->query( null, true );
		$progress  = make_progress_bar( 'Resetting contact dates...', $query->found_items );

		$max_time_in_minutes = 92 * 12 * 60; // 92 days, 15 hours (6:00-21:00), 60 minutes
		$distribution = floor( $max_time_in_minutes / $query->found_items ) * 2;

		foreach ( $contacts as $contact ) {

			$base_date->modify( sprintf( '-%d minutes', rand( 1, $distribution ) ) );

			$contact->update( [
				'date_created'              => $base_date->ymdhis(),
				'date_optin_status_changed' => $base_date->ymdhis(),
			] );

			// Prefer 6:00 am to 9:00 pm
			if ( absint( $base_date->format( 'H' ) ) < 6 ) {
				$base_date->modify( 'yesterday 9:00 pm' );
			}

			$progress->tick();
		}

		$progress->finish();

	}

	/**
	 * Fakes funnel activity
	 *
	 * ## OPTIONS
	 *
	 * <funnel>
	 * : ID of the funnel
	 *
	 * [--query=<param>]
	 * : Query parameters to select contacts
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg/faker funnel 1234 '30 days ago' --search=FooBar
	 *
	 * @when after_wp_load
	 */
	function funnel( $args, $assoc_args ) {

		$query     = $assoc_args;
		$funnel_id = $args[0];

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			\WP_CLI::error( 'The given funnel does not exist.' );
		}

		\Groundhogg\Faker::funnel_journeys( $funnel, $query );

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
	 * [--query=<param>]
	 * : Query parameters to select contacts
	 *
	 * ## EXAMPLES
	 *
	 *     wp groundhogg/faker broadcast 1234 '30 days ago' --search=FooBar
	 *
	 * @when after_wp_load
	 */
	function broadcast( $args, $assoc_args ) {

		$query    = $assoc_args;
		$email_id = $args[0];
		$date     = absint( $args[1] );

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
