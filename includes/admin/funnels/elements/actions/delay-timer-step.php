<?php
/**
 * Remove Tag Funnel Step
 *
 * Html for the remove tag funnel step in the Funnel builder
 *
 * @package     wp-funnels
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_delay_timer_funnel_step_html( $step_id )
{

    //todo finish function

    $amount = wpfn_get_step_meta( $step_id, 'delay_amount', true );
    if ( ! $amount )
        $amount = 3;

    $type = wpfn_get_step_meta( $step_id, 'delay_type', true );
    if ( ! $type )
        $type = 'days';

    $run_time = wpfn_get_step_meta( $step_id, 'run_time', true );
    if ( ! $run_time )
        $run_time = '09:30';

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Wait at least:', 'wp-funnels' ); ?></th>
            <td>
                <input type="number" class="input" min="0" maxlength="20" name="<?php echo wpfn_prefix_step_meta( $step_id, 'delay_amount' ); ?>" value="<?php echo esc_html( $amount ); ?>" >
                <select class="input" name="<?php echo wpfn_prefix_step_meta( $step_id, 'delay_type' ); ?>">
                    <option value="minutes" <?php if ( $type == 'minutes' ) echo "selected='selected'" ?> ><?php echo esc_html__( 'Minutes', 'wp-funnels' ); ?></option>
                    <option value="hours" <?php if ( $type == 'hours' ) echo "selected='selected'" ?>  ><?php echo esc_html__( 'Hours', 'wp-funnels' ); ?></option>
                    <option value="days" <?php if ( $type == 'days' ) echo "selected='selected'" ?>  ><?php echo esc_html__( 'Days', 'wp-funnels' ); ?></option>
                    <option value="weeks" <?php if ( $type == 'weeks' ) echo "selected='selected'" ?>  ><?php echo esc_html__( 'Weeks', 'wp-funnels' ); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th><?php echo esc_html__( 'And run at:', 'wp-funnels' ); ?></th>
            <td>
                <input type="time" id="<?php echo wpfn_prefix_step_meta( $step_id, 'run_time' ); ?>" name="<?php echo wpfn_prefix_step_meta( $step_id, 'run_time' ); ?>" value="<?php echo $run_time;?>">
            </td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_delay_timer', 'wpfn_delay_timer_funnel_step_html' );

function wpfn_delay_timer_icon_html()
{
    ?>
    <div class="dashicons dashicons-clock"></div><p><?php echo esc_html__('Delay Timer', 'wp-funnels' ); ?></p>
    <?php
}

add_action( 'wpfn_action_element_icon_html_delay_timer', 'wpfn_delay_timer_icon_html' );