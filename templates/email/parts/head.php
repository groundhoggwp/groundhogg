<?php

use Groundhogg\Email;
use function Groundhogg\is_browser_view;
use function Groundhogg\the_email;

$email = the_email();

/**
 * @var $email Email
 */

$template    = $email->get_template();
$email_title = $email->get_html_head_title();

global $campaign;
global $broadcast;
global $event;

?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?php echo esc_html( html_entity_decode( $email_title ) ); ?></title>
    <base target="_blank">
    <style id="global-styles">
        <?php load_css( 'email' ); ?>
        <?php do_action( "groundhogg/templates/email/{$template}/style" ); ?>
        <?php do_action( "groundhogg/templates/email/head/style" ); ?>
    </style>
    <style id="responsive">
        <?php load_css( 'responsive' ); ?>
    </style>
    <style id="block-styles">
        <?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html() breaks `div > span` selectors
		echo wp_strip_all_tags( $email->get_css() ); ?>
    </style>
	<?php do_action( "groundhogg/templates/email/{$template}/head" ); ?>
	<?php do_action( "groundhogg/templates/email/head" ); ?>
	<?php

	if ( is_browser_view() && ( ( isset( $campaign ) && isset( $broadcast ) ) || isset( $event ) ) ) {
		?>
        <style id="archive">
            <?php load_css( 'archive' ); ?>
        </style>
		<?php
	}

	if ( is_browser_view() ){
		?><style>
            .hide-in-browser{
                display: none;
                visibility: hidden;
            }
        </style><?php
	}

    ?>
</head>
