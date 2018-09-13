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

function wpgh_edit_meta_funnel_step_html( $step_id )
{
    $meta_keys = wpgh_get_step_meta( $step_id, 'meta_keys', true );
    $meta_values = wpgh_get_step_meta( $step_id, 'meta_values', true );

    if ( ! is_array( $meta_keys ) || ! is_array( $meta_values ) ){
        $meta_keys = array( '' ); //empty to show first option.
        $meta_values = array( '' ); //empty to show first option.
    }

    ?>

    <table class="form-table" id="meta-table-<?php echo $step_id; ?>">
        <tbody>
        <?php foreach ( $meta_keys as $i => $meta_key): ?>
            <tr>
                <td><label><strong><?php _e( 'Key: ' ); ?></strong><input type="text" name="<?php gh_meta_e( $step_id, 'meta_keys' ); ?>[]" value="<?php echo sanitize_key( $meta_key );?>"></label></td>
                <td><label><strong><?php _e( 'Value: ' ); ?></strong><input type="text" name="<?php gh_meta_e( $step_id, 'meta_values' ); ?>[]" value="<?php echo esc_html( $meta_values[ $i ] );?>"></label></td>
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

add_action( 'wpgh_get_step_settings_edit_meta', 'wpgh_edit_meta_funnel_step_html' );

function wpgh_save_edit_meta_step( $step_id )
{
    if ( isset( $_POST[ wpgh_prefix_step_meta( $step_id, 'meta_keys' ) ]  ) ){
        $meta_keys = $_POST[ wpgh_prefix_step_meta( $step_id, 'meta_keys' ) ];
        $meta_values = $_POST[ wpgh_prefix_step_meta( $step_id, 'meta_values' ) ];

        if ( ! is_array( $meta_keys ) )
            return;

        $meta_keys = array_map( 'sanitize_key', $meta_keys );
        $meta_values = array_map( 'sanitize_text_field', $meta_values );

        wpgh_update_step_meta( $step_id, 'meta_keys', $meta_keys );
        wpgh_update_step_meta( $step_id, 'meta_values', $meta_values );
    }
}

add_action( 'wpgh_save_step_edit_meta', 'wpgh_save_edit_meta_step' );