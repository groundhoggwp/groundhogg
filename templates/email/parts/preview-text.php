<?php

use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email = the_email();

do_action( 'groundhogg/templates/email/preview-text/before' );

if ( $email->get_pre_header() ):
	?>
	<div style="display:none;overflow:hidden;line-height:1px;opacity:0;max-height:0;max-width:0">
		<?php echo esc_html( $email->get_merged_pre_header() );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- string literal HTML
		echo str_repeat( '&nbsp;&zwnj;&ZeroWidthSpace;&zwj;&lrm;&rlm;&#xFEFF;', 100 ); ?>
	</div>
<?php
endif;

do_action( 'groundhogg/templates/email/preview-text/after' );
