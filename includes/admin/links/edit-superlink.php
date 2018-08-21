<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-17
 * Time: 3:11 PM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $_GET['superlink_id'] ) )
    wp_die( wpfn_get_random_groundhogday_quote() );

$id = intval( $_GET[ 'superlink_id' ] );

if ( ! wpfn_get_superlink_by_id( $id ) )
    wp_die( wpfn_get_random_groundhogday_quote() );

if ( isset( $_POST[ 'update' ] ) ){

    if ( ! wp_verify_nonce( $_POST['_wpnonce'] ) )
        wp_die( wpfn_get_random_groundhogday_quote() );

    $superlink_name = sanitize_text_field( wp_unslash( $_POST['superlink_name'] ) );
    $superlink_target = sanitize_text_field( wp_unslash( $_POST['superlink_target'] ) );
    $superlink_tags = $_POST['superlink_tags'];

    wpfn_update_superlink( $id, 'name', $superlink_name );
    wpfn_update_superlink( $id, 'target', $superlink_target );
    wpfn_update_superlink( $id, 'tags', $superlink_tags );

    ?><div class="notice notice-success is-dismissible">
    <p><strong><?php _e( 'Updated Superlink' ); ?>.</strong></p>
    <p><a href="<?php echo admin_url( 'admin.php?page=gh_superlinks' )?>">&larr; <?php _e('Back to Tags');?></a></p>
    </div><?php

}

$superlink = wpfn_get_superlink_by_id( $id );

?>
<div class="wrap">
    <h1><?php _e( 'Edit Superlink' ); ?></h1>
    <?php ?>
    <form name="edittag" id="edittag" method="post" action="">
        <?php wp_nonce_field(); ?>
        <table class="form-table">
            <tbody><tr class="form-field term-name-wrap">
                <th scope="row"><label for="superlink-name"><?php _e( 'Superlink Name', 'groundhogg' ) ?></label></th>
                <td><input name="superlink_name" id="superlink-name" type="text" value="<?php echo $superlink['name']; ?>" maxlength="100" autocomplete="off">
                    <p class="description"><?php _e( 'Name a Superlink something simple so you do not forget it.', 'groundhogg' ); ?></p>
                </td>
            </tr>
            <tr class="form-field term-target-wrap">
                <th scope="row"><label for="superlink-target"><?php _e( 'Target URL', 'groundhogg' ) ?></label></th>
                <td><input name="superlink_target" id="superlink-target" type="url" value="<?php echo $superlink['target']; ?>" maxlength="100" autocomplete="off">
                    <p class="description"><a href="#" id="insert-link" data-target="superlink-target"><?php _e( 'Insert Link' ); ?></a> | <?php _e( 'This is the url the contact will be re-directed to after clicking this Superlink.', 'groundhogg' ); ?></p>
                    <script>
                        jQuery( function($){
                            $( '#insert-link' ).linkPicker();
                        });
                    </script>
                </td>
            </tr>
            <tr class="form-field term-tags-wrap">
                <th>
                    <label for="superlink-description"><?php _e( 'Apply Tags When Clicked', 'groundhogg' ) ?></label>
                </th>
                <td>
                    <?php $tag_dropdown_id = 'tags';
                    $tag_dropdown_name = 'superlink_tags[]';
                    $tag_args = array();
                    $tag_args[ 'id' ] = $tag_dropdown_id;
                    $tag_args[ 'name' ] = $tag_dropdown_name;
                    $tag_args[ 'width' ] = '100%';
                    $tag_args[ 'class' ] = 'hidden';

                    if ( !empty ( $superlink['tags'] ) ){
                        $tag_args['selected'] = $superlink['tags'];
                    }
                    ?>
                    <?php wpfn_dropdown_tags( $tag_args ); ?>
                    <p class="description"><?php _e( 'These tags will be applied to a contact whenever this link is clicked.', 'groundhogg' ); ?></p>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="edit-superlink-actions">
            <?php submit_button( __( 'Update' ), 'primary', 'update', false ); ?>
            <span id="delete-link"><a class="delete" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gh_superlinks&action=delete&superlink_id='. $id ), 'delete'  ) ?>"><?php _e( 'Delete' ); ?></a></span>
        </div>
    </form>
</div>
