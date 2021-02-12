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

	$comments = get_comments( [
		'user_id' => $contact->get_user_id()
	] );

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
	<?php if ( ! empty( $comments ) ) : ?>
    <p><?php \Groundhogg\dashicon_e( 'admin-comments' ); ?> <b><?php _e( 'Comments' ) ?></b></p>
    <ul>
		<?php foreach ( $comments as $comment ): ?>
            <li>"<?php echo $comment->comment_content; ?>" - <abbr title="<?php esc_attr_e( $comment->comment_date );?>"><?php echo time_ago( $comment->comment_date ); ?></abbr></li>
		<?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php else: ?>
    <p><?php _e( 'This contact does not have a WordPress user account.', 'groundhogg' ); ?></p>
    <p>
        <button type="button" class="button button-secondary create-user-account">
			<?php _e( 'Create User Account' ); ?>
        </button>
    </p>
<?php endif;
