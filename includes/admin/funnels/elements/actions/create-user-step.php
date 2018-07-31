<?php
/**
 * Create User Funnel Step
 *
 * Html for the create user funnel step in the Funnel builder
 *
 * @package     wp-funnels
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_create_user_funnel_step_html( $step_id )
{
    //todo finish function

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Select a role to give to this user', 'wp-funnels' ); ?></th>
            <td>TODO SELECT ROLE HTML</td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_create_user', 'wpfn_create_user_funnel_step_html' );

function wpfn_create_user_icon_html()
{
    ?>
    <div class="dashicons dashicons-admin-users"></div><p>Create User</p>
    <?php
}

add_action( 'wpfn_action_element_icon_html_create_user', 'wpfn_create_user_icon_html' );