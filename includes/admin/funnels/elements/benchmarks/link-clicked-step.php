<?php
/**
 * Link Clicked Funnel Step
 *
 * Html for the form fill funnel step in the Funnel builder
 *
 * @package     wp-funnels
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpfn_link_clicked_funnel_step_html( $step_id )
{

    $email_dropdown_id = $step_id . '_email_id';
    $email_dropdown_name = $step_id . '_email_name';

    $dropdown_args = array();
    $dropdown_args[ 'id' ] = $email_dropdown_id;
    $dropdown_args[ 'name' ] = $email_dropdown_name;

    $previously_selected = intval( wpfn_get_step_meta( $step_id, 'email_id', true ) );

    if ( $previously_selected )
        $dropdown_args['selected'] = $previously_selected;

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th><?php echo esc_html__( 'Which email is the link in?', 'wp-funnels' ); ?></th>
            <td><?php wpfn_dropdown_emails( $dropdown_args ); ?></td>
        </tr>
        </tbody>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_link_clicked', 'wpfn_link_clicked_funnel_step_html' );

function wpfn_link_clicked_icon_html()
{
    ?>
    <div class="dashicons dashicons-admin-links"></div><p>Link Clicked</p>
    <?php
}

add_action( 'wpfn_benchmark_element_icon_html_link_clicked', 'wpfn_link_clicked_icon_html' );