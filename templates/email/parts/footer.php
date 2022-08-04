<?php

use function Groundhogg\do_replacements;
use function Groundhogg\html;

$custom_text = get_option( 'gh_custom_email_footer_text' );

if ( $custom_text ): ?>
    <div class="pre-footer">
		<?php echo wpautop( $custom_text ); ?>
    </div>
<?php endif; ?>

<div class="footer">
	<?php

	$business_name  = sprintf( "&copy; %s", get_option( 'gh_business_name', get_bloginfo() ) );
	$address        = do_replacements( '{business_address}' );
	$tel            = get_option( 'gh_phone' );
	$terms          = get_option( 'gh_terms' );
	$privacy_policy = get_option( 'gh_privacy_policy' );

	$links = array_filter( [
		$tel ? html()->e( 'a', [ 'href' => 'tel: ' . $tel ], $tel ) : false,
		$privacy_policy ? html()->e( 'a', [ 'href' => $privacy_policy ], __( 'Privacy Policy' ) ) : false,
		$terms ? html()->e( 'a', [ 'href' => $terms ], __( 'Terms' ) ) : false,
	] );

	?>
    <p><?php echo $business_name ?></p>
    <p><?php echo $address ?></p>
    <p><?php echo implode( ' | ', $links ); ?></p>
    <p><?php printf( __( 'Don\'t want these emails? %s.', 'groundhogg' ), html()->e( 'a', [
		    'href' => $email->get_unsubscribe_link()
	    ], __( 'Unsubscribe', 'groundhogg' ) ) ) ?></p>

	<?php include __DIR__ . '/affiliate-link.php' ?>
</div>
<?php include __DIR__ . '/open-tracking.php' ?>
