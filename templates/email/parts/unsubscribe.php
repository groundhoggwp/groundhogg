<?php

use function Groundhogg\html;
use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email = the_email();

if ( ! $email->is_transactional() ): ?>
	<p><?php printf( __( 'Don\'t want these emails? %s.', 'groundhogg' ), html()->e( 'a', [
			'href' => $email->get_unsubscribe_link()
		], __( 'Unsubscribe', 'groundhogg' ) ) ) ?></p>
<?php endif; ?>