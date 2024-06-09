<?php

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
