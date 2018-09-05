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

function wpgh_page_visited_funnel_step_html( $step_id )
{
    $match_type = wpgh_get_step_meta( $step_id, 'match_type' );
    $match_url = wpgh_get_step_meta( $step_id, 'url_match' );

    ?>

    <table class="form-table">
        <tbody>
        <tr>
            <th>
                <?php esc_attr_e( 'Enter URL', 'groundhogg' ); ?>
            </th>
            <td>
                <select style="vertical-align: top;" id="<?php echo wpgh_prefix_step_meta( $step_id, 'match_type' ); ?>" name="<?php echo wpgh_prefix_step_meta( $step_id, 'match_type' ); ?>">
                    <option value="partial" <?php if ( 'partial' === $match_type ) echo "selected='selected'"; ?> ><?php echo esc_html__( 'Partial Match', 'groundhogg' ); ?></option>
                    <option value="exact" <?php if ( 'exact' === $match_type ) echo "selected='selected'"; ?> ><?php echo esc_html__( 'Exact Match', 'groundhogg' ); ?></option>
                </select>
                <input title="<?php esc_attr_e( 'Match Url', 'groundhogg' )?>" type="text" class="input" name="<?php echo wpgh_prefix_step_meta( $step_id, 'url_match' ); ?>" id="<?php echo wpgh_prefix_step_meta( $step_id, 'url_match' ); ?>" value="<?php echo esc_url( $match_url ); ?>">
                <p><a href="#" data-target="<?php echo wpgh_prefix_step_meta( $step_id, 'url_match' ); ?>" id="<?php echo wpgh_prefix_step_meta( $step_id, 'add_link' ); ?>"><?php _e( 'Insert Link' , 'groundhogg' ); ?></a> | <?php _e('Does not match query string.', 'groundhogg' ); ?></p>
                <script>
                    jQuery(function($){
                        $('#<?php echo wpgh_prefix_step_meta( $step_id, 'add_link' ); ?>').linkPicker();
                    });
                </script>
            </td>
        </tr>
    </table>

    <?php
}

add_action( 'wpgh_get_step_settings_page_visited', 'wpgh_page_visited_funnel_step_html' );

/**
 * Save the page visited type step
 *
 * @param $step_id int ID of the step we're saving.
 */
function wpgh_save_page_visited_step( $step_id )
{
    //no need to check the validation as it's already been done buy the main funnel.

    if ( isset( $_POST[ wpgh_prefix_step_meta( $step_id, 'match_type' ) ] ) )
        wpgh_update_step_meta( $step_id, 'match_type', $_POST[ wpgh_prefix_step_meta( $step_id, 'match_type' ) ] );

    if ( isset( $_POST[ wpgh_prefix_step_meta( $step_id, 'url_match' ) ] ) )
        wpgh_update_step_meta( $step_id, 'url_match', $_POST[ wpgh_prefix_step_meta( $step_id, 'url_match' ) ] );

}

add_action( 'wpgh_save_step_page_visited', 'wpgh_save_page_visited_step' );
