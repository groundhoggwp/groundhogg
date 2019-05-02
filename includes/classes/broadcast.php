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
     * @var SMS|Email
     */
    protected $object;

    /**
     * Do any post setup actions.
     *
     * @return void
     */
    protected function post_setup()
    {
        switch ( $this->get_broadcast_type() ) {
            case self::TYPE_EMAIL:
                $this->object = Plugin::$instance->utils->get_email( $this->get_object_id() );
                break;
            case self::TYPE_SMS:
                $this->object = Plugin::$instance->utils->get_sms( $this->get_object_id() );
                break;
        }
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
     * @return int
     */
    protected function get_id()
    {
        return absint( $this->ID );
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

    /**
     * @return string|void
     */
    public function get_funnel_title()
    {
        return __( 'Broadcast Email', 'groundhogg' );
    }

    /**
     * @return string
     */
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
     * @return int
     */
    public function get_object_id()
    {
        return absint( $this->object_id );
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
     * @return Email|SMS|null
     */
    public function get_object()
    {
        return $this->object;
    }


    /**
	 * Get the column row title for the broadcast.
	 *
	 * @return string
	 */
    public function get_title()
    {

    	if ( ! $this->get_object() || ! $this->get_object()->exists() ){
    		return __( '(The associated Email or SMS was deleted.)', 'groundhogg' );
	    }

    	return $this->get_object()->get_title();

    }

    /**
     * Cancel the broadcast
     */
    public function cancel()
    {

        if ( Plugin::$instance->dbs->get_db( 'events' )->mass_update(
            [
                'status' => Event::CANCELLED
            ],
            [
                'step_id'   => $this->get_id(),
                'event_type' => Event::BROADCAST
            ]
        ) ){
            $this->update( [ 'status' => 'cancelled' ] );
        }

    }

    /**
     * Send the associated object to the given contact
     *
     * @param $contact Contact
     * @param $event Event
     *
     * @return bool|\WP_Error whether the email sent or not.
     */
    public function run( $contact, $event = null )
    {

	    do_action( "groundhogg/broadcast/{$this->get_broadcast_type()}/before", $this, $contact, $event );
        $result = $this->get_object()->send( $contact, $event );
        do_action( "groundhogg/broadcast/{$this->get_broadcast_type()}/after", $this, $contact, $event );

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