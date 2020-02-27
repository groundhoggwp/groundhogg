<?php

class GH_UnitTest_ID_Generator {

	static $incr = -1;
	public $next;

	public function __construct( $start = 1 ) {
		if ( $start ) {
			$this->next = $start;
		} else {
			self::$incr++;
			$this->next = self::$incr;
		}
	}

	public function next() {
		$generated = $this->next;
		$this->next++;
		return $generated;
	}

	/**
	 * Return the next int.
	 *
	 * @return int
	 */
	public function generate() {
		return $this->next();
	}

}
