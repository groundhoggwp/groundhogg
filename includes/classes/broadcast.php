<?php
namespace Groundhogg;

use Groundhogg\DB\Broadcasts;
use Groundhogg\DB\DB;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Broadcast
 *
 * This is a simple class that inits a broadcast like object for easy use and manipulation.
 * Also contains some api methods for the event queue
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Broadcast extends Base_Object implements Event_Process
{

    const TYPE_SMS = 'sms';
    const TYPE_EMAIL = 'email';


    /**
     * Do any post setup actions.
     *
     * @return void
     */
    protected function post_setup()
    {
        // TODO: Implement post_setup() method.
    }

    /**
     * Return the DB instance that is associated with items of this type.
     *
     * @return Broadcasts
     */
    protected function get_db()
    {
        return Plugin::$instance->dbs->get_db( 'broadcasts' );
    }

    /**
     * A string to represent the object type
     *
     * @return string
     */
    protected function get_object_type()
    {
        return 'broadcast';
    }

    public function get_funnel_title()
    {
        return __( 'Broadcast Email', 'groundhogg' );
    }

    public function get_step_title()
    {
        return $this->get_title();
    }

    /**
	 * @return string
	 */
    public function get_broadcast_type()
    {
    	return $this->object_type;
    }

    /**
	 * Whether the broadcast is sending an sms
	 *
	 * @return bool
	 */
    public function is_sms()
    {
        return $this->get_broadcast_type() === self::TYPE_SMS;
    }

    /**
	 * Whether the broadcast is sending an email
	 *
	 * @return bool
	 */
    public function is_email()
    {
    	return $this->get_broadcast_type() === self::TYPE_EMAIL;
    }


    /**
	 * Get the column row title for the broadcast.
	 *
	 * @return string
	 */
    public function get_title()
    {

    	if ( ! $this->object->exists() ){
    		return __( '(The associated Email or SMS was deleted.)', 'groundhogg' );
	    }

    	if ( $this->is_sms() ){
    		return $this->sms->title;
	    } else {
    		return $this->email->subject;
	    }

    }

    /**
     * Setup the properties...
     *
     * @param $broadcast object
     */
    public function setup_broadcast( $broadcast )
    {

    	$this->object_type = isset( $broadcast->object_type ) && $broadcast->object_type === 'sms' ? 'sms' : 'email';
    	$this->object_id = intval( $broadcast->object_id );

    	if ( $this->object_type === 'email' ){
		    $this->email = new WPGH_Email( $this->object_id );
		    $this->object = $this->email;
	    } else {
		    $this->sms = new WPGH_SMS( $this->object_id );
		    $this->object = $this->sms;
	    }

	    $this->scheduled_by = intval( $broadcast->scheduled_by );
        $this->send_time    = intval( $broadcast->send_time );
        $this->tags         = maybe_unserialize( $broadcast->tags );
        $this->status       = $broadcast->status;
        $this->date_scheduled = $broadcast->date_scheduled;

    }

    /**
     * cancel the broadcast
     */
    public function cancel()
    {

        if ( WPGH()->events->mass_update(
            array(
                'status' => 'cancelled'
            ),
            array(
                'step_id'   => $this->ID,
                'funnel_id' => WPGH_BROADCAST
            )
        ) ){
            $this->update( array( 'status' => 'cancelled' ) );
        }

    }

    /**
     * Update info about the broadcast
     *
     * @param $args array of info
     * @return bool
     */
    public function update( $args )
    {
        $result = WPGH()->broadcasts->update( $this->ID, $args );

        if ( $result ){
            $broadcast = WPGH()->broadcasts->get( $this->ID );

            $this->setup_broadcast( $broadcast );
        }

        return $result;
    }

    /**
     * Send the associated object to the given contact
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return bool|WP_Error whether the email sent or not.
     */
    public function run( $contact, $event = null )
    {

	    do_action( "groundhogg/broadcast/{$this->object_type}/before", $this, $contact, $event );
        $result = $this->object->send( $contact, $event );
        do_action( "groundhogg/broadcast/{$this->object_type}/after", $this, $contact, $event );

        return $result;
    }

    /**
     * Just return true for now cuz I'm lazy...
     *
     * @return bool
     */
    public function can_run()
    {

        return true;

    }
}