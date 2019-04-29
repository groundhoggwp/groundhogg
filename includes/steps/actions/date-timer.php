<?php
namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Date Timer
 *
 * This allows the adition of an event which "does nothing" but runs at the specified time according to the date provided.
 * Essentially delaying proceeding events.
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
class Date_Timer extends Action
{

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Date Timer', 'element_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'date_timer';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Pause until a specific date & time.', 'element_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/date-timer.png';
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {

        $html = Plugin::$instance->utils->html;

        $html->start_form_table();

        $html->start_row();

        $html->th( __( 'Wait till:', 'groundhogg' ) );

        $run_date_args = [
            'class'         => 'input',
            'name'          => $this->setting_name_prefix( 'run_date' ),
            'id'            => $this->setting_id_prefix( 'run_date' ),
            'value'         => $this->get_setting( 'run_date', date( 'Y-m-d', strtotime( '+3 days' ) ) ),
            'placeholder'   => 'yyy-mm-dd',
        ];

        $run_date = $html->date_picker( $run_date_args );

        $run_time_args = [
            'type'  => 'time',
            'class' => 'input',
            'name'  => $this->setting_name_prefix( 'run_time' ),
            'id'    => $this->setting_id_prefix(   'run_time' ),
            'value' => $this->get_setting( 'run_time', "09:00:00" ),
        ];

        $run_time = $html->input( $run_time_args );

        $local_time_args = [
            'label'         => _x( "Run in the contact's local time.", 'action', 'groundhogg' ),
            'name'          => $this->setting_name_prefix( 'send_in_timezone' ),
            'id'            => $this->setting_id_prefix(   'send_in_timezone' ),
            'value'         => '1',
            'checked'       => $step->get_meta( 'send_in_timezone' ),
            'title'         => __( 'Run in the contact\'s local time.', 'groundhogg' ),
            'required'      => false,
        ];

        $local_time = $html->wrap( $html->checkbox( $local_time_args ), 'div', [ 'id' => $this->setting_id_prefix( 'local_time_div' ) ] );

        $td_content = $run_date . $run_time . $local_time;

        $html->td( $td_content );

        $html->end_row();

        $html->end_form_table();
    }

    /**
     * Compare timers to see if one which date comes first compared to the order of appearance
     *
     * @param $step1 Step
     * @param $step2 Step
     *
     * @return int;
     */
    public function compare_timer( $step1, $step2 )
    {

        $step1_run_time = $this->enqueue( $step1 );
        $step2_run_time = $this->enqueue( $step2 );

        return $step2_run_time - $step1_run_time;

    }

    /**
     * Save the step settings
     *
     * @param $step Step
     */
    public function save( $step )
    {
        $this->save_setting( 'run_date', strtotime( 'Y-m-d', $this->get_posted_data( 'run_date' ) ) );
        $this->save_setting( 'run_time', sanitize_text_field( $this->get_posted_data( 'run_date' ) ) );

        $send_in_timezone = $this->get_posted_data( 'send_in_timezone', false );
        $this->save_setting( 'send_in_timezone', (bool) $send_in_timezone );

        $other_timers = $this->get_like_steps( [ 'funnel_id' => $step->get_funnel_id() ] );

        foreach ( $other_timers as $date_timer ){
            if ( $date_timer->get_order() < $step->get_order() && $this->compare_timer( $date_timer, $step ) < 0 ){
                Plugin::$instance->notices->add( 'timer-error', sprintf( __( 'You have date timers with descending dates! Your funnel may not work as expected. See <a href="#%d">%s</a>! Timers with dates in the past will run immediately.' ), $step->get_id(), $step->get_title() ), 'warning' );
            }
        }

        if ( $this->enqueue( $step ) < time() ){
            Plugin::$instance->notices->add( 'timer-error', sprintf( __( 'You have date timers with dates in the past! Your funnel may not work as expected. See <a href="#%d">%s</a>! Timers with dates in the past will run immediately.' ), $step->get_id(), $step->get_title() ), 'warning' );
        }

    }

    /**
     * Override the parent and set the run time of this function to the settings
     *
     * @param Step $step
     * @return int
     */
    public function enqueue( $step )
    {
        $run_date = $this->get_setting( 'run_date', date( 'Y-m-d', strtotime( '+1 day' ) ) );
        $run_time = $this->get_setting( 'run_time', '09:00:00' );
        $send_in_timezone = $this->get_setting( 'send_in_timezone', false );

        $time_string = $run_date . ' ' . $run_time;

        /* convert to UTC */
        $final_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $time_string ) );

        /* Modify according to the contacts timezone */
        if ( $send_in_timezone && Plugin::$instance->event_queue::is_processing()  ){
            $final_time = Plugin::$instance->event_queue->get_current_contact()->get_local_time_in_utc_0( $final_time );
            if ( $final_time < time() ){
                $final_time+=DAY_IN_SECONDS;
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