<?php
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

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Field_Timer extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'field_timer';

    /**
     * @var string
     */
    public $group   = 'action';

    /**
     * @var string
     */
    public $icon    = 'field-timer.png';

    /**
     * @var string
     */
    public $name    = 'Field Timer';

    /**
     * @var string
     */
    public $description = 'Pause for the specified amount of time before a date in the meta.';

    public function __construct()
    {
        $this->name = _x( 'Field Timer', 'element_name', 'groundhogg' );
        $this->description = _x( 'Pause for the specified amount of time before a date in the meta.', 'element_description', 'groundhogg' );

        parent::__construct();
    }

    /**
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {
        $checked = $step->get_meta( 'disable' );

        $amount = $step->get_meta( 'delay_amount');
        if ( ! $amount )
            $amount = 3;

        $type = $step->get_meta( 'delay_type' );
        if ( ! $type )
            $type = 'days';

        $run_when = $step->get_meta( 'run_when' );
        if ( ! $run_when )
            $run_when = 'now';

        $run_time = $step->get_meta( 'run_time' );
        if ( ! $run_time )
            $run_time = '09:30';

        $date_field = $step->get_meta( 'date_field' );
        $before_or_after = $step->get_meta( 'before_or_after' )

        ?>

        <table class="form-table">
            <tbody>
            <tr>
                <th><?php echo esc_html__( 'Wait at least:', 'groundhogg' ); ?></th>
                <td>
                    <?php $args = array(
                        'name'  => $step->prefix( 'delay_amount' ),
                        'id'    => $step->prefix( 'delay_amount' ),
                        'class' => 'input',
                        'value' => $amount,
                        'min'   => 0,
                        'max'   => 9999,
                    );

                    echo WPGH()->html->number( $args );

                    $delay_types = array(
                        'minutes'   => __( 'Minutes' ),
                        'hours'     => __( 'Hours' ),
                        'days'      => __( 'Days' ),
                        'weeks'     => __( 'Weeks' ),
                        'months'    => __( 'Months' ),
                        'no_delay'  => __( 'No Delay' ),
                    );

                    $args = array(
                        'name'          => $step->prefix( 'delay_type' ),
                        'id'            => $step->prefix( 'delay_type' ),
                        'options'       => $delay_types,
                        'selected'      => $type,
                        'option_none'   => false,
                    );

                    echo WPGH()->html->dropdown( $args );

                    $args = array(
                        'name'          => $step->prefix( 'before_or_after' ),
                        'id'            => $step->prefix( 'before_or_after' ),
                        'options'       => array(
                            'before' => __( 'Before', 'groundhogg' ),
                            'after'  => __( 'After', 'groundhogg' )
                        ),
                        'selected'      => $before_or_after,
                        'option_none'   => false,
                    );

                    echo WPGH()->html->dropdown( $args );

                    $func = 'func_' . $step->prefix( uniqid() );

                    ?>
                    <script>
                        (function( $){

                            function <?php echo $func; ?>(){
                                var $delay = $("#<?php echo $step->prefix( 'delay_type' ); ?>");
                                if ( $delay.val() === 'no_delay' ){
                                    $( "#<?php echo $step->prefix( 'delay_amount' ); ?>" ).attr( 'disabled', 'disabled' );
                                    $( "#<?php echo $step->prefix( 'before_or_after' ); ?>" ).attr( 'disabled', 'disabled' );
                                } else {
                                    $( "#<?php echo $step->prefix( 'delay_amount' ); ?>" ).removeAttr( 'disabled' );
                                    $( "#<?php echo $step->prefix( 'before_or_after' ); ?>" ).removeAttr( 'disabled' );
                                }
                            };

                            <?php echo $func; ?>();
                            $( "#<?php echo $step->prefix( 'delay_type' ); ?>" ).change( <?php echo $func; ?> );
                        })(jQuery);
                    </script>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__( 'Date Field:', 'groundhogg' ); ?></th>
                <td>
                    <?php

                    $args = array(
                        'name'          => $step->prefix( 'date_field' ),
                        'id'            => $step->prefix( 'date_field' ),
                        'options'       => WPGH()->contact_meta->get_keys(),
                        'selected'      => $date_field,
                        'option_none'   => __( 'Please Select a Field', 'groundhogg' ),
                    );

                    echo WPGH()->html->dropdown( $args );

                    ?>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__( 'And run:', 'groundhogg' ); ?></th>
                <td>
                    <?php

                    $when_types = array(
                        'now'   => __( 'Immediately', 'groundhogg' ),
                        'later' => __( 'At time of day...', 'groundhogg' ),
                    );

                    $args = array(
                        'name'          => $step->prefix( 'run_when' ),
                        'id'            => $step->prefix( 'run_when' ),
                        'options'       => $when_types,
                        'selected'      => $run_when,
                        'option_none'   => false,
                    );

                    echo WPGH()->html->dropdown( $args );

                    $args = array(
                        'type'  => 'time',
                        'class' => ( 'now' === $run_when ) ? 'input hidden' : 'input',
                        'name'  => $step->prefix( 'run_time' ),
                        'id'    => $step->prefix( 'run_time' ),
                        'value' => $run_time,
                    );

                    echo WPGH()->html->input( $args ); ?>

                    <script>
                        jQuery( "#<?php echo $step->prefix( 'run_when' ); ?>" ).change(function(){
                            jQuery( "#<?php echo $step->prefix( 'run_time' ); ?>" ).toggleClass( 'hidden' );
                        });
                    </script>
                </td>
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
     * Save the step settings
     *
     * @param $step WPGH_Step
     */
    public function save( $step )
    {

        $amount = intval( $_POST[ $step->prefix('delay_amount' ) ] );
        $step->update_meta( 'delay_amount', $amount );

        $type = sanitize_text_field( $_POST[ $step->prefix( 'delay_type' ) ] );
        $step->update_meta( 'delay_type', $type );

        $run_time = sanitize_text_field( $_POST[ $step->prefix( 'run_when' ) ] );
        $step->update_meta( 'run_when', $run_time );

        $run_time = sanitize_text_field( $_POST[ $step->prefix( 'run_time' ) ] );
        $step->update_meta( 'run_time', $run_time );

        $before_or_after = sanitize_text_field( $_POST[ $step->prefix( 'before_or_after' ) ] );
        $step->update_meta( 'before_or_after', $before_or_after );

        $date_field = sanitize_text_field( $_POST[ $step->prefix( 'date_field' ) ] );
        $step->update_meta( 'date_field', $date_field );

        if ( isset( $_POST[ $step->prefix( 'disable' ) ] ) ){
            $step->update_meta( 'disable', 1 );
        } else {
            $step->delete_meta( 'disable' );
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
        
        $contact = $step->enqueued_contact;

        if ( $step->get_meta( 'disable' ) ){
            return parent::enqueue( $step );
        }

        $amount     = intval( $step->get_meta( 'delay_amount' ) );
        $type       = $step->get_meta( 'delay_type' );
        $run_when   = $step->get_meta( 'run_when' );
        $run_time   = $step->get_meta( 'run_time' );
        $before_or_after    = $step->get_meta( 'before_or_after' );
        $date_field         = $step->get_meta( 'date_field' );


        /* Get the date from the field string... */
        $date = $contact->get_meta( $date_field );
        if ( ! $date ){
            $date = date( 'Y-m-d', time() );
        }

        /* Calculate as if there is no delay... */

        if ( $run_when == 'now' ){
            $time_string = $date . ' ' . date( 'H:i:s', convert_to_local_time( time() ) ) ;
            $final_time = wpgh_convert_to_utc_0( strtotime( $time_string ) );
        } else {
            $time_string = $date . ' ' . $run_time;
            if ( strtotime( $time_string ) < time() ){
                $formatted_date = date( 'Y-m-d', strtotime( 'tomorrow' ) );
                $time_string = $formatted_date . ' ' . $run_time;
            }

            /* convert to utc */
            $final_time = wpgh_convert_to_utc_0( strtotime( $time_string ) );
        }

        /* Now calculate delay time */

        switch ( $type ){
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

        if ( $diff > 0 ){
            $final_time = ( $before_or_after === 'before' ) ? $final_time - $diff : $final_time + $diff;
        }

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