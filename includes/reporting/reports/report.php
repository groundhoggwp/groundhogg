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
abstract class Report
{

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
     * @return int
     */
    public function get_start_time()
    {
        return Plugin::$instance->reporting->get_start_time();
    }

    /**
     * @return int
     */
    public function get_end_time()
    {
        return Plugin::$instance->reporting->get_end_time();
    }

}