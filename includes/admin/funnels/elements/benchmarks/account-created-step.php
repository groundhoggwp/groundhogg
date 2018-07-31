<?php
/**
 * Account Created Funnel Step
 *
 * Html for the accoutn created funnel step in the Funnel builder
 *
 * @package     wp-funnels
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_account_created_funnel_step_html( $step_id )
{

    //todo finish function

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Select a user account level.', 'wp-funnels' ); ?></th>
            <td>TODO ACCOUNT CREATED HTML</td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_account_created', 'wpfn_account_created_funnel_step_html' );

function wpfn_account_created_icon_html()
{
    ?>
    <div class="dashicons dashicons-admin-users"></div><p>User Created</p>
    <?php
}

add_action( 'wpfn_benchmark_element_icon_html_account_created', 'wpfn_account_created_icon_html' );