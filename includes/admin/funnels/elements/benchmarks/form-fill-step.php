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

    $shortcode = sprintf('[gh_form id="%d" success="%s"]', $step_id, esc_url( site_url( '/thank-you/' ) ) );
    $shortcode .= '[gh_first_name]';
    $shortcode .= '[gh_last_name]';
    $shortcode .= '[gh_email]';
    $shortcode .= '[gh_phone]';
    $shortcode .= '[gh_terms]';

    if ( wpgh_is_gdpr() )
        $shortcode .= '[gh_gdpr]';

    if ( wpgh_is_recaptcha_enabled() )
        $shortcode .= '[gh_recaptcha]';

    $shortcode .= '[gh_submit]Submit[/gh_submit]';
    $shortcode .= '[/gh_form]';

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th>
                <?php esc_attr_e( 'Shortcode:', 'groundhogg' ); ?>
            </th>
            <td>
                <p>
                    <strong>
                        <textarea
                                onfocus="this.select()"
                                class="regular-text code"
                             readonly><?php echo $shortcode; ?></textarea>
                    </strong>
                </p>
            </td>
        </tr>
    </table>

    <?php
}

add_action( 'wpgh_get_step_settings_form_fill', 'wpgh_form_fill_funnel_step_html' );


