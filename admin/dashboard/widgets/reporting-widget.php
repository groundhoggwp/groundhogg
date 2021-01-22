<?php

namespace Groundhogg\Admin\Dashboard\Widgets;

use Groundhogg\Plugin;
use Groundhogg\Reporting\Reports\Report;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */

abstract class Reporting_Widget extends Dashboard_Widget
{
    public function get_id()
    {
        return $this->get_report()->get_id();
    }

    public function get_name()
    {
        return $this->get_report()->get_name();
    }

    /**
     * Ge the report ID...
     *
     * @return string
     */
    abstract protected function get_report_id();

    /**
     * Get teh report
     *
     * @return Report|false
     */
    protected function get_report()
    {
        return Plugin::$instance->reporting->get_report( $this->get_report_id() );
    }

    /**
     * Format the data into a chart friendly format.
     *
     * @param $data array
     * @return array
     */
    abstract protected function normalize_data( $data );

    /**
     * @return array
     */
    protected function get_data()
    {
        return $this->normalize_data( $this->get_report()->get_data() );
    }

    /**
     * @return array
     */
    public function get_chart_data()
    {
        return $this->get_data();
    }
    
}