<?php
/**
 * Apply Note Funnel Step
 *
 * Html for the apply note funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpgh_apply_note_funnel_step_html( $step_id )
{

    //todo finish function

    $note = wpgh_get_step_meta( $step_id, 'note_text', true );

    if ( ! $note )
        $note = "This contact is super awesome!";

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Note Text', 'groundhogg' ); ?></th>
            <td><textarea cols="64" rows="4" id="<?php echo wpgh_prefix_step_meta( $step_id, 'note_text'); ?>" name="<?php echo wpgh_prefix_step_meta( $step_id, 'note_text'); ?>"><?php echo $note; ?></textarea></td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpgh_get_step_settings_apply_note', 'wpgh_apply_note_funnel_step_html' );

function wpgh_save_apply_note_step( $step_id )
{
    if ( isset( $_POST[ wpgh_prefix_step_meta( $step_id, 'note_text') ] ) ){
        wpgh_update_step_meta( $step_id, 'note_text', sanitize_textarea_field( $_POST[ wpgh_prefix_step_meta( $step_id, 'note_text') ] ) );
    }
}

add_action( 'wpgh_save_step_apply_note', 'wpgh_save_apply_note_step' );