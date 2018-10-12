<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-04
 * Time: 5:42 PM
 */

class WPGH_Delay_Timer extends WPGH_Funnel_Step
{

    /**
     * @var string
     */
    public $type    = 'delay_timer';

    /**
     * @var string
     */
    public $group   = 'action';

    /**
     * @var string
     */
    public $icon    = 'delay-timer.png';

    /**
     * @var string
     */
    public $name    = 'Delay Timer';

    /**
     * @param $step WPGH_Step
     */
    public function settings( $step )
    {

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
                    );

                    $args = array(
                        'name'          => $step->prefix( 'delay_type' ),
                        'id'            => $step->prefix( 'delay_type' ),
                        'options'       => $delay_types,
                        'selected'      => $type,
                        'option_none'   => false,
                    );

                    echo WPGH()->html->dropdown( $args ); ?>
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

    }

    /**
     * Override the parent and set the run time of this function to the settings
     *
     * @param WPGH_Step $step
     * @return int
     */
    public function enqueue( $step )
    {
        $amount     = $step->get_meta( 'delay_amount' );
        $type       = $step->get_meta( 'delay_type' );
        $run_when   = $step->get_meta( 'run_when' );
        $run_time   = $step->get_meta( 'run_time' );

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
            $final_time = strtotime( $time_string ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
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