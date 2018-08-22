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

    $step = wpfn_get_funnel_step_by_id( $step_id );
    //$title = $step[ 'funnelstep_title' ];

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th>
                <?php esc_attr_e( 'Copy & Paste:', 'groundhogg' ); ?>
            </th>
            <td><p><strong><input class="regular-text" type="text" value='[gh_form id="<?php echo $step_id; ?>" fields="first,last,email,phone" submit="submit" success="/thank-you/" labels="off"]' readonly></strong></p>
            </td>
        </tr>
    </table>

    <?php
}

add_action( 'wpfn_get_step_settings_form_fill', 'wpfn_form_fill_funnel_step_html' );


