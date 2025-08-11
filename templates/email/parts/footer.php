<?php

use function Groundhogg\array_to_css;
use function Groundhogg\do_replacements;
use function Groundhogg\html;
use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email = the_email();

if ( $email->has_footer_block() ) {
	return;
}

$custom_text = get_option( 'gh_custom_email_footer_text' );

$alignment = $email->get_alignment();

$p_style = [
	'text-align'  => $alignment,
	'line-height' => 1,
	'margin'      => '0.5em 0'
];

if ( $email->get_template() === 'framed' ) {
	$p_style['alignment'] = 'center';
	$p_style['color']     = $email->get_meta( 'footerFontColor' ) ?: '#000';
}

$_p = function ( $content, $style = [] ) use ( $p_style ) {
	html( 'p', [
		'style' => array_merge( $p_style, $style ),
	], $content );
};

$show_custom_footer_text = apply_filters( 'groundhogg/templates/email/parts/footer/show_custom_footer_text', ! empty( $custom_text ), $email );

?>
<?php if ( $show_custom_footer_text ): ?>
	<?php
	$custom_footer_text_style = apply_filters( 'groundhogg/templates/email/parts/footer/custom_footer_text_style', [
		'margin-top' => '40px'
	], $email );
	?>
	<div class="pre-footer-content" style="<?php echo esc_attr( array_to_css( $custom_footer_text_style ) ) ?>">
		<?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- dealt with wp_kses
        echo \Groundhogg\email_kses( wpautop( do_replacements( $custom_text, $email->get_contact() ) ) ); ?>
	</div>
<?php endif; ?>
<div class="footer" style="margin-top: 40px;">
	<?php
	$business_name  = sprintf( "&copy; %s", get_option( 'gh_business_name', get_bloginfo() ) );
	$address        = do_replacements( '{business_address}' );
	$tel            = get_option( 'gh_phone' );
	$terms          = get_option( 'gh_terms' );
	$privacy_policy = get_option( 'gh_privacy_policy' ) ?: get_privacy_policy_url();

	$links = array_filter( [
		$tel ? html()->e( 'a', [ 'href' => 'tel: ' . $tel ], esc_html( $tel ) ) : false,
		$privacy_policy ? html()->e( 'a', [ 'href' => $privacy_policy ], esc_html__( 'Privacy Policy', 'groundhogg' ) ) : false,
		$terms ? html()->e( 'a', [ 'href' => $terms ], esc_html__( 'Terms', 'groundhogg' ) ) : false,
	] );

	$_p( $business_name );
	$_p( $address );
	$_p( implode( ' | ', $links ) );

	if ( ! $email->is_transactional() ) {
		/* translators: 1: unsubscribe link HTML */
		$_p( sprintf( esc_html__( 'Don\'t want these emails? %s.', 'groundhogg' ), html()->e( 'a', [
			'href' => $email->get_unsubscribe_link()
		], esc_html__( 'Unsubscribe', 'groundhogg' ) ) ) );
	}

	include __DIR__ . '/affiliate-link.php' ?>

</div>
<?php

do_action( 'groundhogg/templates/email/footer/after' );
