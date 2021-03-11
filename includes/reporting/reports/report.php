<?php

namespace Groundhogg\Reporting\Reports;


use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */

/**
 *
 * Reports shall be based on a simple table structure...
 * Rows
 * Columns
 *
 * Class Report
 * @package Groundhogg\Reporting\Reports
 */
abstract class Report {

	protected $get_from_previous_period_flag = false;

	/**
	 * Get the report ID
	 *
	 * @return string
	 */
	abstract public function get_id();

	/**
	 * Get the report name
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Get the report data
	 *
	 * @return array
	 */
	abstract public function get_data();

	/**
	 * @param $bool
	 */
	public function set_get_from_previous( $bool ) {
		$this->get_from_previous_period_flag = (bool) $bool;
	}

	/**
	 * @return bool
	 */
	public function is_getting_from_previous() {
		return $this->get_from_previous_period_flag;
	}

	public function get_previous_period_data() {
		$this->set_get_from_previous( true );

		$data = $this->get_data();

		$this->set_get_from_previous( false );

		return $data;
	}

	/**
	 * @return int
	 */
	public function get_start_time() {
		$start = Plugin::$instance->reporting->get_start_time();
		$end   = Plugin::$instance->reporting->get_end_time();

		if ( $this->is_getting_from_previous() ) {
			$diff  = $end - $start;
			$start -= $diff;
		}

		return $start;
	}

	/**
	 * @return int
	 */
	public function get_end_time() {
		$start = Plugin::$instance->reporting->get_start_time();
		$end   = Plugin::$instance->reporting->get_end_time();

		if ( $this->is_getting_from_previous() ) {
			$diff = $end - $start;
			$end  -= $diff;
		}

		return $end;
	}

	/**
	 * @return int
	 */
	public function get_difference() {
		return Plugin::$instance->reporting->get_difference();
	}

	/**
	 * @return int
	 */
	public function get_points() {
		return Plugin::$instance->reporting->get_points();
	}

}