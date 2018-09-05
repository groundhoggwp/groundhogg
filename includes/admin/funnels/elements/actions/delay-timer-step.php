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

function wpgh_delay_timer_funnel_step_html( $step_id )
{

    //todo finish function

    $amount = wpgh_get_step_meta( $step_id, 'delay_amount', true );
    if ( ! $amount )
        $amount = 3;

    $type = wpgh_get_step_meta( $step_id, 'delay_type', true );
    if ( ! $type )
        $type = 'days';

    $run_when = wpgh_get_step_meta( $step_id, 'run_when', true );
    if ( ! $run_when )
        $run_when = 'now';


    $run_time = wpgh_get_step_meta( $step_id, 'run_time', true );
    if ( ! $run_time )
        $run_time = '09:30';

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Wait at least:', 'groundhogg' ); ?></th>
            <td>
                <input type="number" class="input" min="0" maxlength="20" name="<?php echo wpgh_prefix_step_meta( $step_id, 'delay_amount' ); ?>" value="<?php echo esc_html( $amount ); ?>" >
                <select style="vertical-align: top;" class="input" name="<?php echo wpgh_prefix_step_meta( $step_id, 'delay_type' ); ?>">
                    <option value="minutes" <?php if ( $type == 'minutes' ) echo "selected='selected'" ?> ><?php echo esc_html__( 'Minutes', 'groundhogg' ); ?></option>
                    <option value="hours" <?php if ( $type == 'hours' ) echo "selected='selected'" ?>  ><?php echo esc_html__( 'Hours', 'groundhogg' ); ?></option>
                    <option value="days" <?php if ( $type == 'days' ) echo "selected='selected'" ?>  ><?php echo esc_html__( 'Days', 'groundhogg' ); ?></option>
                    <option value="weeks" <?php if ( $type == 'weeks' ) echo "selected='selected'" ?>  ><?php echo esc_html__( 'Weeks', 'groundhogg' ); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th><?php echo esc_html__( 'And run:', 'groundhogg' ); ?></th>
            <td>
                <select style="vertical-align: top;" id="<?php echo wpgh_prefix_step_meta( $step_id, 'run_when' ); ?>" name="<?php echo wpgh_prefix_step_meta( $step_id, 'run_when' ); ?>">
                    <option value="now" <?php if ( 'now' === $run_when ) echo "selected='selected'"; ?> ><?php echo esc_html__( 'Immediately', 'groundhogg' ); ?></option>
                    <option value="later" <?php if ( 'later' === $run_when ) echo "selected='selected'"; ?> ><?php echo esc_html__( 'At time of day', 'groundhogg' ); ?></option>
                </select>
                <input class="input <?php if ( 'now' === $run_when ) echo "hidden"; ?>" type="time" id="<?php echo wpgh_prefix_step_meta( $step_id, 'run_time' ); ?>" name="<?php echo wpgh_prefix_step_meta( $step_id, 'run_time' ); ?>" value="<?php echo $run_time;?>">
                <script>
                    jQuery( "#<?php echo wpgh_prefix_step_meta( $step_id, 'run_when' ); ?>" ).change(function(){
                        jQuery( "#<?php echo wpgh_prefix_step_meta( $step_id, 'run_time' ); ?>" ).toggleClass( 'hidden' );
                    });
                </script>
            </td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpgh_get_step_settings_delay_timer', 'wpgh_delay_timer_funnel_step_html' );

function wpgh_delay_timer_icon_html()
{
    ?>
    <div class="dashicons dashicons-clock"></div><p><?php echo esc_html__('Delay Timer', 'groundhogg' ); ?></p>
    <?php
}

add_action( 'wpgh_action_element_icon_html_delay_timer', 'wpgh_delay_timer_icon_html' );

function wpgh_save_delay_timer_step( $step_id )
{
    $amount = $_POST[ wpgh_prefix_step_meta( $step_id, 'delay_amount' ) ];
    wpgh_update_step_meta( $step_id, 'delay_amount', $amount );

    $type = $_POST[ wpgh_prefix_step_meta( $step_id, 'delay_type' ) ];
    wpgh_update_step_meta( $step_id, 'delay_type', $type );

    $run_time = $_POST[ wpgh_prefix_step_meta( $step_id, 'run_when' ) ];
    wpgh_update_step_meta( $step_id, 'run_when', $run_time );

    $run_time = $_POST[ wpgh_prefix_step_meta( $step_id, 'run_time' ) ];
    wpgh_update_step_meta( $step_id, 'run_time', $run_time );
}

add_action( 'wpgh_save_step_delay_timer', 'wpgh_save_delay_timer_step' );