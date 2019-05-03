<?php
namespace Groundhogg\Admin\Superlinks;

use Groundhogg\Plugin;
/**
 * Edit A Superlink
 *
 * @package     Admin
 * @subpackage  Admin/Supperlinks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;
$id = intval( $_GET[ 'superlink' ] );

$superlink =Plugin::instance()->dbs->get_db('superlinks')->get($id);

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
            <td>
                <?php
                $args = array(
                    'type'      => 'url',
                    'id'        => 'superlink_target',
                    'name'      => 'superlink_target',
                    'title'     => __( 'Superlink target' ),
                    'value'     => $superlink->target,
                );

              echo Plugin::$instance->utils->html->link_picker( $args); ?>
                <p class="description"><?php _e( 'This is the url the contact will be re-directed to after clicking this Superlink.', 'groundhogg' ); ?></p>
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
                echo Plugin::$instance->utils->html->tag_picker( $tag_args);
                ?>
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