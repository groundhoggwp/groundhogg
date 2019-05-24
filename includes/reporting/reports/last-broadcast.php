<?php
namespace Groundhogg\Reporting\Reports;


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

    /**
     * Get the report data
     *
     * @return array
     */
    public function get_data()
    {

        $all_broadcasts = get_db( 'broadcasts' )->query( [ 'status' => 'sent' ] );

        if ( empty( $all_broadcasts ) ){
            return [];
        }

        $last_broadcast = array_shift( $all_broadcasts );
        $last_broadcast_id = absint( $last_broadcast->ID );

        $total_sent = get_db( 'events' )->count( array(
            'event_type'    => Event::BROADCAST,
            'step_id'       => $last_broadcast_id,
            'status'        => Event::COMPLETE
        ) );

        $opens = get_db( 'activity' )->count( array(
            'step_id'       => $last_broadcast_id,
            'activity_type' => 'email_opened'
        ) );

        $unopened = $total_sent - $opens;

        $clicks = get_db( 'activity' )->count( array(
            'step_id'       => $last_broadcast_id,
            'activity_type' => 'email_link_click'
        ) );

        $unclicked = $opens - $clicks;

        return [
            'sent' => $total_sent,
            'opens' => $opens,
            'clicked' => $clicks,
            'unopened' => $unopened,
            'unclicked' => $unclicked
        ];
    }
}