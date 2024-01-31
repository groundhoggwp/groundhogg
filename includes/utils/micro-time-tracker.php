<?php

namespace Groundhogg\Utils;

class Micro_Time_Tracker {

	private $start;

	public function __construct() {
		$this->set_start();
	}

	public function set_start() {
		$this->start = microtime( true );
	}

	public function time_elapsed() {
		return microtime( true ) - $this->start;
	}

	public function time_elapsed_rounded( $precision = 2 ) {
		return number_format( $this->time_elapsed(), $precision );
	}

	public function show_time_elapsed( $precision = 2 ) {
		echo $this->time_elapsed_rounded( $precision ) . ' seconds, ';
	}

}
