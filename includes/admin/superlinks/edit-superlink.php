<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-17
 * Time: 3:11 PM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$id = intval( $_GET[ 'superlink' ] );

$superlink = WPGH()->superlinks->get_superlink( $id );

?>
<form name="edittag" id="edittag" method="post" action="">
    <?php wp_nonce_field(); ?>
    <table class="form-table">
        <tbody><tr class="form-field term-name-wrap">
            <th scope="row"><label for="superlink-name"><?php _e( 'Superlink Name', 'groundhogg' ) ?></label></th>
            <td><input name="superlink_name" id="superlink-name" type="text" value="<?php echo $superlink->name; ?>" maxlength="100" autocomplete="off">
                <p class="description"><?php _e( 'Name a Superlink something simple so you do not forget it.', 'groundhogg' ); ?></p>
            </td>
        </tr>
        <tr class="form-field term-target-wrap">
            <th scope="row"><label for="superlink-target"><?php _e( 'Target URL', 'groundhogg' ) ?></label></th>
            <td><input name="superlink_target" id="superlink-target" type="url" value="<?php echo $superlink->target; ?>" maxlength="100" autocomplete="off">
                <p class="description"><a href="#" id="insert-link" data-target="superlink-target"><?php _e( 'Insert Link' ); ?></a> | <?php _e( 'This is the url the contact will be re-directed to after clicking this Superlink.', 'groundhogg' ); ?></p>
                <script>
                    jQuery( function($){
                        $( '#insert-link' ).linkPicker();
                    });
                </script>
            </td>
        </tr>
        <tr class="form-field term-tags-wrap">
            <th scope="row">
                <label for="superlink-description"><?php _e( 'Apply Tags When Clicked', 'groundhogg' ) ?></label>
            </th>
            <td>
                <?php $tag_args = array();
                $tag_args[ 'id' ] = 'superlink_tags';
                $tag_args[ 'name' ] = 'superlink_tags[]';

                if ( !empty ( $superlink->tags ) ){
                    $tag_args['selected'] = $superlink->tags;
                }
                ?>
                <?php echo WPGH()->html->tag_picker( $tag_args ); ?>
                <p class="description"><?php _e( 'These tags will be applied to a contact whenever this link is clicked. To create a new tag hit [enter] or [comma]', 'groundhogg' ); ?></p>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="edit-superlink-actions">
        <?php submit_button( __( 'Update' ), 'primary', 'update', false ); ?>
        <span id="delete-link"><a class="delete" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gh_superlinks&action=delete&superlink='. $id ), 'delete'  ) ?>"><?php _e( 'Delete' ); ?></a></span>
    </div>
</form>