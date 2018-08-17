<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-17
 * Time: 3:11 PM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $_GET['tag_id'] ) )
    wp_die( wpfn_get_random_groundhogday_quote() );

$id = intval( $_GET[ 'tag_id' ] );

if ( ! wpfn_tag_exists( $id ) )
    wp_die( wpfn_get_random_groundhogday_quote() );

if ( isset( $_POST[ 'update' ] ) ){

    if ( ! wp_verify_nonce( $_POST['_wpnonce'] ) )
        wp_die( wpfn_get_random_groundhogday_quote() );

    $tag_name = sanitize_text_field( wp_unslash( $_POST[ 'name' ] ) );
    $tag_description = sanitize_textarea_field( wp_unslash( $_POST[ 'description' ] ) );

    wpfn_update_tag( $id, 'tag_description', $tag_description );
    wpfn_update_tag( $id, 'tag_name', $tag_name );

    ?><div class="notice notice-success is-dismissible">
    <p><strong><?php _e( 'Updated Tag' ); ?>.</strong></p>
    <p><a href="<?php echo admin_url( 'admin.php?page=tags' )?>">&larr; <?php _e('Back to Tags');?></a></p>
    </div><?php

}


$tag = wpfn_tag_exists( $id );
?>
<div class="wrap">
    <h1><?php _e( 'Edit Tag' ); ?></h1>
    <?php ?>
    <form name="edittag" id="edittag" method="post" action="" class="validate">
        <?php wp_nonce_field(); ?>
        <table class="form-table">
            <tbody><tr class="form-field form-required term-name-wrap">
                <th scope="row"><label for="name"><?php _e( 'Name' ) ?></label></th>
                <td><input name="name" id="name" type="text" value="<?php esc_attr_e( $tag['tag_name'] ); ?>" size="40" aria-required="true">
                    <p class="description"><?php _e( 'A descriptive name of the tag so you remember what it means', 'groundhogg' ) ?>.</p>
                </td>
            </tr>
            <tr class="form-field term-description-wrap">
                <th scope="row"><label for="description"><?php _e( 'Description' ); ?></label></th>
                <td><textarea name="description" id="description" rows="5" cols="50" class="large-text"><?php echo $tag['tag_description']; ?></textarea>
                    <p class="description"><?php _e( 'Tag descriptions are only visible to admins and will never be seen by contacts.', 'groundhogg' ) ?>.</p>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="edit-tag-actions">
            <?php submit_button( __( 'Update' ), 'primary', 'update', false ); ?>
            <span id="delete-link"><a class="delete" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=tags&action=delete&tag_id='. $id ), 'delete'  ) ?>"><?php _e( 'Delete' ); ?></a></span>
        </div>
    </form>
</div>
