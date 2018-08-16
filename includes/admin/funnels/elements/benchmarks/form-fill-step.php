<?php
/**
 * Form Fill Funnel Step
 *
 * Html for the form fill funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_form_fill_funnel_step_html( $step_id )
{

    //todo finish function

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Select a form.', 'groundhogg' ); ?></th>
            <td>TODO FORM FILL HTML</td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_form_fill', 'wpfn_form_fill_funnel_step_html' );

function wpfn_form_fill_icon_html()
{
    ?>
    <div class="dashicons dashicons-feedback"></div><p>Form Fill</p>
    <?php
}

add_action( 'wpfn_benchmark_element_icon_html_form_fill', 'wpfn_form_fill_icon_html' );