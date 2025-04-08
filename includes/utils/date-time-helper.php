<?php

namespace Groundhogg\Utils;

use function Groundhogg\get_date_time_format;
use function Groundhogg\get_time_format;

class DateTimeHelper extends \DateTime {

	public function __construct( $datetime = 'now', \DateTimeZone $timezone = null ) {

		if ( ! $timezone ) {
			$timezone = wp_timezone();
		}

		$timestamp = false;

		if ( is_int( $datetime ) ) {
			$timestamp = $datetime;
			$datetime  = 'now';
		}

		parent::__construct( $datetime, $timezone );

		if ( $timestamp ) {
			$this->setTimestamp( $timestamp );
		}
	}

	public function whenIs() {

		$compare   = new DateTimeHelper( 'today' );
		$timestamp = $this->getTimestamp();
		$this->setTime( 0, 0, 0 );
		$interval = $compare->diff( $this );
		$this->setTimestamp( $timestamp );

		if ( $interval->days == 0 ) {
			return 'today';
		} else if ( $interval->days == 1 ) {
			if ( $interval->invert == 0 ) {
				return 'tomorrow';
			} else {
				return 'yesterday';
			}
		}

		return false;
	}

	public function isToday() {
		return $this->whenIs() === 'today';
	}

	public function isTomorrow() {
		return $this->whenIs() === 'tomorrow';
	}

	public function isYesterday() {
		return $this->whenIs() === 'yesterday';
	}

	public function ymdhis() {
		return $this->format( 'Y-m-d H:i:s' );
	}

	public function mysql() {
		return $this->ymdhis();
	}

	public function unix() {
		return $this->getTimestamp();
	}

	public function ymd() {
		return $this->format( 'Y-m-d' );
	}

	public function wpDateTimeFormat() {
		return $this->format( get_date_time_format() );
	}

	public function wpDateFormat() {
		return $this->format( get_option( 'date_format' ) );
	}

	public function wpTimeFormat() {
		return $this->format( get_option( 'time_format' ) );
	}

	public function human_time_diff( $time = 0 ) {

		if ( ! is_int( $time ) && is_object( $time ) && method_exists( $time, 'getTimestamp' ) ) {
			$time = $time->getTimestamp();
		}

		return human_time_diff( $this->getTimestamp(), $time ?: time() );
	}

	/**
	 * Whether the current date represents a leap year
	 *
	 * @return bool
	 */
	public function isLeapYear() {
		return absint( $this->format( 'L' ) ) === 1;
	}

	public function i18n() {

		switch ( $this->whenIs() ) {
			case 'today':
				return sprintf( __( 'today at %s', 'groundhogg' ), $this->format( get_time_format() ) );
			case 'tomorrow':
				return sprintf( __( 'tomorrow at %s', 'groundhogg' ), $this->format( get_time_format() ) );
			case 'yesterday':
				return sprintf( __( 'yesterday at %s', 'groundhogg' ), $this->format( get_time_format() ) );
			default:
				return $this->wpDateTimeFormat();
		}

	}

	/**
	 * Sets the year to the current year based on either a base timestamp or the current time
	 *
	 * @throws \DateMalformedStringException
	 *
	 * @param int $baseTimestamp the base timestamp to use
	 *
	 * @return DateTimeHelper
	 */
	public function setToCurrentYear( $baseTimestamp = 0 ) {

		if ( $baseTimestamp === 0 ) {
			$baseTimestamp = time();
		}

		$now = new DateTimeHelper( $baseTimestamp );

		// already current year
		if ( $now->format('Y') === $this->format('Y') ) {
			return $this;
		}

		$this->setDate( (int) $now->format( 'Y' ), (int) $this->format( 'm' ), (int) $this->format( 'd' ) );

		return $this;
	}

	public function isPast() {
		return $this->getTimestamp() < time();
	}

	public function isFuture() {
		return $this->getTimestamp() > time();
	}

	public function isNow() {
		return $this->getTimestamp() === time();
	}

	public function isBefore( \DateTime $before ) {
		return $this < $before;
	}

	public function isAfter( \DateTime $after ) {
		return $this > $after;
	}

	public function isBetween( \DateTime $after, \DateTime $before ) {
		return $after < $this && $this < $before;
	}
}
