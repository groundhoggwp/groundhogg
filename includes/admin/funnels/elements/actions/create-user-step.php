<?php
/**
 * Create User Funnel Step
 *
 * Html for the create user funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_create_user_funnel_step_html( $step_id )
{
    $account_role = wpfn_get_step_meta( $step_id, 'role', true );

    $args = array();

    if ( ! $account_role )
        $account_role = 'subscriber'

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Which account level would you like to grant?', 'groundhogg' ); ?></th>
            <td>
                <select name="<?php echo wpfn_prefix_step_meta( $step_id, 'role' ); ?>" id="<?php echo wpfn_prefix_step_meta( $step_id, 'role' ); ?>">
                    <?php wp_dropdown_roles( $account_role ); ?>
                </select>
                <script>jQuery(document).ready(function(){jQuery( '#<?php echo wpfn_prefix_step_meta( $step_id, 'role' ); ?>' ).select2()});</script>
            </td>
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

function wpfn_save_create_user_funnel_step( $step_id )
{
    $role = sanitize_text_field( $_POST[ wpfn_prefix_step_meta( $step_id, 'role' ) ] );
    wpfn_update_step_meta( $step_id, 'role', $role );
}

add_action( 'wpfn_save_step_create_user', 'wpfn_save_create_user_funnel_step' );