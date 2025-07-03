<?php

/**
 * Show the user data in the user info card
 *
 * - ID with link to edit
 * - Date of last login
 * -
 *
 * @var $contact \Groundhogg\Contact
 */

/* Auto link the account before we see the create account form. */
$contact->auto_link_account();

if ( $contact->get_userdata() ):

	?>
<table>
    <tr>
        <th><?php _e( 'User ID', 'groundhogg' ) ?></th>
        <td><a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $contact->get_user_id() ) ) ?>"><?php echo '#' . $contact->get_user_id(); ?></a></td>
    </tr>
    <tr>
        <th><?php _e( 'Username', 'groundhogg' ) ?></th>
        <td><a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $contact->get_user_id() ) ) ?>"><?php echo $contact->get_userdata()->user_login; ?></a></td>
    </tr>
    <tr>
        <th><?php _e( 'Email', 'groundhogg' ) ?></th>
        <td><?php esc_attr_e( $contact->user->user_email ); ?></td>
    </tr>
    <tr>
        <th><?php _e( 'Display Name', 'groundhogg' ) ?></th>
        <td><?php esc_attr_e( $contact->user->display_name ); ?></td>
    </tr>
    <tr>
        <th><?php _e( 'Roles', 'groundhogg' ) ?></th>
        <td><?php esc_html_e( \Groundhogg\andList( array_map( '\Groundhogg\get_role_display_name', $contact->user->roles ) ) ) ?></td>
    </tr>
</table>
    <?php if ( ! \Groundhogg\contact_and_user_match( $contact, $contact->user ) ): ?>
        <p><?php echo \Groundhogg\html()->e( 'a', [
                'href' => \Groundhogg\action_url( 'unlink_user', [ 'contact' => $contact->get_id() ] )
            ], __( 'Unlink this user', 'groundhogg' ) ) ?></p>
    <?php endif; ?>
<?php else: ?>
	<p><?php _e( 'This contact does not have a WordPress user account.', 'groundhogg' ); ?></p>
	<form id="create-user-form" action="<?php echo admin_url( 'user-new.php' ); ?>" method="post">
		<input type="hidden" name="createuser" value="1">
		<input type="hidden" name="first_name" value="<?php esc_attr_e( $contact->get_first_name() ); ?>">
		<input type="hidden" name="last_name" value="<?php esc_attr_e( $contact->get_last_name() ); ?>">
		<input type="hidden" name="email" value="<?php esc_attr_e( $contact->get_email() ); ?>">
		<input type="hidden" name="user_login" value="<?php esc_attr_e( $contact->get_email() ); ?>">
		<p>
			<button type="submit" class="gh-button secondary create-user-account">
				<?php _e( 'Create User Account' ); ?>
			</button>
		</p>
	</form>

<?php endif;
