<?php
/**
 * Tag removed Funnel Step
 *
 * Html for the remove tag funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_tag_removed_funnel_step_html( $step_id )
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
            <th><?php echo esc_html__( 'Run when any of these tags are removed', 'groundhogg' ); ?>:</th>
            <td><?php wpfn_dropdown_tags( $dropdown_args ); ?></td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_tag_removed', 'wpfn_tag_removed_funnel_step_html' );

function wpfn_tag_removed_icon_html()
{
    ?>
    <div class="dashicons dashicons-tag"></div><p><?php _e( 'Tag Removed', 'groundhogg' ); ?></p>
    <?php
}

add_action( 'wpfn_benchmark_element_icon_html_tag_removed', 'wpfn_tag_removed_icon_html' );

/**
 * Save the remove tag step
 *
 * @param $step_id int ID of the step we're saving.
 */
function wpfn_save_tag_removed_step( $step_id )
{
    //no need to check the validation as it's already been done buy the main funnel.
    if ( isset( $_POST[ wpfn_prefix_step_meta( $step_id, 'tags' ) ] ) ){
        $tags = $_POST[ wpfn_prefix_step_meta( $step_id, 'tags' ) ];
        wpfn_update_step_meta( $step_id, 'tags', $tags );
    }
}

add_action( 'wpfn_save_step_tag_removed', 'wpfn_save_tag_removed_step' );
