<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-17
 * Time: 3:11 PM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$id = intval( $_GET[ 'tag' ] );

$tag = wpgh_tag_exists( $id );
?>

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
        <span id="delete-link"><a class="delete" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gh_tags&action=delete&tag_id='. $id ), 'delete'  ) ?>"><?php _e( 'Delete' ); ?></a></span>
    </div>
</form>
