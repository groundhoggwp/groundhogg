<?php
namespace Groundhogg\Reporting\Reports;


use Groundhogg\Broadcast;
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

class Last_Broadcast extends Report
{
    /**
     * Get the report ID
     *
     * @return string
     */
    public function get_id()
    {
        return 'last_broadcast';
    }

    /**
     * Get the report name
     *
     * @return string
     */
    public function get_name()
    {
        return __( 'Last Broadcast', 'groundhogg' );
    }

    public function get_broadcast()
    {
        $all_broadcasts = get_db( 'broadcasts' )->query( [ 'status' => 'sent' ], 'send_time' );

        if ( empty( $all_broadcasts ) ){
            return false;
        }

        $last_broadcast = array_pop( $all_broadcasts );
        $last_broadcast_id = absint( $last_broadcast->ID );

        $broadcast = new Broadcast( $last_broadcast_id );

        return $broadcast;
    }

    /**
     * Get the report data
     *
     * @return array
     */
    public function get_data()
    {

        $broadcast = $this->get_broadcast();

        if ( $broadcast && $broadcast->exists() ){
            return $broadcast->get_report_data();
        }

        return [];
    }
}