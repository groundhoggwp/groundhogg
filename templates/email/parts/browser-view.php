<?php

use function Groundhogg\is_browser_view;
use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_browser_view() && the_email()->browser_view_enabled() ):
	?>
	<div class="browser-view" style="text-align: center">
        <a style="text-decoration: none; background-color: #F6F9FB; padding: 6px 12px; border-radius: 5px; display: inline-block;" href="<?php echo esc_url_raw(  the_email()->browser_view_link() ); ?>">
            <?php _e( apply_filters( 'groundhogg/email_template/browser_view_text', __( 'View this email in your browser.', 'groundhogg' ) ), 'groundhogg' ); ?>
        </a>
	</div>
<?php
endif;