<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'array_is_list' ) ) {

	/**
	 * Returns true of the array is sequentially ordered
	 *
	 * @param array $array
	 *
	 * @return bool true if items are ordered sequentially. Otherwise, false.
	 */
	function array_is_list( array $array ): bool {
		$i = - 1;
		foreach ( $array as $k => $v ) {
			++ $i;
			if ( $k !== $i ) {
				return false;
			}
		}
		return true;
	}
}

// PHP 8.3 introduced these date exceptions. Broadcast::schedule() throws DateException, and
// callers catch it, so define them on older versions. Must stay in the global namespace.
if ( ! class_exists( 'DateException', false ) ) {
	class DateException extends Exception {}
}

if ( ! class_exists( 'DateMalformedStringException', false ) ) {
	class DateMalformedStringException extends DateException {}
}
