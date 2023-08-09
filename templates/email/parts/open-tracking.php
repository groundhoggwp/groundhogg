<?php

use Groundhogg\Email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var $email Email
 */

if ( ! \Groundhogg\is_option_enabled( 'gh_disable_open_tracking' ) ): ?>
    <img alt="" style="visibility: hidden" width="0" height="0"
         src="<?php echo esc_url( $email->get_open_tracking_link() ); ?>">
<?php endif; ?>
