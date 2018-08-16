<?php
/**
 * Tag Removed Funnel Step
 *
 * Html for the tag removed funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_tag_removed_funnel_step_html( $step_id )
{

    //todo finish function

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Select a tag.', 'groundhogg' ); ?></th>
            <td>TODO TAG REMOVED HTML</td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_tag_removed', 'wpfn_tag_removed_funnel_step_html' );

function wpfn_tag_removed_icon_html()
{
    ?>
    <div class="dashicons dashicons-tag"></div><p>Tag Removed</p>
    <?php
}

add_action( 'wpfn_benchmark_element_icon_html_tag_removed', 'wpfn_tag_removed_icon_html' );