<?php
/**
 * Remove Tag Funnel Step
 *
 * Html for the remove tag funnel step in the Funnel builder
 *
 * @package     wp-funnels
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_remove_tag_funnel_step_html( $step_id )
{

    //todo finish function

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Select an tag to remove', 'wp-funnels' ); ?></th>
            <td> TODO REMOVE TAG HTML</td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_remove_tag', 'wpfn_remove_tag_funnel_step_html' );

function wpfn_remove_tag_icon_html()
{
    ?>
    <div class="dashicons dashicons-tag"></div><p>Remove Tag</p>
    <?php
}

add_action( 'wpfn_action_element_icon_html_remove_tag', 'wpfn_remove_tag_icon_html' );