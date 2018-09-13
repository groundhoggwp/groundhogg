<?php
/**
 * Edit MEta Funnel Step
 *
 * Html for the edit meta funnel step in the Funnel builder
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels/Steps
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

function wpgh_http_post_funnel_step_html( $step_id )
{
    $post_keys = wpgh_get_step_meta( $step_id, 'post_keys', true );
    $post_values = wpgh_get_step_meta( $step_id, 'post_values', true );

    $post_url = esc_url( wpgh_get_step_meta( $step_id, 'post_url', true ) );

    if ( ! is_array( $post_keys ) || ! is_array( $post_values ) ){
        $post_keys = array( '' ); //empty to show first option.
        $post_values = array( '' ); //empty to show first option.
    }

    ?>

    <table class="form-table" id="meta-table-<?php echo $step_id; ?>">
        <tbody>
        <tr>
            <td><strong><?php _e( 'Post Url:', 'groundhogg' ); ?></strong></td>
            <td colspan="2"><input type="url" class="regular-text" name="<?php gh_meta_e( $step_id, 'post_url' ); ?>" value="<?php echo $post_url?>"></td>
        </tr>
        <?php foreach ( $post_keys as $i => $post_key): ?>
            <tr>
                <td><label><strong><?php _e( 'Key: ' ); ?></strong><input type="text" name="<?php gh_meta_e( $step_id, 'post_keys' ); ?>[]" value="<?php echo sanitize_key( $post_key );?>"></label></td>
                <td><label><strong><?php _e( 'Value: ' ); ?></strong><input type="text" name="<?php gh_meta_e( $step_id, 'post_values' ); ?>[]" value="<?php echo esc_html( $post_values[ $i ] );?>"></label></td>
                <td>
                    <span class="row-actions">
                        <span class="add"><a style="text-decoration: none" href="javascript:void(0)" class="addmeta"><span class="dashicons dashicons-plus"></span></a></span> |
                        <span class="delete"><a style="text-decoration: none" href="javascript:void(0)" class="deletemeta"><span class="dashicons dashicons-trash"></span></a></span>
                    </span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <script>
        jQuery(function($){
            var table = $( "#meta-table-<?php echo $step_id; ?>" );
            table.click(function ( e ){
                var el = $(e.target);
                if ( el.closest( '.addmeta' ).length ) {
                    el.closest('tr').last().clone().appendTo( el.closest('tr').parent() );
                    el.closest('tr').parent().children().last().find( ':input' ).val( '' );
                } else if ( el.closest( '.deletemeta' ).length ) {
                    el.closest( 'tr' ).remove();
                }
            });
        });
    </script>
    <?php
}

add_action( 'wpgh_get_step_settings_http_post', 'wpgh_http_post_funnel_step_html' );

function wpgh_save_http_post_step( $step_id )
{
    if ( isset( $_POST[ wpgh_prefix_step_meta( $step_id, 'post_url' ) ] ) ){
	    wpgh_update_step_meta( $step_id, 'post_url', esc_url_raw( $_POST[ wpgh_prefix_step_meta( $step_id, 'post_url' ) ] ) );
    }

    if ( isset( $_POST[ wpgh_prefix_step_meta( $step_id, 'post_keys' ) ]  ) ){
        $post_keys = $_POST[ wpgh_prefix_step_meta( $step_id, 'post_keys' ) ];
        $post_values = $_POST[ wpgh_prefix_step_meta( $step_id, 'post_values' ) ];

        if ( ! is_array( $post_keys ) )
            return;

        $post_keys = array_map( 'sanitize_key', $post_keys );
        $post_values = array_map( 'sanitize_text_field', $post_values );

        wpgh_update_step_meta( $step_id, 'post_keys', $post_keys );
        wpgh_update_step_meta( $step_id, 'post_values', $post_values );
    }
}

add_action( 'wpgh_save_step_http_post', 'wpgh_save_http_post_step' );