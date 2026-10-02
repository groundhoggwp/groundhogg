<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Step;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\bold_it;
use function Groundhogg\html;
use function Groundhogg\one_of;
use function Groundhogg\ordinal_suffix;
use function Groundhogg\orList;

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

				try {
					$date->setTimestamp( self::calc_run_time( time(), $step ) );

					$preview = [
						'<b>' . esc_html__( 'Runs on...', 'groundhogg' ) . '</b>',
						'<span>' . esc_html( $date->wpDateTimeFormat() ) . '</span>'
					];
				} catch ( \Exception $e ) {
					$preview = [
						'<b>' . esc_html__( 'Does not run', 'groundhogg' ) . '</b>',
						'<span>' . esc_html( $e->getMessage() ) . '</span>'
					];
				}

				html( 'div', [
					'class' => "display-flex gap-10 column"
				], $preview );

				?>
            </div>
        </div>
		<?php
	}

	public function generate_step_title( $step ) {
		return $this->get_setting( 'delay_preview' ) ?: self::delay_preview( $this->get_delay_settings( $step ?: $this->get_current_step() ) );
	}

	/**
	 * The step's delay settings, with defaults for anything missing
	 *
	 * @param Step $step
	 *
	 * @return array
	 */
	protected function get_delay_settings( Step $step ) {
		return wp_parse_args( $step->get_meta(), [
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
	}

	/**
	 * Human-readable summary of the delay settings.
	 * Mirrors delayTimerName() in assets/js/admin/funnels/funnel-steps.js, which computes delay_preview in the editor,
	 * so steps whose settings were set some other way (abilities, REST, imports) still get an accurate title.
	 *
	 * @param array $settings
	 *
	 * @return string
	 */
	public static function delay_preview( array $settings ) {

		$format_time = function ( $time ) {
			try {
				return bold_it( ( new DateTimeHelper( '2021-01-01 ' . $time ) )->wpTimeFormat() );
			} catch ( \Exception $e ) {
				return bold_it( esc_html( $time ) );
			}
		};

		$days_of_week = [
			'monday'    => __( 'Monday', 'groundhogg' ),
			'tuesday'   => __( 'Tuesday', 'groundhogg' ),
			'wednesday' => __( 'Wednesday', 'groundhogg' ),
			'thursday'  => __( 'Thursday', 'groundhogg' ),
			'friday'    => __( 'Friday', 'groundhogg' ),
			'saturday'  => __( 'Saturday', 'groundhogg' ),
			'sunday'    => __( 'Sunday', 'groundhogg' ),
		];

		$determiners = [
			'first'  => __( 'First', 'groundhogg' ),
			'second' => __( 'Second', 'groundhogg' ),
			'third'  => __( 'Third', 'groundhogg' ),
			'fourth' => __( 'Fourth', 'groundhogg' ),
			'last'   => __( 'Last', 'groundhogg' ),
		];

		$months = [
			'january'   => __( 'January', 'groundhogg' ),
			'february'  => __( 'February', 'groundhogg' ),
			'march'     => __( 'March', 'groundhogg' ),
			'april'     => __( 'April', 'groundhogg' ),
			'may'       => __( 'May', 'groundhogg' ),
			'june'      => __( 'June', 'groundhogg' ),
			'july'      => __( 'July', 'groundhogg' ),
			'august'    => __( 'August', 'groundhogg' ),
			'september' => __( 'September', 'groundhogg' ),
			'october'   => __( 'October', 'groundhogg' ),
			'november'  => __( 'November', 'groundhogg' ),
			'december'  => __( 'December', 'groundhogg' ),
		];

		// Bold the labels of the selected keys, skipping unknown ones
		$bold_labels = function ( $selected, $labels ) {
			$selected = array_filter( (array) $selected, function ( $key ) use ( $labels ) {
				return isset( $labels[ $key ] );
			} );

			return array_values( array_map( function ( $key ) use ( $labels ) {
				return bold_it( $labels[ $key ] );
			}, $selected ) );
		};

		$months_list = $settings['run_on_month_type'] === 'specific'
			? orList( $bold_labels( $settings['run_on_months'], $months ) )
			: bold_it( __( 'any month', 'groundhogg' ) );

		$preview = [];

		switch ( $settings['run_when'] ) {
			default:
			case 'now':
				$preview[] = _x( 'at any time', 'run at any time of day', 'groundhogg' );
				break;
			case 'later':
				/* translators: %s: a specific time like "09:00:00" */
				$preview[] = sprintf( _x( 'at %s', 'at a specific time', 'groundhogg' ), $format_time( $settings['run_time'] ) );
				break;
			case 'between':
				/* translators: 1: a specific time like "09:00:00", 2: another specific time like "17:00:00" */
				$preview[] = sprintf( _x( 'between %1$s and %2$s', 'within a time from', 'groundhogg' ), $format_time( $settings['run_time'] ), $format_time( $settings['run_time_to'] ) );
				break;
		}

		switch ( $settings['run_on_type'] ) {
			default:
			case 'any':
				$run = _x( 'run', 'verb meaning to start a process', 'groundhogg' );
				break;
			case 'weekday':
				$run = _x( 'run on <b>a weekday</b>', 'verb meaning to start a process - on a weekday', 'groundhogg' );
				break;
			case 'weekend':
				$run = _x( 'run on <b>a weekend</b>', 'verb meaning to start a process - on a weekend', 'groundhogg' );
				break;
			case 'day_of_week':
				$dow_list = orList( $bold_labels( $settings['run_on_dow'], $days_of_week ) );

				if ( isset( $determiners[ $settings['run_on_dow_type'] ] ) ) {
					/* translators: 1: the occurrence within the month, like "first", 2: a day of the week */
					$days = sprintf( _x( 'the %1$s %2$s', 'the - determiner - day of week', 'groundhogg' ), strtolower( $determiners[ $settings['run_on_dow_type'] ] ), $dow_list );
				} else {
					/* translators: %s: a day of the week */
					$days = sprintf( _x( 'any %s', 'any - day of the week', 'groundhogg' ), $dow_list );
				}

				/* translators: 1: a list of ordinal days of the month (1st, 2nd, 3rd, etc...), 2: a list of months (February, March, April) */
				$run = sprintf( _x( 'run on %1$s of %2$s', 'verb meaning to start on process - on a specific day of a specific month', 'groundhogg' ), $days, $months_list );
				break;
			case 'day_of_month':
				$doms = array_map( function ( $dom ) {
					return bold_it( $dom === 'last' ? __( 'last day', 'groundhogg' ) : ordinal_suffix( $dom ) );
				}, array_values( (array) $settings['run_on_dom'] ) );

				$days = empty( $doms )
					? bold_it( __( 'any day', 'groundhogg' ) )
					/* translators: %s: and ordinal day of the month (1st, 2nd, 3rd, etc...) */
					: sprintf( _x( 'the %s', 'the - ordinal day of month', 'groundhogg' ), orList( $doms ) );

				/* translators: 1: a list of ordinal days of the month (1st, 2nd, 3rd, etc...), 2: a list of months (February, March, April) */
				$run = sprintf( _x( 'run on %1$s of %2$s', 'verb meaning to start on process - on a specific day of a specific month', 'groundhogg' ), $days, $months_list );
				break;
		}

		array_unshift( $preview, $run );

		if ( $settings['delay_type'] !== 'none' ) {

			$amount = absint( $settings['delay_amount'] );

			switch ( $settings['delay_type'] ) {
				case 'minutes':
					$unit = _n( 'minute', 'minutes', $amount, 'groundhogg' );
					break;
				case 'hours':
					$unit = _n( 'hour', 'hours', $amount, 'groundhogg' );
					break;
				default:
				case 'days':
					$unit = _n( 'day', 'days', $amount, 'groundhogg' );
					break;
				case 'weeks':
					$unit = _n( 'week', 'weeks', $amount, 'groundhogg' );
					break;
				case 'months':
					$unit = _n( 'month', 'months', $amount, 'groundhogg' );
					break;
				case 'years':
					$unit = _n( 'year', 'years', $amount, 'groundhogg' );
					break;
			}

			/* translators: %s: a duration of time like "3 days" */
			array_unshift( $preview, sprintf( _x( 'Wait at least %s and then', 'wait for a duration', 'groundhogg' ), bold_it( $amount . ' ' . $unit ) ) );
		}

		return ucfirst( implode( ' ', $preview ) );
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
	 * @throws \Exception when it can't be worked out, see next_matching_day()
	 *
	 * @param Step $step
	 * @param int  $baseTimestamp
	 *
	 * @return int
	 */
	public function calc_run_time( int $baseTimestamp, Step $step ): int {

		$settings = $this->get_delay_settings( $step );

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
	 * If no day matches within 5 years it's the last day of the month for days of the month that no chosen month has,
	 * the 30th of February is the 28th or the 29th. Nothing else is a day that a timer is run on when it's set not to,
	 * so it throws. Days or months that are left empty aren't a restriction, that's any day or month.
	 *
	 * @param DateTimeHelper $date
	 * @param array          $settings
	 *
	 * @throws \RuntimeException when there's no day that matches
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
					return; // nothing selected is any day
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
					return; // nothing selected is any day
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
				return; // nothing selected is any month
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

		// Nothing matched, and for days of the month that's when every day chosen is after the last day of every month
		// chosen, like the 30th of February. Only then is it the last day of the month, in the first month that it's not
		// before the start, it's a month that was chosen and the closest day to what was chosen. Where there's a day that
		// does match it's not used, the 31st doesn't run in April when it runs in May.
		if ( $settings['run_on_type'] === 'day_of_month' ) {

			$date->setTimestamp( $start );
			[ $year, $month ] = array_map( 'intval', explode( '-', $date->format( 'Y-n' ) ) );

			for ( $i = 0; $i < 60; $i ++ ) {

				$date->setDate( $year, $month + $i, 1 );

				if ( $months && ! in_array( strtolower( $date->format( 'F' ) ), $months ) ) {
					continue;
				}

				$date->setDate( (int) $date->format( 'Y' ), (int) $date->format( 'n' ), (int) $date->format( 't' ) );
				$date->setTime( ...$time );

				if ( $date->getTimestamp() >= $start ) {
					return;
				}
			}
		}

		throw new \RuntimeException( __( 'No day in the next 5 years matches the days and months that this delay timer is set to run on.', 'groundhogg' ) );
	}
}
