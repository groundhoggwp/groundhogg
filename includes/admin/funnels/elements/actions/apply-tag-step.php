<?php
/**
 * Apply Tag Funnel Step
 *
 * Html for the apply tag funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_apply_tag_funnel_step_html( $step_id )
{

    $tag_dropdown_id = $step_id . '_tags';
    $tag_dropdown_name = $step_id . '_tags[]';

    $dropdown_args = array();
    $dropdown_args[ 'id' ] = $tag_dropdown_id;
    $dropdown_args[ 'name' ] = $tag_dropdown_name;
    $dropdown_args[ 'width' ] = '100%';
    $dropdown_args[ 'class' ] = 'hidden';

    $previously_selected = wpfn_get_step_meta( $step_id, 'tags', true );

    if ( $previously_selected )
        $dropdown_args['selected'] = $previously_selected;

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Select Tags to Apply:', 'groundhogg' ); ?></th>
            <td>
                <?php wpfn_dropdown_tags( $dropdown_args ); ?>
                <p class="description"><?php _e( 'Add new tags be hitting [enter] or by typing a [comma].', 'groundhogg' ); ?></p>
            </td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_apply_tag', 'wpfn_apply_tag_funnel_step_html' );

/**
 * Save the apply tag step
 *
 * @param $step_id int ID of the step we're saving.
 */
function wpfn_save_apply_tag_step( $step_id )
{
//    print_r( $_POST[ wpfn_prefix_step_meta( $step_id, 'tags' ) ] );
//    wp_die();

    //no need to check the validation as it's already been done buy the main funnel.
    if ( isset( $_POST[ wpfn_prefix_step_meta( $step_id, 'tags' ) ] ) ){
        $tags = wpfn_validate_tags( $_POST[ wpfn_prefix_step_meta( $step_id, 'tags' ) ] );
        wpfn_update_step_meta( $step_id, 'tags', $tags );
    }
}

add_action( 'wpfn_save_step_apply_tag', 'wpfn_save_apply_tag_step' );
