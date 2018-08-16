<?php
/**
 * Tag Applied Funnel Step
 *
 * Html for the apply tag funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_tag_applied_funnel_step_html( $step_id )
{

    //todo finish function

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Select a tag.', 'groundhogg' ); ?></th>
            <td>TODO TAG APPLIED HTML</td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_tag_applied', 'wpfn_tag_applied_funnel_step_html' );

function wpfn_tag_applied_icon_html()
{
    ?>
    <div class="dashicons dashicons-tag"></div><p>Tag Applied</p>
    <?php
}

add_action( 'wpfn_benchmark_element_icon_html_tag_applied', 'wpfn_tag_applied_icon_html' );