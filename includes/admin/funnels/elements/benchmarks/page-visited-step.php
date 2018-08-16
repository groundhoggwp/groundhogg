<?php
/**
 * Page Visited Funnel Step
 *
 * Html for the page visited funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_page_visited_funnel_step_html( $step_id )
{

    //todo finish function

    $pageId = wpfn_get_step_meta( $step_id, 'page_id' );

    $args = array();

    if ( $pageId ){
        $args['selected'] = $pageId;
    }

    $args['name'] = wpfn_prefix_step_meta( $step_id, 'page_id' );
    $args['id'] = wpfn_prefix_step_meta( $step_id, 'page_id' );

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Select a page.', 'groundhogg' ); ?></th>
            <td><?php wp_dropdown_pages( $args ); ?>
                <p>
                    <a id="<?php echo wpfn_prefix_step_meta( $step_id, 'view_page' ); ?>" target="_blank" href="<?php echo admin_url( 'post.php?action=edit&post=' . $pageId ); ?>"><?php _e( 'Edit Page'); ?></a>
                </p>
                <script>jQuery(document).ready(function(){jQuery( '#<?php echo wpfn_prefix_step_meta( $step_id, 'page_id' ); ?>' ).select2()});</script>
                <script>jQuery(function($){$('#<?php echo $args['id'];?>').change(function(){$('#<?php echo wpfn_prefix_step_meta( $step_id, 'view_page' ); ?>').attr('href', '<?php echo admin_url( 'post.php?action=edit&post='); ?>' + $(this).val())})});</script>
            </td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_page_visited', 'wpfn_page_visited_funnel_step_html' );

function wpfn_page_visited_icon_html()
{
    ?>
    <div class="dashicons dashicons-welcome-view-site"></div><p>Page Visited</p>
    <?php
}

add_action( 'wpfn_benchmark_element_icon_html_page_visited', 'wpfn_page_visited_icon_html' );

/**
 * Save the email type step
 *
 * @param $step_id int ID of the step we're saving.
 */
function wpfn_save_page_visited_step( $step_id )
{
    //no need to check the validation as it's already been done buy the main funnel.
    $page_id = intval( $_POST[ wpfn_prefix_step_meta( $step_id, 'page_id' ) ] );
    wpfn_update_step_meta( $step_id, 'page_id', $page_id );
}

add_action( 'wpfn_save_step_page_visited', 'wpfn_save_page_visited_step' );
