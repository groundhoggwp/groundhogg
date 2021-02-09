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

if ( $contact->get_user_id() ):?>
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

<?php endif;
