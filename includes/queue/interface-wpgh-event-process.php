<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-03-04
 * Time: 9:00 AM
 */

interface WPGH_Event_Process
{
    /**
     * WPGH_Event_Process constructor.
     * @param $id int
     */
    public function __construct( $id );

    /**
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     * @return mixed
     */
    public function run( $contact, $event );

    /**
     * @return bool
     */
    public function can_run();
}