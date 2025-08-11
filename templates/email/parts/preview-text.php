<?php

use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email = the_email();

do_action( 'groundhogg/templates/email/preview-text/before' );

if ( $email->get_pre_header() ):
	?>
	<div style="display: none; max-height: 0px; overflow: hidden;">
		<?php echo esc_html( $email->get_merged_pre_header() ) ?>
	</div>
	<div style="display: none; max-height: 0px; overflow: hidden;">
		<?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- string literal HTML
        echo str_repeat( '&#847; &zwnj; &nbsp; &#8199; &shy;', 5 ); ?>
	</div>
<?php
endif;

do_action( 'groundhogg/templates/email/preview-text/after' );
