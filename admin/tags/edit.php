<?php
namespace Groundhogg\Admin\Tags;


use Groundhogg\Plugin;
use function Groundhogg\get_request_var;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Edit Tag
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Tags
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

$id = absint( get_request_var( 'tag' ) );

if ( ! $id ) {
	return;
}

$tag = Plugin::$instance->dbs->get_db( 'tags' )->get( $id );

?>
<form name="edittag" id="edittag" method="post" action="" class="validate">
	<?php wp_nonce_field(); ?>
    <table class="form-table">
        <tbody>
        <tr class="form-field form-required term-name-wrap">
            <th scope="row"><label for="name"><?php echo esc_html( _x( 'Name', 'tag name', 'groundhogg' ) ); ?></label></th>
            <td><input name="name" id="name" type="text" value="<?php echo esc_attr( $tag->tag_name ); ?>" size="40"
                       aria-required="true">
                <p class="description"><?php esc_html_e( 'A descriptive name of the tag so you remember what it means', 'groundhogg' ) ?>
                    .</p>
            </td>
        </tr>
        <tr class="form-field term-description-wrap">
            <th scope="row"><label for="description"><?php echo esc_html( _x( 'Description', 'tag description', 'groundhogg' ) ); ?></label></th>
            <td><textarea name="description" id="description" rows="5" cols="50"
                          class="large-text"><?php echo esc_html( $tag->tag_description ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Tag descriptions are only visible to admins and will never be seen by contacts.', 'groundhogg' ) ?>
                    .</p>
            </td>
        </tr>
        </tbody>
		<?php do_action( 'groundhogg/admin/tags/edit/form', $id ); ?>
    </table>
    <div class="edit-tag-actions">
		<?php submit_button( esc_html__( 'Update' , 'groundhogg' ), 'primary', 'update', false ); ?>
        <span id="delete-link">
            <a class="delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=gh_tags&action=delete&tag=' . $id ), 'delete' ) ) ?>">
                <?php esc_html_e( 'Delete', 'groundhogg' ); ?>
            </a>
        </span>
    </div>
</form>
