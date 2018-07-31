<?php
/**
 * Apply Note Funnel Step
 *
 * Html for the apply note funnel step in the Funnel builder
 *
 * @package     wp-funnels
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_apply_note_funnel_step_html( $step_id )
{

    //todo finish function

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Select an tag to apply', 'wp-funnels' ); ?></th>
            <td> TODO APPLY NOTE HTML</td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_apply_note', 'wpfn_apply_note_funnel_step_html' );

function wpfn_apply_note_icon_html()
{
    ?>
    <div class="dashicons dashicons-id-alt"></div><p>Add Note</p>
    <?php
}

add_action( 'wpfn_action_element_icon_html_apply_note', 'wpfn_apply_note_icon_html' );