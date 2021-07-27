<?php

namespace Groundhogg\Utils;

use function Groundhogg\micro_seconds;

class Limits {

	/**
	 * @var int
	 */
	protected static $start_time = 0;
	protected static $processed_actions = 0;
	protected static $total_processed_actions = 0;
	protected static $total_time_elapsed = 0;
	protected static $limits_exceeded = false;


	/**
	 * Start the function to prevent limits exceeding.
	 */
	public static function start() {

		// Only set once.
		if ( ! self::$start_time ) {
			self::$start_time = microtime( true );
		}

		return self::$start_time;
	}

	/**
	 * Store the elapsed time and reset variables.
	 */
	public static function stop() {

		self::$total_time_elapsed += self::time_elapsed();

		self::$processed_actions = 0;
		self::$start_time        = 0;
	}

	/**
	 * Increment the process actions variable.
	 */
	public static function processed_action() {
		self::$processed_actions ++;
		self::$total_processed_actions ++;
	}

	/**
	 * The total time since limits was started.
	 *
	 * @return float|int
	 */
	public static function total_time_elapsed() {
		return self::$total_time_elapsed + self::time_elapsed();
	}


	/**
	 * Return the time elapsed from the moment Limits was started.
	 *
	 * @return float|int|string
	 */
	public static function time_elapsed() {

		if ( ! self::$start_time ) {
			return 0;
		}

		return microtime( true ) - self::$start_time;
	}

	/**
	 * Converts a shorthand byte value to an integer byte value.
	 *
	 * Wrapper for wp_convert_hr_to_bytes(), moved to load.php in WordPress 4.6 from media.php
	 *
	 * @link https://secure.php.net/manual/en/function.ini-get.php
	 * @link https://secure.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
	 *
	 * @param string $value A (PHP ini) byte value, either shorthand or ordinary.
	 *
	 * @return int An integer byte value.
	 */
	public static function convert_hr_to_bytes( $value ) {
		if ( function_exists( 'wp_convert_hr_to_bytes' ) ) {
			return wp_convert_hr_to_bytes( $value );
		}

		$value = strtolower( trim( $value ) );
		$bytes = (int) $value;

		if ( false !== strpos( $value, 'g' ) ) {
			$bytes *= GB_IN_BYTES;
		} elseif ( false !== strpos( $value, 'm' ) ) {
			$bytes *= MB_IN_BYTES;
		} elseif ( false !== strpos( $value, 'k' ) ) {
			$bytes *= KB_IN_BYTES;
		}

		// Deal with large (float) values which run into the maximum integer size.
		return min( $bytes, PHP_INT_MAX );
	}

	/**
	 * Attempts to raise the PHP memory limit for memory intensive processes.
	 *
	 * Only allows raising the existing limit and prevents lowering it.
	 *
	 * Wrapper for wp_raise_memory_limit(), added in WordPress v4.6.0
	 *
	 * @return bool|int|string The limit that was set or false on failure.
	 */
	public static function raise_memory_limit() {
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			return wp_raise_memory_limit( 'admin' );
		}

		$current_limit     = @ini_get( 'memory_limit' );
		$current_limit_int = self::convert_hr_to_bytes( $current_limit );

		if ( - 1 === $current_limit_int ) {
			return false;
		}

		$wp_max_limit       = WP_MAX_MEMORY_LIMIT;
		$wp_max_limit_int   = self::convert_hr_to_bytes( $wp_max_limit );
		$filtered_limit     = apply_filters( 'admin_memory_limit', $wp_max_limit );
		$filtered_limit_int = self::convert_hr_to_bytes( $filtered_limit );

		if ( - 1 === $filtered_limit_int || ( $filtered_limit_int > $wp_max_limit_int && $filtered_limit_int > $current_limit_int ) ) {
			if ( false !== @ini_set( 'memory_limit', $filtered_limit ) ) {
				return $filtered_limit;
			} else {
				return false;
			}
		} elseif ( - 1 === $wp_max_limit_int || $wp_max_limit_int > $current_limit_int ) {
			if ( false !== @ini_set( 'memory_limit', $wp_max_limit ) ) {
				return $wp_max_limit;
			} else {
				return false;
			}
		}

		return false;
	}

	/**
	 * Attempts to raise the PHP timeout for time intensive processes.
	 *
	 * Only allows raising the existing limit and prevents lowering it. Wrapper for wc_set_time_limit(), when available.
	 *
	 * @param int The time limit in seconds.
	 */
	public static function raise_time_limit( $limit = 0 ) {

		if ( ! $limit ) {
			$limit = self::get_time_limit();
		}

		if ( $limit < ini_get( 'max_execution_time' ) ) {
			return;
		}

		if ( function_exists( 'wc_set_time_limit' ) ) {
			wc_set_time_limit( $limit );
		} elseif ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) ) {
			@set_time_limit( $limit );
		}
	}

	/**
	 * Get the number of seconds the process has been running.
	 *
	 * @return int The number of seconds.
	 */
	public static function get_execution_time() {

		// Sum up all the process if self::stop() is used.
		$execution_time = self::total_time_elapsed();

		// Get the CPU time if the hosting environment uses it rather than wall-clock time to calculate a process's execution time.
		if ( function_exists( 'getrusage' ) && apply_filters( 'action_scheduler_use_cpu_execution_time', defined( 'PANTHEON_ENVIRONMENT' ) ) ) {
			$resource_usages = getrusage();

			if ( isset( $resource_usages['ru_stime.tv_usec'], $resource_usages['ru_stime.tv_usec'] ) ) {
				$execution_time = $resource_usages['ru_stime.tv_sec'] + ( $resource_usages['ru_stime.tv_usec'] / 1000000 );
			}
		}

		return $execution_time;
	}


	/**
	 * Check if the host's max execution time is (likely) to be exceeded if processing more actions.
	 *
	 * @return bool
	 */
	public static function time_likely_to_be_exceeded() {

		if ( ! self::$total_processed_actions || self::get_time_limit() === 0 ) {
			return false;
		}

		$execution_time     = self::get_execution_time();
		$max_execution_time = self::get_time_limit();
		$time_per_action    = $execution_time / self::$total_processed_actions ?: 1;
		$estimated_time     = $execution_time + ( $time_per_action * 3 );

		return $estimated_time > $max_execution_time;
	}

	/**
	 * The time we have in seconds to process the queue.
	 *
	 * @return float|int
	 */
	public static function get_time_limit() {

		$real_time_limit = absint( ini_get( 'max_execution_time' ) );

		if ( $real_time_limit === 0 ){
			return MINUTE_IN_SECONDS;
		}

		return min( MINUTE_IN_SECONDS, absint( ini_get( 'max_execution_time' ) ) );
	}

	/**
	 * Get memory limit
	 *
	 * Based on WP_Background_Process::get_memory_limit()
	 *
	 * @return int
	 */
	public static function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			$memory_limit = '128M'; // Sensible default, and minimum required by WooCommerce
		}

		if ( ! $memory_limit || - 1 === $memory_limit || '-1' === $memory_limit ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32G';
		}

		return self::convert_hr_to_bytes( $memory_limit );
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90% of the maximum WordPress memory.
	 *
	 * Based on WP_Background_Process::memory_exceeded()
	 *
	 * @return bool
	 */
	public static function memory_exceeded() {

		$memory_limit   = self::get_memory_limit() * 0.90;
		$current_memory = memory_get_usage( true );

		return $current_memory >= $memory_limit;
	}

	/**
	 * See if the batch limits have been exceeded, which is when memory usage is almost at
	 * the maximum limit, or the time to process more actions will exceed the max time limit.
	 *
	 * Based on WC_Background_Process::batch_limits_exceeded()
	 *
	 * @param int $processed_actions The number of actions processed so far - used to determine the likelihood of exceeding the time limit if processing another action
	 *
	 * @return bool
	 */
	public static function limits_exceeded( $processed_actions = 0 ) {

		if ( self::$limits_exceeded ) {
			return true;
		}

		if ( $processed_actions ) {
			self::$processed_actions = $processed_actions;
		}

		// check if doing unit tests.
		if ( ( defined( 'DOING_GROUNDHOGG_TESTS' ) && DOING_GROUNDHOGG_TESTS ) ) {
			return false;
		}

		self::$limits_exceeded = self::memory_exceeded() || self::time_likely_to_be_exceeded();

		return self::$limits_exceeded;
	}

}
