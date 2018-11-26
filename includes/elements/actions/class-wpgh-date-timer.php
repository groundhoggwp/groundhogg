<?php
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

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Date_Timer extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'date_timer';

    /**
     * @var string
     */
    public $group   = 'action';

    /**
     * @var string
     */
    public $icon    = 'date-timer.png';

    /**
     * @var string
     */
    public $name    = 'Date Timer';

    /**
     * @var string
     */
    public $description = 'Pause until a specific date & time.';

    /**
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {

        $run_date = $step->get_meta( 'run_date' );
        if ( ! $run_date )
            $run_date = date( 'Y-m-d', strtotime( '+1 day' ) );

        $run_time = $step->get_meta( 'run_time' );
        if ( ! $run_time )
            $run_time = '09:30';

        $checked = $step->get_meta( 'disable' );

        ?>

        <table class="form-table">
            <tbody>
            <tr>
                <th><?php echo esc_html__( 'Wait till:', 'groundhogg' ); ?></th>
                <td>
                    <?php $args = array(
                        'class'         => 'input',
                        'name'          => $step->prefix( 'run_date' ),
                        'id'            => $step->prefix( 'run_date' ),
                        'value'         => $run_date,
                        'placeholder'   => 'yyy-mm-dd',
                    );

                    echo WPGH()->html->input( $args ); ?>
                    <?php

                    $args = array(
                        'type'  => 'time',
                        'class' => 'input',
                        'name'  => $step->prefix( 'run_time' ),
                        'id'    => $step->prefix( 'run_time' ),
                        'value' => $run_time,
                    );

                    echo WPGH()->html->input( $args ); ?>
                    <script>jQuery(function($){$('#<?php echo $step->prefix( 'run_date' ); ?>').datepicker({
                        changeMonth: true,
                        changeYear: true,
                        minDate:0,
                        dateFormat:'yy-m-d'
                    })});</script>
            </tr>
            <tr>
                <th>
                    <?php echo esc_html__( 'Disable Temporarily:', 'groundhogg' ); ?>
                </th>
                <td><?php
                    $args = array(
//                    'type'  => 'time',
//                    'class' => 'input',
                        'name'  => $step->prefix( 'disable' ),
                        'id'    => $step->prefix( 'disable' ),
                        'value' => 1,
                        'checked' => $checked,
                        'label' => __( 'Disable', 'groundhogg' )
                    );

                    echo WPGH()->html->checkbox( $args ); ?>
                </td>
            </tr>
            </tbody>
        </table>

        <?php
    }

    /**
     * Compare timers to see if one which date comes first compared to the order of appearance
     *
     * @param $step1 WPGH_Step
     * @param $step2 WPGH_Step
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
     * @param $step WPGH_Step
     */
    public function save( $step )
    {

        $amount = sanitize_text_field(  $_POST[ $step->prefix( 'run_date' )] );
        $date = date( 'Y-m-d', strtotime( $amount ) );
        $step->update_meta( 'run_date', $date );

        $type = sanitize_text_field( $_POST[ $step->prefix( 'run_time' ) ] );
        $step->update_meta( 'run_time', $type );

        if ( isset( $_POST[ $step->prefix( 'disable' ) ] ) ){
            $step->update_meta( 'disable', 1 );
        } else {
            $step->delete_meta( 'disable' );
        }

        $date_timers = WPGH()->steps->get_steps( array( 'step_type' => 'date_timer', 'funnel_id' => $step->funnel_id ) );

        foreach ( $date_timers as $date_timer ){

            $date_timer = new WPGH_Step( $date_timer->ID );

            if ( $date_timer->order < $step->order && $this->compare_timer( $date_timer, $step ) < 0 ){

                WPGH()->notices->add( 'timer-error', sprintf( __( 'You have date timers with descending dates! Your funnel may not work as expected. See <a href="#%d">%s</a>! Timers with dates in the past will run immediately.' ), $step->ID, $step->title ), 'warning' );

            }

        }

        if ( $this->enqueue( $step ) < time() ){

            WPGH()->notices->add( 'timer-error', sprintf( __( 'You have date timers with dates in the past! Your funnel may not work as expected. See <a href="#%d">%s</a>! Timers with dates in the past will run immediately.' ), $step->ID, $step->title ), 'warning' );

        }

    }

    /**
     * Override the parent and set the run time of this function to the settings
     *
     * @param WPGH_Step $step
     * @return int
     */
    public function enqueue( $step )
    {
        if ( $step->get_meta( 'disable' ) ){
            return parent::enqueue( $step );
        }

        $run_date = $step->get_meta( 'run_date' );
        if ( ! $run_date )
            $run_date = date( 'Y-m-d', strtotime( '+1 day' ) );

        $run_time = $step->get_meta( 'run_time' );
        if ( ! $run_time )
            $run_time = '09:30';

        $time_string = $run_date . ' ' . $run_time;

        /* convert to UTC */
        $final_time = strtotime( $time_string ) - ( wpgh_get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

        return $final_time;
    }

    /**
     * Process the apply tag step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return true
     */
    public function run( $contact, $event )
    {
        //do nothing
        return true;
    }

}