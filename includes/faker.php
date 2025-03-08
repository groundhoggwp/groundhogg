<?php

namespace Groundhogg;

use Groundhogg\Classes\Activity;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\Cli\doing_cli;
use function WP_CLI\Utils\make_progress_bar;

class Faker {

	/**
	 * Kind of like a coin flip but percentage based
	 *
	 * @param int $percentage
	 *
	 * @return bool
	 */
	public static function chance( int $percentage ): bool {

		// Generate a random number between 0 and 100
		$randomNumber = rand( 0, 100 );

		// Return true if the random number is less than or equal to the percentage, otherwise return false
		return $randomNumber <= $percentage;
	}

	/**
	 * Fake a broadcast by creating events directly in the event history table. Also fake activity
	 *
	 * Considerations
	 * - keep broadcast sizes limited, maybe 1K-2K, since we may be limited on processing time/power.
	 *
	 * @param Email     $email the email template to use
	 * @param array     $query the contact query
	 * @param \DateTime $date  the date we "sent" the broadcast
	 *
	 * @return void
	 */
	public static function broadcast( Email $email, array $query, \DateTime $date ) {

		// Create the broadcast
		$broadcast = new Broadcast( [
			'object_id'    => $email->get_id(),
			'object_type'  => 'email',
			'status'       => 'sent',
			'send_time'    => $date->getTimestamp(),
			'scheduled_by' => 1,
			'query'        => $query,
		] );

		// associate campaigns that are associated with the email
		$campaigns = $email->get_related_objects( 'campaign' );
		foreach ( $campaigns as $campaign ) {
			$broadcast->create_relationship( $campaign );
		}

		$query['before']     = $date->getTimestamp();
		$query['marketable'] = true;

		$query    = new Contact_Query( $query );
		$contacts = $query->query( null, true );

		if ( doing_cli() ) {
			$progress = make_progress_bar( 'Generating activity and events', count( $contacts ) );
		}

		// Loop through contacts and create events, then activity
		foreach ( $contacts as $contact ) {

			$event = new Event();
			$event->create( [
				'time'           => $date->getTimestamp(),
				'time_scheduled' => $date->getTimestamp(),
				'funnel_id'      => Broadcast::FUNNEL_ID,
				'step_id'        => $broadcast->get_id(),
				'email_id'       => $email->get_id(),
				'contact_id'     => $contact->get_id(),
				'event_type'     => Event::BROADCAST,
				'status'         => Event::COMPLETE,
			] );

			self::email_activity( $email, $event );

			if ( isset( $progress ) ) {
				$progress->tick();
			}
		}

		if ( isset( $progress ) ) {
			$progress->finish();
		}
	}

	/**
	 * Given an email and event, generate email activity
	 * - 30% chance of open
	 * - if opened, 15% chance of click
	 * - if opened, always 2% chance of unsubscribing
	 *
	 * @param Email $email
	 * @param Event $event
	 *
	 * @return void|int
	 */
	public static function email_activity( Email $email, Event $event ) {

		// 40% chance of open
		if ( self::chance( 40 ) ) {

			$date = new DateTimeHelper( $event->get_time() );
			$date->modify( sprintf( '+%d minutes', rand( 2, 10 ) ) );

			track_event_activity( $event, Activity::EMAIL_OPENED, [], [
				'timestamp' => $date->getTimestamp()
			] );

			// 20% chance of click
			if ( self::chance( 20 ) ) {

				$date->modify( '+1 minute' );

				// For getting urls related to the current contact
				$email->set_event( $event );
				$email->set_contact( $event->get_contact() );

				$urls = $email->get_urls();

				if ( ! empty( $urls ) ) {
					$url = $urls[ array_rand( $urls ) ];

					if ( ! empty( $url ) ) {
						track_event_activity( $event, Activity::EMAIL_CLICKED, [], [
							'referer'   => $url,
							'timestamp' => $date->getTimestamp()
						] );

						// Clicked a funnel tracking link
						if ( preg_match( '@/gh/click/(?<id>[0-9]+)-[^/]+@', $url, $matches ) ) {
							return absint( $matches['id'] );
						}

						track_page_visit( $url, $event->get_contact(), [
							'timestamp' => $date->getTimestamp()
						] );
					}
				}
			}

			// 2% chance of a contact unsubscribing
			if ( self::chance( 2 ) ) {

				$date->modify( '+2 minutes' );

				$url = managed_page_url( 'preferences/manage' );

				track_event_activity( $event, Activity::EMAIL_CLICKED, [], [
					'referer'   => $url,
					'timestamp' => $date->getTimestamp()
				] );

				track_event_activity( $event, Activity::UNSUBSCRIBED, [], [
					'timestamp' => $date->getTimestamp()
				] );

				// Unsubscribe the contact and set the date_optin_status_changed
				$event->get_contact()->update( [
					'date_optin_status_changed' => $date->ymdhis(),
					'optin_status'              => Preferences::UNSUBSCRIBED
				] );

				track_page_visit( $url, $event->get_contact(), [
					'timestamp' => $date->getTimestamp()
				] );
			}
		}
	}

	/**
	 * Given a contact query, process multiple funnel journeys
	 *
	 * @throws \Exception
	 *
	 * @param array  $query  The contacts to create fake data for
	 * @param string $modifier modifier of the start tie based on the date the contact was created
	 * @param Funnel $funnel The funnel to create the fake data for
	 *
	 * @return void
	 */
	public static function funnel_journeys( Funnel $funnel, array $query, string $modifier = '' ) {

		$query    = new Contact_Query( $query );
		$contacts = $query->query( null, true );

		if ( doing_cli() ) {
			\WP_CLI::log( sprintf( 'Generating funnel activity for %s contacts', _nf( count( $contacts ) ) ) );
			$progress = make_progress_bar( 'Generating activity and events', count( $contacts ) );
		}

		foreach ( $contacts as $contact ) {
			$start = $contact->get_date_created( true );

			if ( $modifier ) {
				$start->modify( $modifier );
			}

			if ( $start->isFuture() ){
				continue;
			}

			self::funnel_journey( $funnel, $contact, $start );

			if ( isset( $progress ) ) {
				$progress->tick();
			}
		}

		if ( isset( $progress ) ) {
			$progress->finish();
		}

	}

	/**
	 * Given a contact and a funnel, fake a funnel journey from start to finish
	 * This will generate fake events and activity based on the start and the steps and timers within the funnel
	 * IT should generate opens/clicks/unsub for email steps, as well as activity related to any other events
	 *
	 * Considerations
	 * - To keep it simple, avoid funnels with multiple benchmarks in OR configurations
	 * - Ignore conditional logic?
	 * - Complete every step? Chance to move to next benchmark?
	 * - Simple funnels, Forms, timers, emails, admin notifications?
	 * - We're just bailing if delay timers go into the future, so maybe only fake data for funnels where the "length" is less than the time between the start date and the current time.
	 *
	 * MVP
	 * - Do every step
	 * - ignore conditional logic
	 * - Fake data for forms and emails only
	 *
	 *
	 * @param Funnel    $funnel
	 * @param Contact   $contact
	 * @param \DateTime $date when to "start" the funnel.
	 *
	 * @return void
	 */
	public static function funnel_journey( Funnel $funnel, Contact $contact, \DateTime $date ) {

		$entry = $funnel->get_entry_steps();

		if ( empty( $entry ) ) {
			return;
		}

		$step = $entry[ array_rand( $entry ) ];

		do {

			$event_args = [
				'time'           => $date->getTimestamp(),
				'micro_time'     => micro_seconds(),
				'time_scheduled' => $date->getTimestamp(),
				'funnel_id'      => $funnel->get_id(),
				'step_id'        => $step->get_id(),
				'contact_id'     => $contact->get_id(),
				'event_type'     => Event::FUNNEL,
				'status'         => Event::COMPLETE,
			];

			$event = new Event();
			$event->create( $event_args );

			$next = $step->get_next_action( $contact );

			switch ( $step->get_type() ) {
				case 'send_email':

					$email = new Email( $step->get_meta( 'email_id' ) );

					$event->update( [
						'email_id' => $email->get_id()
					] );

					$link_click_benchmark_id = self::email_activity( $email, $event );

					if ( $link_click_benchmark_id ){
						$next = new Step( $link_click_benchmark_id );
					}

					break;
				case 'delay_timer':

					$after_timer = $step->get_run_time( $date->getTimestamp() );

					if ( $after_timer > time() ) {
						break 2; // todo: Is there a better way to handle this than just bail?
					}

					$date->setTimestamp( $after_timer );
					$event->update( [
						'time' => $date->getTimestamp()
					] );
					break;

				case 'web_form':

					get_db( 'form_impressions' )->add( [
						'form_id'    => $step->get_id(),
						'ip_address' => $contact->get_ip_address(),
						'views'      => rand( 1, 3 ),
						'timestamp'  => $date->getTimestamp() - ( 5 * MINUTE_IN_SECONDS )
					] );

					$submission = new Submission();
					$submission->create( [
						'step_id'      => $step->get_id(),
						'contact_id'   => $contact->get_id(),
						'date_created' => $date->format( 'Y-m-d H:i:s' )
					] );

					$submission->add_posted_data( [
						'first_name' => $contact->get_first_name(),
						'last_name'  => $contact->get_last_name(),
						'email'      => $contact->get_email(),
					] );

					break;
				case 'login_status':
					track_event_activity( $event, Activity::LOGIN, [], [
						'timestamp' => $date->getTimestamp()
					] );
					break;
				case 'custom_activity':
					track_event_activity( $event, $step->get_meta( 'type' ), [], [
						'timestamp' => $date->getTimestamp()
					] );
					break;
				case 'apply_tag':
					$contact->apply_tag( wp_parse_id_list( $step->get_meta( 'tags' ) ) );
					break;
				case 'tag_applied':
					if ( $step->is_inner() ) {
						$contact->apply_tag( wp_parse_id_list( $step->get_meta( 'tags' ) ) );
					}
					break;
				case 'remove_tag':
					$contact->remove_tag( wp_parse_id_list( $step->get_meta( 'tags' ) ) );
					break;
				case 'tag_removed':
					if ( $step->is_inner() ) {
						$contact->remove_tag( wp_parse_id_list( $step->get_meta( 'tags' ) ) );
					}
					break;
				case 'apply_note':
				case 'create_task':
				case 'create_user':
				case 'apply_owner':
				case 'new_custom_activity':
				case 'edit_meta':
					$step->run( $contact, $event );
					break;
				case 'link_click':
					track_page_visit( $step->get_meta( 'redirect_to' ), $contact, [
						'timestamp' => $date->getTimestamp()
					] );
					break;
			}

			// Then it's the end of the funnel or there is a benchmark
			if ( ! $next || ! $next->exists() ) {
				$benchmarks = $step->get_proceeding_benchmarks();
				// ~50% of contacts will continue through benchmarks
				if ( ! empty( $benchmarks ) && self::chance( 50 ) ) {
					$next = $benchmarks[ array_rand( $benchmarks ) ];
				}
			}

			$step = $next;

		} while ( $step );
	}

}
