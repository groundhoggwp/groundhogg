<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Step;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\array_find;
use function Groundhogg\Cli\doing_cli;
use function Groundhogg\get_object_ids;
use function WP_CLI\Utils\format_items;
use function cli\prompt;

class Simulator {

	protected static $flow = [];
	protected static $options = [];
	protected static $steps = [];
	protected static $is_dry_run = true;

	public static function is_dry_run(){
		return self::$is_dry_run;
	}

	/**
	 * @param $step_or_id Step|int
	 *
	 * @return Step
	 */
	protected static function asFromSteps( $step_or_id ) {

		if ( ! $step_or_id ) {
			return $step_or_id;
		}

		if ( ! is_a( $step_or_id, Step::class ) ) {
			$step_or_id = new Step( $step_or_id );
		}

		return array_find( self::$steps, function ( Step $other ) use ( $step_or_id ) {
			return $other->ID === $step_or_id->ID;
		} );

	}

	/**
	 * @param Step $from
	 *
	 * @return Step
	 */
	protected static function getNext( Step $from ) {
		return array_find( array_filter( self::$steps, function ( Step $step ) use ( $from ) {
			return in_array( $step->branch, $from->get_branch_path_ids() );
		} ), function ( Step $step ) use ( $from ) {
			return $step->is_after( $from );
		} );
	}

	/**
	 * If ajax, add to the flow variable
	 * if cli, straight out...
	 *
	 * @param $item string|int
	 *
	 * @return void
	 */
	public static function log( $item ) {

		self::$flow[] = $item;

		if ( doing_cli() ) {
			if ( is_numeric( $item ) ) {
				$step = self::asFromSteps( $item );
				$item = $step->get_title();
			}
			\WP_CLI::log( sanitize_text_field( $item ) );
		}
	}

	/**
	 * Respond to the request
	 *
	 * @return void
	 */
	public static function respond() {

		if ( wp_doing_ajax() ) {
			wp_send_json( [
				'flow'    => self::$flow,
				'options' => self::$options
			] );
		}

		\WP_CLI::success( 'Simulation complete!' );
	}

	/**
	 * Get the trigger to continue from via the command line...
	 *
	 * @param $steps
	 *
	 * @return Step|null
	 */
	protected static function cli_handle_trigger_select( $steps ) {

		if ( ! doing_cli() ) {
			return null;
		}

		$options = array_map( function ( Step $step ) {
			return [
				'ID'    => $step->ID,
				'title' => $step->get_title(),
			];
		}, $steps );

		\WP_CLI::log( "\nSelect a trigger to continue..." );

		format_items( 'table', $options, array( 'ID', 'title' ) );

		while ( true ) {

			$confirm = prompt( "Enter the ID or [n/0] to stop", false );
			$confirm = absint( $confirm );

			if ( $confirm === 0 ) {
				return null;
			}

			$step = self::asFromSteps( $confirm );

			if ( $step ) {
				return $step;
			}
		}
	}

	public static function simulate( Step $step, Contact $contact, bool $dryRun = true ) {

		self::$is_dry_run = $dryRun;

		if ( ! $step->exists() ) {
			wp_send_json_error();
		}

		$funnel      = $step->get_funnel();
		self::$steps = $funnel->get_steps();
		$step        = self::asFromSteps( $step );

		/* @var $current Step */
		/* @var $next Step */
		/* @var $prev Step */

		$current = $step;
		$prev    = new Step();
		$next    = false;
		$time    = time();

		while ( $current ) {

			$current->enqueued_contact = $contact;

			// process step-based and timer logic...
			$current = Step::_maybe_filter_step_before_enqueuing( $current, $contact );

			if ( ! is_a( $current, Step::class ) ){
				continue;
			}

			self::log( $current->get_id() );

			if ( $current->is_benchmark() ) {
				$next = array_find( self::$steps, function ( Step $step ) use ( $current ) {
					return $step->is_after( $current );
				} );
			} else if ( $current->is_logic() && method_exists( $current->get_step_element(), 'get_logic_action' ) ) {

				self::log( "🧠 Evaluating logic..." );

				$next = $current->get_step_element()->get_logic_action( $contact );
				$next = self::asFromSteps( $next );

				if ( $current->is_branch_logic() ) {
					if ( $next ) {
						$branch_name = $current->get_step_element()->_get_branch_name( $next->branch );
						self::log( "☑️ Matches conditions for <span class='gh-text purple'>$branch_name</span>" );
					} else {
						self::log( "✖️ No matching steps found, skipping..." );
					}
				} else {

					if ( $next ) {
						// avoid infinite looping...
						$step_counts = array_count_values( self::$flow );
						$nextId      = $next->get_id();

						if ( isset( $step_counts[ $nextId ] ) && $step_counts[ $nextId ] > 2 ) {
							$next = false;
							self::log( "➰ Simulation loop limit exceeded, moving on..." );
						} else {

							if ( $next->is_after( $current ) ){
								self::log( "⏩ Travelling to target..." );
							} else {
								self::log( "⏪ Travelling to target..." );
							}

						}
					} else if ( $next === null ) {
						self::log( "🛑 Stopping the flow..." );
					} else {
						self::log( "▶️ Moving on..." );
					}
				}

				if ( $next === false ) {
					$next = self::getNext( $current );
				}

			} else {

				if ( $current->is_timer() ) {
					$time = $current->get_run_time( $time );
					self::log( "⌛ Wait until " . ( new DateTimeHelper( $time ) )->wpDateTimeFormat() );
				} else if ( ! self::is_dry_run() ) {
					$event = new Event();
					// run the step, but use a simulated event
					$current->get_step_element()->pre_run( $contact, $event );
					$result = $current->get_step_element()->run( $contact, $event );
					if ( is_wp_error( $result ) ){
						self::log( sprintf( "⚠️ %s", $result->get_error_message() ) );
					}
				}

				$next = self::getNext( $current );
			}

			if ( $next && $next->is_benchmark() ) {

				if ( ! $next->can_passthru() ) {
					$siblings = $next->get_siblings_of_same_level();
					$passthru = array_find( $siblings, function ( Step $step ) {
						return $step->is_benchmark() && $step->can_passthru();
					} );

					// no pass thru benchmark, so we prompt to see which way to go
					if ( ! $passthru ) {

						array_unshift( $siblings, $next );

						if ( wp_doing_ajax() ) {
							self::$options = get_object_ids( $siblings );
							self::respond();
						}

						$next = null;

						if ( doing_cli() ) {
							$next = self::cli_handle_trigger_select( $siblings );
						}
					} else {
						$next = $passthru;
						self::log( "🚶‍➡️ Passing through trigger..." );
					}
				}
			}

			$prev    = $current;
			$current = $next;
		}

		self::log( '🏁 Simulation complete!' );

		self::respond();
	}

}
