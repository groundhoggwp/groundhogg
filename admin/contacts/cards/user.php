<?php

/**
 * Show the user data in the user info card
 *
 * - ID with link to edit
 * - Date of last login
 * -
 *
 * @var $contact Contact
 */

use Groundhogg\Contact;
use function Groundhogg\action_url;
use function Groundhogg\andList;
use function Groundhogg\contact_and_user_match;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/* Auto link the account before we see the create account form. */
$contact->auto_link_account();

if ( $contact->get_userdata() ):

	?>
    <table>
        <tr>
            <th><?php esc_html_e( 'User ID', 'groundhogg' ) ?></th>
            <td><a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $contact->get_user_id() ) ) ?>"><?php echo esc_html( '#' . $contact->get_user_id() ); ?></a></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Username', 'groundhogg' ) ?></th>
            <td><a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $contact->get_user_id() ) ) ?>"><?php echo esc_html( $contact->get_userdata()->user_login ); ?></a></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Email', 'groundhogg' ) ?></th>
            <td><?php echo esc_html( $contact->user->user_email ); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Display Name', 'groundhogg' ) ?></th>
            <td><?php echo esc_html( $contact->user->display_name ); ?></td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Roles', 'groundhogg' ) ?></th>
            <td><?php echo esc_html( andList( array_map( '\Groundhogg\get_role_display_name', $contact->user->roles ) ) ) ?></td>
        </tr>
    </table>
	<?php if ( ! contact_and_user_match( $contact, $contact->user ) ): ?>
    <p><?php
	    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo html()->e( 'a', [
		    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'href' => action_url( 'unlink_user', [ 'contact' => $contact->get_id() ] )
		], esc_html__( 'Unlink this user', 'groundhogg' ) ) ?></p>
<?php endif; ?>
<?php else: ?>
    <p><?php esc_html_e( 'This contact does not have a WordPress user account.', 'groundhogg' ); ?></p>
    <form id="create-user-form" action="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>" method="post">
        <input type="hidden" name="createuser" value="1">
        <input type="hidden" name="first_name" value="<?php echo esc_attr( $contact->get_first_name() ); ?>">
        <input type="hidden" name="last_name" value="<?php echo esc_attr( $contact->get_last_name() ); ?>">
        <input type="hidden" name="email" value="<?php echo esc_attr( $contact->get_email() ); ?>">
        <input type="hidden" name="user_login" value="<?php echo esc_attr( $contact->get_email() ); ?>">
        <p>
            <button type="submit" class="gh-button secondary create-user-account">
				<?php esc_html_e( 'Create User Account', 'groundhogg' ); ?>
            </button>
        </p>
    </form>

<?php endif;
