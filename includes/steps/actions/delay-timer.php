<?php
namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Delay Timer
 *
 * This allows the adition of an event which "does nothing" but runs at the specified time according to the time provided.
 * Essentially delaying proceeding events.
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
class Delay_Timer extends Action
{
    /**
     * @return string
     */
    public function get_help_article()
    {
        return 'https://docs.groundhogg.io/docs/builder/actions/delay-timer/';
    }

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Delay Timer', 'step_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'delay_timer';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Pause for the specified amount of time.', 'step_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/delay-timer.png';
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {

        $html = Plugin::$instance->utils->html;

        $html->start_form_table();

        $html->start_row();

        $html->th( __( 'Wait at least:', 'groundhogg' ) );

        $html->td( [
            // DELAY AMOUNT
            $html->number( [
                'class'         => 'input',
                'name'          => $this->setting_name_prefix( 'delay_amount' ),
                'id'            => $this->setting_id_prefix( 'delay_amount' ),
                'value'         => $this->get_setting( 'delay_amount', 3 ),
                'placeholder'   => 3,
            ] ),
            // DELAY TYPE
            $html->dropdown( [
                'name'          => $this->setting_name_prefix( 'delay_type' ),
                'id'            => $this->setting_id_prefix( 'delay_type' ),
                'options'       => [
                    'minutes'   => __( 'Minutes' ),
                    'hours'     => __( 'Hours' ),
                    'days'      => __( 'Days' ),
                    'weeks'     => __( 'Weeks' ),
                    'months'    => __( 'Months' ),
                ],
                'selected'      => $this->get_setting( 'delay_type', 'minutes' ),
                'option_none'   => false,
            ] ),
        ] );

        $html->end_row();
        $html->start_row();

        $html->th( __( 'And run:', 'groundhogg' ) );

        $html->td( [
            // RUN WHEN
            $html->dropdown( [
                'name'          => $this->setting_name_prefix( 'run_when' ),
                'id'            => $this->setting_id_prefix( 'run_when' ),
                'class'         => 'run_when',
                'options'       => [
                    'now'   => __( 'Immediately', 'groundhogg' ),
                    'later' => __( 'At time of day', 'groundhogg' ),
                ],
                'selected'      => $this->get_setting( 'run_when', 'now' ),
                'option_none'   => false,
            ] ),
            // RUN TIME
            $html->input( [
                'type'  => 'time',
                'class' => ( 'now' === $this->get_setting( 'run_when', 'now' ) ) ? 'input run_time hidden' : 'run_time input',
                'name'  => $this->setting_name_prefix( 'run_time' ),
                'id'    => $this->setting_id_prefix(   'run_time' ),
                'value' => $this->get_setting( 'run_time', "09:00:00" ),
            ] ),
            // LOCAL TIME
            $html->wrap(
                $html->checkbox( [
                    'label'         => _x( "Run in the contact's local time.", 'action', 'groundhogg' ),
                    'name'          => $this->setting_name_prefix( 'send_in_timezone' ),
                    'id'            => $this->setting_id_prefix(   'send_in_timezone' ),
                    'value'         => '1',
                    'checked'       => (bool) $this->get_setting( 'send_in_timezone' ),
                    'title'         => __( "Run in the contact's local time.", 'groundhogg' ),
                    'required'      => false,
                ] ),
                'div',
                [
                    'id' => $this->setting_id_prefix( 'local_time_div' )
                ]
            )
        ] );

        $html->end_row();
        $html->end_form_table();
    }

    /**
     * Save the step settings
     *
     * @param $step Step
     */
    public function save( $step )
    {
        $this->save_setting( 'delay_amount', absint( $this->get_posted_data( 'delay_amount' ) ) );
        $this->save_setting( 'delay_type', sanitize_text_field( $this->get_posted_data( 'delay_type' ) ) );
        $this->save_setting( 'run_when', sanitize_text_field( $this->get_posted_data( 'run_when' ) ) );
        $this->save_setting( 'run_time', sanitize_text_field( $this->get_posted_data( 'run_time' ) ) );

        $send_in_timezone = $this->get_posted_data( 'send_in_timezone', false );
        $this->save_setting( 'send_in_timezone', (bool) $send_in_timezone );
    }

    /**
     * Override the parent and set the run time of this function to the settings
     *
     * @param Step $step
     * @return int
     */
    public function enqueue( $step )
    {

        $amount     = absint( $this->get_setting( 'delay_amount' ) );
        $type       = $this->get_setting( 'delay_type' );
        $run_time   = $this->get_setting( 'run_time', '09:00:00' );
        $run_when   = $this->get_setting( 'run_when', 'now' );
        $send_in_timezone = $this->get_setting( 'send_in_timezone', false );

        if ( $run_when == 'now' ){
            $time_string = '+ ' . $amount . ' ' . $type;
            $final_time = strtotime( $time_string );
        } else {
            $time_string = '+ ' . $amount . ' ' . $type;
            $base_time = strtotime( $time_string );
            $formatted_date = date( 'Y-m-d', $base_time );
            $time_string = $formatted_date . ' ' . $run_time;
            if ( strtotime( $time_string ) < time() ){
                $formatted_date = date( 'Y-m-d', strtotime( 'tomorrow' ) );
                $time_string = $formatted_date . ' ' . $run_time;
            }

            /* convert to utc */
            $final_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $time_string ) );

            /* Modify according to the contacts timezone */
            if ( $send_in_timezone && Plugin::$instance->event_queue::is_processing()  ){
                $final_time = Plugin::$instance->event_queue->get_current_contact()->get_local_time_in_utc_0( $final_time );
                if ( $final_time < time() ){
                    $final_time+=DAY_IN_SECONDS;
                }
            }
        }

        return $final_time;
    }
    /**
     * Process the apply tag step...
     *
     * @param $contact Contact
     * @param $event Event
     *
     * @return true
     */
    public function run( $contact, $event )
    {
        //do nothing
        return true;
    }
}