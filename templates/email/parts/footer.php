<?php

use function Groundhogg\do_replacements;
use function Groundhogg\html;
use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email = the_email();

$custom_text = get_option( 'gh_custom_email_footer_text' );

$alignment = $email->get_alignment();

?>

<?php if ( ! empty( $custom_text ) ): ?>
	<div class="pre-footer-content">
		<?php echo wpautop( $custom_text ); ?>
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
		$tel ? html()->e( 'a', [ 'href' => 'tel: ' . $tel ], $tel ) : false,
		$privacy_policy ? html()->e( 'a', [ 'href' => $privacy_policy ], __( 'Privacy Policy' ) ) : false,
		$terms ? html()->e( 'a', [ 'href' => $terms ], __( 'Terms' ) ) : false,
	] );

	?>
	<p><?php echo $business_name ?></p>
	<p><?php echo $address ?></p>
	<p><?php echo implode( ' | ', $links ); ?></p>
	<?php include __DIR__ . '/unsubscribe.php' ?>

	<?php include __DIR__ . '/affiliate-link.php' ?>
</div>