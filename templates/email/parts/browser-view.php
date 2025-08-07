<?php

use function Groundhogg\is_browser_view;
use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_browser_view() && the_email()->browser_view_enabled() ):
	?>
	<table class="browser-view" cellspacing="0" cellpadding="0" width="100%">
		<tr>
			<td align="center" style="padding-top: 20px;">
				<table cellpadding="0" cellspacing="0">
					<tr>
						<td style="background-color: #F6F9FB; padding: 6px 12px; border-radius: 5px; text-align: center; width: min-content;">
							<a style="text-decoration: none; display: inline-block;" href="<?php echo esc_url_raw(  the_email()->browser_view_link() ); ?>">
								<?php echo esc_html( apply_filters( 'groundhogg/email_template/browser_view_text', __( 'View this email in your browser.', 'groundhogg' ) ), 'groundhogg' ); ?>
							</a>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
<?php
endif;
