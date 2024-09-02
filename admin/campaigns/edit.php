<?php
namespace Groundhogg\Admin\Campaigns;


use Groundhogg\Campaign;
use function Groundhogg\action_input;
use function Groundhogg\action_url;
use function Groundhogg\get_request_var;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Edit Tag
 *
 * @package     Admin
 * @subpackage  Admin/Tags
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

$id = absint( get_request_var( 'campaign' ) );

if ( ! $id ) {
	return;
}

$campaign = new Campaign( $id );

?>
<form name="edittag" id="edittag" method="post" action="" class="validate">
	<?php action_input( 'edit', true, true ); ?>
	<table class="form-table">
		<tbody>
		<tr class="form-field form-required term-name-wrap">
			<th scope="row"><label for="name"><?php _e( 'Name' ) ?></label></th>
			<td><input name="name" id="name" type="text" value="<?php esc_attr_e( $campaign->get_name() ); ?>" size="40"
                       aria-required="true">
				<p class="description"><?php _e( 'A descriptive name of the tag so you remember what it means.', 'groundhogg' ) ?></p>
			</td>
		</tr>
        <tr class="form-field form-required term-slug-wrap">
            <th scope="row"><label for="slug"><?php _e( 'Slug' ) ?></label></th>
            <td><input name="slug" id="slug" type="text" value="<?php esc_attr_e( $campaign->get_slug() ); ?>" size="40"
                       aria-required="true">
                <p class="description"><?php _e( 'The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' ) ?></p>
            </td>
        </tr>
		<tr class="form-field term-description-wrap">
			<th scope="row"><label for="description"><?php _e( 'Description' ); ?></label></th>
			<td><textarea name="description" id="description" rows="5" cols="50"
			              class="large-text"><?php echo $campaign->get_description(); ?></textarea>
				<p class="description"><?php _e( 'The description is not prominent by default; However it may be shown if the campaign archive is public.', 'groundhogg' ); ?></p>
			</td>
		</tr>
        <tr class="form-field term-description-wrap">
            <th scope="row"><label for="description"><?php _e( 'Visibility' ); ?></label></th>
            <td><label class="display-flex align-center gap-10"><span><?php _e( 'Make the archive for this campaign publicly accessible?', 'groundhogg' ); ?></span>
                <?php
                echo html()->toggle( [
	                'id'       => 'is-public',
	                'name'     => 'public',
	                'onLabel'  => __( 'Yes' ),
	                'offLabel' => __( 'No' ),
                    'checked'  => $campaign->is_public(),
                ] );
                ?>
                </label>
            </td>
        </tr>
		</tbody>
	</table>
	<div class="edit-tag-actions">
		<?php submit_button( __( 'Update' ), 'primary', 'update', false ); ?>
		<span id="delete-link">
            <a class="delete" href="<?php echo action_url( 'delete', [ 'campaign' => $campaign->ID ] ) ?>">
                <?php _e( 'Delete' ); ?>
            </a>
        </span>
	</div>
</form>
