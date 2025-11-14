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
		<span class="preview-text"><?php echo esc_html( $email->get_merged_pre_header() );?></span>
        <span class="white-space">
        <?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- string literal HTML
		echo str_repeat( '&zwnj; &nbsp; &zwj; &#8199; &shy; ', 100 ); ?></span>
	</div>
<?php
endif;

do_action( 'groundhogg/templates/email/preview-text/after' );
