<?php
namespace Groundhogg\Reporting\Reports;


use Groundhogg\Contact_Query;
use Groundhogg\DB\Meta_DB;
use Groundhogg\Event;
use function Groundhogg\get_db;
use Groundhogg\Plugin;
use Groundhogg\Reporting\Reporting;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */

class Emails_Sent extends Report
{

    /**
     * Get the report ID
     *
     * @return string
     */
    public function get_id()
    {
        return 'emails_sent';
    }

    /**
     * Get the report name
     *
     * @return string
     */
    public function get_name()
    {
        return __( 'Emails Sent', 'groundhogg' );
    }

    /**
     * Get the report data
     *
     * @return array
     */
    public function get_data()
    {
        global $wpdb;

        $events_table = get_db('events')->get_table_name();
        $steps_table  = get_db('steps')->get_table_name();

        $data = $wpdb->get_results( $wpdb->prepare(
            "SELECT e.*,s.step_type FROM $events_table e 
                        LEFT JOIN $steps_table s ON e.step_id = s.ID 
                        WHERE e.status = %s AND ( s.step_type = %s OR e.event_type = %d OR e.event_type = %d)
                        AND e.time >= %d AND e.time <= %d
                        ORDER BY time DESC"
            , 'complete', 'send_email', Event::BROADCAST, Event::EMAIL_NOTIFICATION,
            $this->get_start_time(), $this->get_end_time() )
        );

        return $data;
    }
}