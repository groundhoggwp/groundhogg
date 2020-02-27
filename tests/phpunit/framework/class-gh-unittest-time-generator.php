<?php

class GH_UnitTest_Time_Generator {

	protected $start;
	protected $end;
	protected $format;

	public function __construct( $format = 'U', $start = 0, $end = 0 ) {

		if ( is_string( $start ) ) {
			$start = strtotime( $start );
		}

		if ( is_string( $end ) ) {
			$end = strtotime( $end );
		}

		if ( ! $start ){
			$start = time() - WEEK_IN_SECONDS;
		}

		if ( ! $end ){
			$end = time();
		}

		$this->start  = $start;
		$this->end    = $end;
		$this->format = $format;

	}

	/**
	 * Return the next int.
	 *
	 * @return int
	 */
	public function generate() {

		if ( $this->format === 'U' ){
			return rand( $this->start, $this->end );
		}

		return date( $this->format, rand( $this->start, $this->end ) );
	}

}
