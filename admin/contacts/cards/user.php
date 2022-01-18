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

use function Groundhogg\time_ago;

if ( $contact->get_user_id() ):

	?>
	<p><?php \Groundhogg\dashicon_e( 'admin-users' ); ?> <b><?php _e( 'Details', 'groundhogg' ) ?></b></p>
	<ul class="info-list">
		<li>
			<span class="label"><?php _e( 'User ID', 'groundhogg' ) ?></span>
			<span class="data">
			        <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $contact->get_user_id() ) ) ?>"><?php echo '#' . $contact->get_user_id(); ?></a>
		        </span>
		</li>
		<li>
			<span class="label"><?php _e( 'Username', 'groundhogg' ) ?></span>
			<span class="data">
			        <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $contact->get_user_id() ) ) ?>"><?php echo $contact->get_userdata()->user_login; ?></a>
		        </span>
		</li>
	</ul>
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
