<?php
namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Contact_Query;
use Groundhogg\Event;
use function Groundhogg\get_request_query;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-04
 * Time: 3:22 PM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Broadcast_Scheduler extends Bulk_Job
{

    protected $config = [];
    protected $broadcast_id;
    protected $send_time;
    protected $send_now;
    protected $send_in_timezone;

    /**
     * The number of emails which have been scheduled so far.
     *
     * @var int
     */
    protected $emails_scheduled = 0;

    /**
     * The offset based on the total number of email scheduled.
     *
     * @var int
     */
    protected $send_time_offset = 0;

    /**
     * Get the action reference.
     *
     * @return string
     */
    function get_action(){
        return 'gh_schedule_broadcast';
    }

    /**
     * Get an array of items someway somehow
     *
     * @param $items array
     * @return array
     */
    public function query($items)
    {
        if ( ! current_user_can( 'schedule_broadcasts' ) ){
            return $items;
        }

        $query = new Contact_Query();
        $args = get_request_query();

        if ( empty( $args ) ){
            $config = get_transient('gh_get_broadcast_config');
            if ( $config ){
                $args = $config[ 'contact_query' ];
            }
        }

        $contacts = $query->query( $args );

        $ids = wp_list_pluck( $contacts, 'ID' );

        return $ids;
    }

    /**
     * Get the maximum number of items which can be processed at a time.
     *
     * @param $max int
     * @param $items array
     * @return int
     */
    public function max_items($max, $items)
    {
        if ( ! current_user_can( 'schedule_broadcasts' ) ){
            return $max;
        }

        $max = intval( ini_get( 'max_input_vars' ) );
        return min( $max, 100 );
    }

    /**
     * Process an item
     *
     * @param $item mixed
     * @return void
     */
    protected function process_item( $item )
    {
        if ( ! current_user_can('schedule_broadcasts') ) {
            return;
        }

        $id = absint( $item );

        $local_time = $this->get_send_time();

        if ( $this->send_in_timezone && ! $this->send_now ) {

            $contact = Plugin::$instance->utils->get_contact($id);
            $local_time = $contact->get_local_time_in_utc_0( $this->send_time );
            if ($local_time < time()) {
                $local_time += DAY_IN_SECONDS;
            }
        }

        $args = [
            'time'          => $local_time,
            'contact_id'    => $id,
            'funnel_id'     => 1,
            'step_id'       => $this->broadcast_id,
            'status'        => 'waiting',
            'event_type'    => Event::BROADCAST
        ];

        Plugin::$instance->dbs->get_db('events')->add($args);

        $this->emails_scheduled += 1;

        if ( $this->emails_scheduled % $this->get_max_emails_per_minute() === 0 ){
            $this->send_time_offset += MINUTE_IN_SECONDS;
        }
    }

    /**
     * @return int
     */
    protected function get_send_time()
    {
        return $this->send_time + $this->send_time_offset;
    }

    /**
     * Do stuff before the loop
     *
     * @return void
     */
    protected function pre_loop()
    {
        $config = get_transient('gh_get_broadcast_config');

        $config = wp_parse_args($config, [
            'broadcast_id' => 0,
            'send_time' => time(),
            'send_now' => false,
            'send_in_local_time' => false
        ]);

        $this->config = $config;

        $this->broadcast_id     = absint($config['broadcast_id']);

        $this->send_time        = absint($config['send_time']);
        $this->send_now         = filter_var($config['send_now'], FILTER_VALIDATE_BOOLEAN);
        $this->send_in_timezone = filter_var($config['send_in_local_time'], FILTER_VALIDATE_BOOLEAN);

        $this->send_time_offset = absint( get_transient('gh_send_time_offset' ) );
        $this->emails_scheduled = absint( get_transient('gh_emails_scheduled' ) );
    }

    /**
     * The maximum number of emails which can be scheduled within 1 minute.
     * A.k.a Email throttling
     *
     * @return int
     */
    protected function get_max_emails_per_minute()
    {
        return apply_filters( 'groundhogg/broadcasts/max_per_minute', 500 );
    }

    /**
     * do stuff after the loop
     *
     * @return void
     */
    protected function post_loop()
    {
        set_transient( 'gh_emails_scheduled', $this->emails_scheduled, HOUR_IN_SECONDS );
        set_transient( 'gh_send_time_offset', $this->send_time_offset, HOUR_IN_SECONDS );
    }

    protected function send_response($response)
    {
        $response[ 'total' ]  = $this->emails_scheduled;
        $response[ 'offset' ] = $this->send_time_offset;

        parent::send_response($response);
    }

    /**
     * Cleanup any options/transients/notices after the bulk job has been processed.
     *
     * @return void
     */
    protected function clean_up()
    {
        delete_transient( 'gh_get_broadcast_config' );
        delete_transient( 'gh_send_time_offset' );
        delete_transient( 'gh_emails_scheduled' );
    }

    /**
     * Get the return URL
     *
     * @return string
     */
    protected function get_return_url()
    {
        $url = admin_url( 'admin.php?page=gh_broadcasts' );
        return $url;
    }

    protected function get_finished_notice()
    {
        return _x( 'Broadcast scheduled!', 'notice', 'groundhogg' );
    }
}