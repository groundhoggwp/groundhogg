<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class WPGH_Guided_Setup_Step_Other extends WPGH_Guided_Setup_Step
{

    public function get_title()
    {
        return _x( 'Other Stuff', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'other_stuff';
    }

    public function get_description()
    {
        return _x( 'Tune Groundhogg even further by using these settings below.', 'guided_setup', 'groundhogg' );
    }

    public function get_settings()
    {
        ob_start();
        ?>
        <table class="form-table">
            <tr>
                <th><?php _ex( 'Enable the Groundhogg API', 'setting_label', 'groundhogg' ); ?></th>
                <td><?php echo  WPGH()->html->checkbox( array(
                        'name' => 'gh_enable_api',
                        'label' => __( 'Enable', 'guided_setup', 'Groundhogg' ),
                        'value' => 1,
                        'checked' => wpgh_is_option_enabled( 'gh_enable_api' )
                    ) );?>
                    <p class="description"><?php _ex( 'This will allow other plugins & software to communicate with Groundhogg on this site.', 'guided_setup', 'groundhogg' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php _ex( 'How frequently should the event queue run?', 'setting_label', 'groundhogg' ); ?></th>
                <td><?php echo WPGH()->html->dropdown( array(
                        'name' => 'gh_queue_interval',
                        'selected' => wpgh_get_option(  'gh_queue_interval', 'every_5_minutes' ),
                        'options'  => array(
                            'every_1_minutes' => 'Every 1 Minutes',
                            'every_5_minutes' => 'Every 5 Minutes',
                            'every_10_minutes' => 'Every 10 Minutes',
                        ),
                    ) ); ?>
                    <p class="description"><?php _ex( 'This will decide how frequently the Event (automation) queue runs. For example, if [Every 5 Minutes] is selected then then actions which are added to the queue will be run in 5 minute intervals.', 'guided_setup', 'groundhogg' ); ?></p>
                    <p class="description"><?php _ex( 'If you are on a shared hosting plan we recommend Every 5 or 10 minute intervals. ', 'guided_setup', 'groundhogg' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
        return ob_get_clean();
    }

    public function save()
    {
        if ( isset( $_POST[ 'gh_enable_api' ] ) && ! empty( $_POST[ 'gh_enable_api' ] ) ){
            wpgh_update_option( 'gh_enable_api', [ 'on' ] );
        }

        if ( isset( $_POST[ 'gh_queue_interval' ] ) && ! empty( $_POST[ 'gh_queue_interval' ] ) ){
            wpgh_update_option( 'gh_queue_interval', sanitize_key( $_POST[ 'gh_queue_interval' ] ) );
        }

        return true;
    }

}