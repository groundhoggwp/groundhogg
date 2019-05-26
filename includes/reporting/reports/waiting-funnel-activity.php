<?php
namespace Groundhogg\Reporting\Reports;


use Groundhogg\Contact_Query;
use Groundhogg\DB\Meta_DB;
use Groundhogg\Event;
use Groundhogg\Funnel;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use Groundhogg\Reporting\Reporting;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */

class Waiting_Funnel_Activity extends Report
{

    /**
     * Get the report ID
     *
     * @return string
     */
    public function get_id()
    {
        return 'waiting_funnel_activity';
    }

    /**
     * Get the report name
     *
     * @return string
     */
    public function get_name()
    {
        return __( 'Waiting Funnel Activity', 'groundhogg' );
    }

    /**
     * @return int
     */
    public function get_funnel_id()
    {
        $funnels = get_db( 'funnels' );
        $funnels = $funnels->query(['status' => 'active']);
        $funnel = array_shift( $funnels );
        $default_funnel_id = absint( $funnel->ID );
        return absint( get_request_var( 'funnel', $default_funnel_id ) );
    }

    /**
     * Get the report data
     *
     * @return array
     */
    public function get_data()
    {
        $funnel = new Funnel( $this->get_funnel_id() );
        $steps = $funnel->get_steps();
        $dataset = [];

        foreach ( $steps as $i => $step ) {
            $query = new Contact_Query();
            $args = array(
                'report' => array(
                    'funnel' => $funnel->get_id(),
                    'step'   => $step->get_id(),
                    'status' => 'waiting',
                )
            );
            $count = count($query->query($args));
            $dataset[] = array( ( $i + 1 ) .'. '. $step->get_title(), $count );
        }

        return $dataset;
    }
}