<?php

use Groundhogg\Email;
use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! \Groundhogg\is_option_enabled( 'gh_disable_open_tracking' ) && the_email()->get_event() && the_email()->get_event()->exists() ): ?>
    <img alt="" style="visibility: hidden" width="0" height="0"
         src="<?php echo esc_url( the_email()->get_open_tracking_link() ); ?>">
<?php endif; ?>
