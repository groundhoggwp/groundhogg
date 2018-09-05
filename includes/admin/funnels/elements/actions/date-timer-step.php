<?php
/**
 * Remove Tag Funnel Step
 *
 * Html for the remove tag funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpgh_date_timer_funnel_step_html( $step_id )
{

    //todo finish function

    $run_date = wpgh_get_step_meta( $step_id, 'run_date', true );
    if ( ! $run_date )
        $run_date = date( 'd-m-Y', strtotime( '+1 day' ) );

    $run_time = wpgh_get_step_meta( $step_id, 'run_time', true );
    if ( ! $run_time )
        $run_time = '09:30';

    ?>

    <table class="form-table">
        <tbody>
            <tr>
                <th><?php echo esc_html__( 'Wait till:', 'groundhogg' ); ?></th>
                <td><input placeholder="d-m-yy" type="text" id="<?php echo wpgh_prefix_step_meta( $step_id, 'run_date' ); ?>" name="<?php echo wpgh_prefix_step_meta( $step_id, 'run_date' ); ?>" value="<?php echo $run_date; ?>"></td>
                <script>jQuery(function($){$('#<?php echo wpgh_prefix_step_meta( $step_id, 'run_date' ); ?>').datepicker({
                        changeMonth: true,
                        changeYear: true,
                        minDate:0,
                        dateFormat:'d-m-yy'
                    })});</script>
            </tr>
            <tr>
                <th><?php echo esc_html__( 'And run at:', 'groundhogg' ); ?></th>
                <td>
                    <input type="time" id="<?php echo wpgh_prefix_step_meta( $step_id, 'run_time' ); ?>" name="<?php echo wpgh_prefix_step_meta( $step_id, 'run_time' ); ?>" value="<?php echo $run_time;?>">
                </td>
            </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpgh_get_step_settings_date_timer', 'wpgh_date_timer_funnel_step_html' );

function wpgh_date_timer_icon_html()
{
    ?>
    <div class="dashicons dashicons-calendar"></div><p>Date Timer</p>
    <?php
}

add_action( 'wpgh_action_element_icon_html_date_timer', 'wpgh_date_timer_icon_html' );