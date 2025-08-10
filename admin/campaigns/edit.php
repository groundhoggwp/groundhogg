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
 * @since       File available since Release 0.1
 * @subpackage  Admin/Tags
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
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
            <th scope="row"><label for="name"><?php echo esc_html( _x( 'Name', 'campaign name', 'groundhogg' ) ) ?></label></th>
            <td><input name="name" id="name" type="text" value="<?php echo esc_attr( $campaign->get_name() ); ?>" size="40"
                       aria-required="true">
                <p class="description"><?php esc_html_e( 'A descriptive name of the campaign so you remember what it means.', 'groundhogg' ) ?></p>
            </td>
        </tr>
        <tr class="form-field form-required term-slug-wrap">
            <th scope="row"><label for="slug"><?php echo esc_html( _x( 'Slug', 'campaign slug', 'groundhogg' ) ) ?></label></th>
            <td><input name="slug" id="slug" type="text" value="<?php echo esc_attr( $campaign->get_slug() ); ?>" size="40"
                       aria-required="true">
                <p class="description"><?php esc_html_e( 'The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'groundhogg' ) ?></p>
            </td>
        </tr>
        <tr class="form-field term-description-wrap">
            <th scope="row"><label for="description"><?php echo esc_html( _x( 'Description', 'campaign description', 'groundhogg' ) ); ?></label></th>
            <td><textarea name="description" id="description" rows="5" cols="50"
                          class="large-text"><?php echo esc_html( $campaign->get_description() ); ?></textarea>
                <p class="description"><?php esc_html_e( 'The description is not prominent by default; However it may be shown if the campaign archive is public.', 'groundhogg' ); ?></p>
            </td>
        </tr>
        <tr class="form-field term-description-wrap">
            <th scope="row"><label for="description"><?php echo esc_html( _x( 'Visibility', 'campaign visibility', 'groundhogg' ) ); ?></label></th>
            <td><label class="display-flex align-center gap-10"><span><?php esc_html_e( 'Make the archive for this campaign publicly accessible?', 'groundhogg' ); ?></span>
					<?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo html()->toggle( [
						'id'       => 'is-public',
						'name'     => 'public',
						'onLabel'  => esc_html__( 'Yes', 'groundhogg' ),
						'offLabel' => esc_html__( 'No', 'groundhogg' ),
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'checked'  => $campaign->is_public(),
					] );
					?>
                </label>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="edit-tag-actions">
	    <?php submit_button( esc_html__( 'Update', 'groundhogg' ), 'primary', 'update', false ); ?>
        <span id="delete-link">
            <a class="delete" href="<?php echo esc_url( action_url( 'delete', [ 'campaign' => $campaign->ID ] ) ) ?>">
                <?php esc_html_e( 'Delete', 'groundhogg' ); ?>
            </a>
        </span>
    </div>
</form>
