<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Step;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\html;
use function Groundhogg\one_of;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DelayDateTime extends DateTimeHelper {

	private $min;
	private $max;

	public function setMin() {
		$this->min = $this->getTimestamp();
	}

	public function setMax() {
		$this->max = $this->getTimestamp();
	}

	public function useMax() {
        if ( $this->max ){
	        $this->setTimestamp( $this->max );
        }
	}

	/**
	 * Modify the date but don't make it smaller than the min and larger than the max
	 *
	 * @param $modifier
	 *
	 * @return $this
	 */
	public function minMax( $modifier ) {

		$orig = $this->getTimestamp();

		$this->modify( $modifier );

		// Don't make it smaller than the min
		if ( $this->min && $this->getTimestamp() >= $this->min ) {
			if ( ! $this->max || $this->getTimestamp() < $this->max ) {
				$this->setMax();
			}
		}

		// Set the timestamp back to the orig
		$this->setTimestamp( $orig );

		return $this;
	}

	public function isPast() {
		return $this->getTimestamp() < time();
	}
}

/**
 * Delay Timer
 *
 * This allows the adition of an event which "does nothing" but runs at the specified time according to the time provided.
 * Essentially delaying proceeding events.
 *
 * @since       File available since Release 0.9
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
class Delay_Timer extends Action {

	const TYPE = 'delay_timer';

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://docs.groundhogg.io/docs/builder/actions/delay-timer/';
	}

	/**
	 * Get the element name
	 *
	 * @return string
	 */
	public function get_name() {
		return esc_html_x( 'Delay Timer', 'step_name', 'groundhogg' );
	}

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	public function get_type() {
		return 'delay_timer';
	}

	public function get_sub_group() {
		return 'delay';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	public function get_description() {
		return _x( 'Pause for the specified amount of time.', 'step_description', 'groundhogg' );
	}

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	public function get_icon() {
//		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/delay-timer.png';
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/delay/delay-timer.svg';
	}

	public function admin_scripts() {
		wp_enqueue_script( 'groundhogg-funnel-delay-timer' );
	}

	/**
	 * @param $step Step
	 */
	public function settings( $step ) {
		html( 'div', [
			'id'    => "step_{$step->ID}_delay_timer_settings",
			'class' => 'ignore-morph'
		], 'Delay Timer' );

	}

	/**
	 * Show a preview of the run time
	 *
	 * @param Step $step
	 *
	 * @return void
	 */
	protected function before_step_notes( Step $step ) {

		?>
        <div class="gh-panel">
            <div class="gh-panel-header">
                <h2><?php esc_html_e( 'Delay Preview', 'groundhogg' ) ?></h2>
            </div>
            <div class="inside">
				<?php

				$date = new DelayDateTime( 'now' );

				$date->setTimestamp( self::calc_run_time( time(), $step ) );

				html( 'div', [
					'class' => "display-flex gap-10 column"
				], [
					'<b>' . esc_html__( 'Runs on...', 'groundhogg' ) . '</b>',
					'<span>' . esc_html( $date->wpDateTimeFormat() ) . '</span>'
				] );

				?>
            </div>
        </div>
		<?php
	}

	public function generate_step_title( $step ) {
		return $this->get_setting( 'delay_preview' ) ?: 'Wait 3 days';
	}

	public function get_settings_schema() {
		return [
			'delay_preview'     => [
				'default'  => '',
				'sanitize' => function ( $value ) {
					return wp_kses( $value, 'data' );
				}
			],
			'delay_amount'      => [
				'default'  => 0,
				'sanitize' => 'absint'
			],
			'delay_type'        => [
				'default'  => 'days',
				'sanitize' => function ( $value ) {
					return one_of( $value, [ 'minutes', 'hours', 'days', 'weeks', 'months', 'years', 'none' ] );
				}
			],
			'run_on_type'       => [
				'default'  => 'any',
				'sanitize' => function ( $value ) {
					return one_of( $value, [ 'any', 'weekday', 'weekend', 'day_of_month', 'day_of_week' ] );
				}
			],
			'run_when'          => [
				'default'  => 'now',
				'sanitize' => function ( $value ) {
					return one_of( $value, [ 'now', 'later', 'between' ] );
				}
			],
			'run_time'          => [
				'default'  => '09:00:00',
				'sanitize' => function ( $value ) {
					return ( new DateTimeHelper( $value ) )->format( 'H:i:s' );
				}
			],
			'run_time_to'       => [
				'default'  => '17:00:00',
				'sanitize' => function ( $value ) {
					return ( new DateTimeHelper( $value ) )->format( 'H:i:s' );
				}
			],
			'send_in_timezone'  => [
				'default'  => false,
				'sanitize' => 'boolval'
			],
			'run_on_dow_type'   => [
				'default'  => 'any',
				'sanitize' => function ( $value ) {
					return one_of( $value, [ 'any', 'first', 'second', 'third', 'fourth', 'last' ] );
				}
			],
			'run_on_dow'        => [
				'default'  => [],
				'sanitize' => function ( $value ) {
					return array_intersect( $value, [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ] );
				}
			],
			'run_on_month_type' => [
				'default'  => 'any',
				'sanitize' => function ( $value ) {
					return one_of( $value, [ 'any', 'specific' ] );
				}
			],
			'run_on_months'     => [
				'default'  => [],
				'sanitize' => function ( $value ) {
					return array_intersect( $value, [
						'january',
						'february',
						'march',
						'april',
						'may',
						'june',
						'july',
						'august',
						'september',
						'october',
						'november',
						'december',
					] );
				}
			],
			'run_on_dom'        => [
				'default'  => [],
				'sanitize' => function ( $value ) {
					// 'last' must stay a string so calc_run_time()'s strict === 'last' check matches
					$days = array_map( function ( $day ) {
						return $day === 'last' ? 'last' : absint( $day );
					}, (array) $value );

					return array_values( array_filter( $days, function ( $day ) {
						return $day === 'last' || ( $day >= 1 && $day <= 31 );
					} ) );
				}
			],
		];
	}

	/**
	 * Replaces the get_enqueue_time() method and utilizes a base timestamp
	 *
	 * @throws \Exception
	 *
	 * @param Step $step
	 * @param int  $baseTimestamp
	 *
	 * @return int
	 */
	public function calc_run_time( int $baseTimestamp, Step $step ): int {

		$settings = wp_parse_args( $step->get_meta(), [
			'delay_amount'      => 3,
			'delay_type'        => 'days',
			'run_on_type'       => 'any',
			'run_when'          => 'now',
			'run_time'          => '09:00:00',
			'send_in_timezone'  => false,
			'run_time_to'       => '17:00:00',
			'run_on_dow_type'   => 'any', // Run on days of week type
			'run_on_dow'        => [], // Run on days of week
			'run_on_month_type' => 'any', // Run on month type
			'run_on_months'     => [], // Run on months
			'run_on_dom'        => [], // Run on days of month,
		] );

		$contact = $step->enqueued_contact;
		$date    = new DelayDateTime( $baseTimestamp );
		$tz      = $settings['send_in_timezone'] && $contact ? $contact->get_time_zone( false ) : wp_timezone();
		$date->setTimezone( $tz );

		// The base amount of time which we need to wait for
		if ( $settings['delay_type'] !== 'none' && $settings['delay_amount'] ) {
			$date->modify( sprintf( '+%d %s', $settings['delay_amount'], $settings['delay_type'] ) );
		}

		switch ( $settings['run_when'] ) {

			default:
			case 'now':
				// do nothing
				break;
			case 'later':

				$date->modify( $settings['run_time'] );

				// relative to the base time, not the current time, so historical base times work too
				if ( $date->getTimestamp() < $baseTimestamp ) {
					$date->modify( '+1 day' );
				}

				break;
			case 'between':

				$from = clone $date;
				$from->modify( $settings['run_time'] );
				$to = clone $date;
				$to->modify( $settings['run_time_to'] );

				// If the time does not fall within the given from/to modify it to the next day run time.
				if ( $date < $from ) {
					$date->modify( $settings['run_time'] );
				}

				if ( $date > $to ) {
					$date->modify( '+1 day ' . $settings['run_time'] );
				}

				break;
		}

		if ( in_array( $settings['run_on_type'], [ 'weekday', 'weekend', 'day_of_week', 'day_of_month' ] ) ) {
			$this->next_matching_day( $date, $settings );
		}

		// if the calculated time is now, lets advanced the base time by a minute...
		// this cleverly prevents an infinite loop
		if ( $date->isNow() ) {
			return $this->calc_run_time( $date->getTimestamp() + MINUTE_IN_SECONDS, $step );
		}

		return $date->getTimestamp();
	}

	/**
	 * Move the date forward to the first day, starting with the date itself, that matches the run_on_* settings.
	 * Walks the calendar a day at a time, skipping whole months that aren't selected, and keeps the time of day.
	 * If no day matches within 5 years (e.g. the 30th of February) the date is left unchanged.
	 *
	 * @param DateTimeHelper $date
	 * @param array          $settings
	 *
	 * @return void
	 */
	protected function next_matching_day( DateTimeHelper $date, array $settings ) {

		switch ( $settings['run_on_type'] ) {
			case 'weekday':
				$matches = function ( $date ) {
					return $date->format( 'N' ) <= 5;
				};
				break;
			case 'weekend':
				$matches = function ( $date ) {
					return $date->format( 'N' ) >= 6;
				};
				break;
			case 'day_of_week':

				$days_of_week = array_map( 'strtolower', (array) $settings['run_on_dow'] );
				$dow_type     = $settings['run_on_dow_type'];
				$nth          = array_search( $dow_type, [ 1 => 'first', 'second', 'third', 'fourth' ] );

				if ( empty( $days_of_week ) ) {
					return;
				}

				$matches = function ( $date ) use ( $days_of_week, $dow_type, $nth ) {

					if ( ! in_array( strtolower( $date->format( 'l' ) ), $days_of_week ) ) {
						return false;
					}

					$day = (int) $date->format( 'j' );

					if ( $dow_type === 'last' ) {
						return $day + 7 > (int) $date->format( 't' );
					}

					return ! $nth || (int) ceil( $day / 7 ) === $nth;
				};

				break;
			case 'day_of_month':

				$days_of_month = array_map( 'intval', array_filter( (array) $settings['run_on_dom'], 'is_numeric' ) );
				$last_day      = in_array( 'last', (array) $settings['run_on_dom'], true );

				if ( empty( $days_of_month ) && ! $last_day ) {
					return;
				}

				$matches = function ( $date ) use ( $days_of_month, $last_day ) {
					$day = (int) $date->format( 'j' );

					return in_array( $day, $days_of_month, true ) || ( $last_day && $day === (int) $date->format( 't' ) );
				};

				break;
			default:
				return;
		}

		$months = false;

		if ( $settings['run_on_month_type'] !== 'any' && in_array( $settings['run_on_type'], [ 'day_of_week', 'day_of_month' ] ) ) {
			$months = array_map( 'strtolower', (array) $settings['run_on_months'] );

			if ( empty( $months ) ) {
				return;
			}
		}

		$start = $date->getTimestamp();
		$time  = array_map( 'intval', explode( ':', $date->format( 'H:i:s' ) ) );
		$limit = ( clone $date )->modify( '+5 years' ); // long enough for the 29th of February

		while ( $date < $limit ) {

			[ $year, $month, $day ] = array_map( 'intval', explode( '-', $date->format( 'Y-n-j' ) ) );

			if ( $months && ! in_array( strtolower( $date->format( 'F' ) ), $months ) ) {
				$date->setDate( $year, $month + 1, 1 );
				continue;
			}

			if ( $matches( $date ) ) {

				// setDate() shifts the time when it lands in a DST gap, so restore the wall clock time
				if ( $date->getTimestamp() !== $start ) {
					$date->setTime( ...$time );
				}

				return;
			}

			$date->setDate( $year, $month, $day + 1 );
		}

		$date->setTimestamp( $start );
	}
}
