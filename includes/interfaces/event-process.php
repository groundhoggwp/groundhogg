<?php
namespace Groundhogg;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-03-04
 * Time: 9:00 AM
 */

interface Event_Process
{
    public function get_funnel_title();
    public function get_step_title();

    /**
     * @param $contact Contact
     * @param $event
     * @return mixed
     */
    public function run( $contact, $event );

    /**
     * @return bool
     */
    public function can_run();
}