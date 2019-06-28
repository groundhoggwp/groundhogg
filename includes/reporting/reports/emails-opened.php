<?php
namespace Groundhogg\Reporting\Reports;


use Groundhogg\Classes\Activity;
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

class Emails_Opened extends Report
{

    /**
     * Get the report ID
     *
     * @return string
     */
    public function get_id()
    {
        return 'emails_opened';
    }

    /**
     * Get the report name
     *
     * @return string
     */
    public function get_name()
    {
        return __( 'Emails Opened', 'groundhogg' );
    }

    /**
     * Get the report data
     *
     * @return array
     */
    public function get_data()
    {
        $db = get_db( 'activity' );

        $data = $db->query( [
            'activity_type' => Activity::EMAIL_OPENED,
            'before' => $this->get_end_time(),
            'after' => $this->get_start_time()
        ] );

        return $data;
    }
}