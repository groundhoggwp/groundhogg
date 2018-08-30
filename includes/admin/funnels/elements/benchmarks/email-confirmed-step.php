<?php
/**
 * Email confirmed Funnel Step
 *
 * Html for the page visited funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_email_confirmed_funnel_step_html( $step_id )
{
    ?>
    <table class="form-table">
        <tr>
            <td>
                <p class="description"><?php _e( 'Runs whenever an email is confirmed while in this funnel', 'groundhogg' ); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'wpfn_get_step_settings_email_confirmed', 'wpfn_email_confirmed_funnel_step_html' );