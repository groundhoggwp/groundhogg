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

function wpgh_form_fill_funnel_step_html( $step_id )
{

    $step = wpgh_get_funnel_step_by_id( $step_id );
    //$title = $step[ 'funnelstep_title' ];

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th>
                <?php esc_attr_e( 'Copy & Paste:', 'groundhogg' ); ?>
            </th>
            <td><p><strong><input class="regular-text" type="text" value='[gh_form id="<?php echo $step_id; ?>" fields="first,last,email,phone,terms" submit="submit" success="<?php echo esc_url( site_url( '/thank-you/' ) );?>" labels="off"]' readonly></strong></p>
            </td>
        </tr>
    </table>

    <?php
}

add_action( 'wpgh_get_step_settings_form_fill', 'wpgh_form_fill_funnel_step_html' );


