<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-04
 * Time: 3:22 PM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WPGH_Bulk_Job' ) ){
    include WPGH_PLUGIN_DIR. 'includes/class-wpgh-bulk-job.php';
}

class WPGH_Broadcast_Bulk_Job extends WPGH_Bulk_Job
{

    protected $config = [];
    protected $broadcast_id;
    protected $send_time;
    protected $send_now;
    protected $send_in_timezone;

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

        $query = new WPGH_Contact_Query();
        $args = $_GET;

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
        $local_time = $this->send_time;

        if ( $this->send_in_timezone && ! $this->send_now ) {
            $contact = wpgh_get_contact( $id );
            $local_time = $contact->get_local_time_in_utc_0( $this->send_time );
            if ($local_time < time()) {
                $local_time += DAY_IN_SECONDS;
            }
        }

        $args = [
            'time'          => $local_time,
            'contact_id'    => $id,
            'funnel_id'     => WPGH_BROADCAST,
            'step_id'       => $this->broadcast_id,
            'status'        => 'waiting',
            'event_type'    => WPGH_BROADCAST_EVENT
        ];

        WPGH()->events->add($args);
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
        $this->send_time        = intval($config['send_time']);
        $this->send_now         = filter_var($config['send_now'], FILTER_VALIDATE_BOOLEAN);
        $this->send_in_timezone = filter_var($config['send_in_local_time'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * do stuff after the loop
     *
     * @return void
     */
    protected function post_loop(){}

    /**
     * Cleanup any options/transients/notices after the bulk job has been processed.
     *
     * @return void
     */
    protected function clean_up()
    {
        wpgh_delete_transient( 'gh_get_broadcast_config' );
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