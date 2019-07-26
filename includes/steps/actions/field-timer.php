<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use function Groundhogg\get_db;
use function Groundhogg\html;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( !defined( 'ABSPATH' ) ) exit;

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
class Field_Timer extends Action
{

    /**
     * @return string
     */
    public function get_help_article()
    {
        return 'https://docs.groundhogg.io/docs/builder/actions/field-timer/';
    }

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Field Timer', 'step_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'field_timer';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Pause for the specified amount of time before a date in the meta.', 'step_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/field-timer.png';
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {
        $amount = $this->get_setting( 'delay_amount' , 3 );
        $type = $this->get_setting('delay_type'  , 'days');
        $run_when = $this->get_setting( 'run_when'  , 'now');
        $run_time = $this->get_setting( 'run_time' , '09:30' );
        $date_field = $this->get_setting( 'date_field' );
        $before_or_after = $this->get_setting('before_or_after' )

        ?>

        <table class="form-table">
            <tbody>
            <tr>
                <th><?php echo esc_html__( 'Wait at least:', 'groundhogg' ); ?></th>
                <td>
                    <?php

                    echo html()->number( [
                        'name' => $this->setting_name_prefix( 'delay_amount' ),
                        'id' => $this->setting_id_prefix( 'delay_amount' ),
                        'class' => 'input',
                        'value' => $amount,
                        'min' => 0,
                        'max' => 9999,
                    ] );

                    echo html()->dropdown( [
                        'name' => $this->setting_name_prefix( 'delay_type' ),
                        'id' => $this->setting_id_prefix( 'delay_type' ),
                        'options' => [
                            'minutes' => __( 'Minutes' ),
                            'hours' => __( 'Hours' ),
                            'days' => __( 'Days' ),
                            'weeks' => __( 'Weeks' ),
                            'months' => __( 'Months' ),
                            'no_delay' => __( 'No Delay' ),
                        ],
                        'selected' => $type,
                        'option_none' => false,
                    ] );


                    echo html()->dropdown( [
                        'name' => $this->setting_name_prefix( 'before_or_after' ),
                        'id' => $this->setting_id_prefix( 'before_or_after' ),
                        'options' => array(
                            'before' => __( 'Before', 'groundhogg' ),
                            'after' => __( 'After', 'groundhogg' )
                        ),
                        'selected' => $before_or_after,
                        'option_none' => false,
                    ] );

                    $func = 'func_' . $this->setting_id_prefix( uniqid() );

                    ?>
                    <script>
                        (function ($) {

                            function <?php echo $func; ?>() {
                                var $delay = $("#<?php echo $this->setting_id_prefix( 'delay_type' ); ?>");
                                if ($delay.val() === 'no_delay') {
                                    $("#<?php echo $this->setting_id_prefix( 'delay_amount' ); ?>").attr('disabled', 'disabled');
                                    $("#<?php echo $this->setting_id_prefix( 'before_or_after' ); ?>").attr('disabled', 'disabled');
                                } else {
                                    $("#<?php echo $this->setting_id_prefix( 'delay_amount' ); ?>").removeAttr('disabled');
                                    $("#<?php echo $this->setting_id_prefix( 'before_or_after' ); ?>").removeAttr('disabled');
                                }
                            };

                            <?php echo $func; ?>();
                            $("#<?php echo $this->setting_id_prefix( 'delay_type' ); ?>").change( <?php echo $func; ?> );
                        })(jQuery);
                    </script>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__( 'Date Field:', 'groundhogg' ); ?></th>
                <td>
                    <?php
                    echo html()->dropdown( [
                        'name' => $this->setting_name_prefix( 'date_field' ),
                        'id' => $this->setting_id_prefix( 'date_field' ),
                        'options' => get_db( 'contactmeta' )->get_keys(),
                        'selected' => $date_field,
                        'option_none' => __( 'Please Select a Field', 'groundhogg' ),
                    ] );

                    ?>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__( 'And run:', 'groundhogg' ); ?></th>
                <td>
                    <?php

                    $when_types = [
                        'now' => __( 'Immediately', 'groundhogg' ),
                        'later' => __( 'At time of day...', 'groundhogg' ),
                    ];

                    echo html()->dropdown( [
                        'name' => $this->setting_name_prefix( 'run_when' ),
                        'id' => $this->setting_id_prefix( 'run_when' ),
                        'options' => $when_types,
                        'selected' => $run_when,
                        'option_none' => false,
                    ] );

                    echo html()->input( [
                        'type' => 'time',
                        'class' => ( 'now' === $run_when ) ? 'input hidden' : 'input',
                        'name' => $this->setting_name_prefix( 'run_time' ),
                        'id' => $this->setting_id_prefix( 'run_time' ),
                        'value' => $run_time,
                    ] ); ?>

                    <script>
                        jQuery("#<?php echo $this->setting_id_prefix( 'run_when' ); ?>").change(function () {
                            jQuery("#<?php echo $this->setting_id_prefix( 'run_time' ); ?>").toggleClass('hidden');
                        });
                    </script>
                </td>
            </tr>
            </tbody>
        </table>

        <?php
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
        $this->save_setting( 'before_or_after', sanitize_text_field( $this->get_posted_data( 'before_or_after' ) ) );
        $this->save_setting( 'date_field', sanitize_text_field( $this->get_posted_data( 'date_field' ) ) );
    }

    /**
     * Override the parent and set the run time of this function to the settings
     *
     * @param Step $step
     * @return int
     */
    public function enqueue( $step )
    {

        $contact = $step->enqueued_contact;

        $amount = absint( $this->get_setting('delay_amount' ) );
        $type = $this->get_setting( 'delay_type' );
        $run_when = $this->get_setting( 'run_when' );
        $run_time = $this->get_setting( 'run_time' );
        $before_or_after = $this->get_setting( 'before_or_after' );
        $date_field = $this->get_setting('date_field' );


        /* Get the date from the field string... */
        $date = $contact->get_meta( $date_field );

        if ( ! $date ) {
            $date = date( 'Y-m-d', time() );
        }

        if ( is_numeric( $date ) ){
            $date = date( 'Y-m-d', absint( $date ) );
        }

        if ( strtotime( $date ) <= 0 ){
            return parent::enqueue( $step );
        }

        /* Calculate as if there is no delay... */

        if ( $run_when == 'now' ) {
            $time_string = $date . ' ' . date( 'H:i:s', Plugin::$instance->utils->date_time->convert_to_local_time( time() ) );
            $final_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $time_string ) );
        } else {
            $time_string = $date . ' ' . $run_time;
            if ( strtotime( $time_string ) < time() ) {
                $formatted_date = date( 'Y-m-d', strtotime( 'tomorrow' ) );
                $time_string = $formatted_date . ' ' . $run_time;
            }

            /* convert to utc */
            $final_time = Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $time_string ) );
        }

        /* Now calculate delay time */

        switch ( $type ) {
            case 'minutes':
                $diff = $amount * MINUTE_IN_SECONDS;
                break;

            case 'hours':
                $diff = $amount * HOUR_IN_SECONDS;
                break;

            case 'days':
                $diff = $amount * DAY_IN_SECONDS;
                break;

            case 'months':
                $diff = $amount * MONTH_IN_SECONDS;
                break;

            case 'no_delay':
            default:
                $diff = 0;
                break;
        }

        if ( $diff > 0 ) {
            $final_time = ( $before_or_after === 'before' ) ? $final_time - $diff : $final_time + $diff;
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