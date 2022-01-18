<?php

use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_date_time_format;
use function Groundhogg\html;

?>
<div class="inside align-left-space-between align-top">
	<!-- Photo -->
	<div class="contact-picture">
		<?php echo html()->e( 'img', [
			'class'  => 'profile-picture',
			'title'  => __( 'Profile Picture' ),
			'width'  => 100,
			'height' => 100,
			'src'    => $contact->get_profile_picture()
		] ); ?>
	</div>
	<div class="basic-details">
		<!-- FIRST -->
		<div
			class="full-name"><?php dashicon_e( 'admin-users' ); ?><?php echo $contact->get_full_name(); ?></div>
		<div class="email">
			<?php dashicon_e( 'email' ); ?><?php echo html()->e( 'a', [
				'id'   => 'send-email',
				'href' => 'mailto:' . $contact->get_email(),
			], $contact->get_email() ) ?>
			<span
				class="pill <?php echo $contact->is_marketable() ? 'green' : 'red'; ?>"><?php echo Preferences::get_preference_pretty_name( $contact->get_optin_status() ); ?></span>
			<?php if ( ! $contact->is_marketable() ):?>
			<p class="marketable-reason">
				<?php echo Plugin::instance()->preferences->get_optin_status_text( $contact ) ?>
			</p>
			<?php endif; ?>
		</div>
		<?php if ( $contact->get_phone_number() || $contact->get_mobile_number() ): ?>

			<div class="align-left-space-between">
				<?php if ( $contact->get_phone_number() ): ?>
					<div
						class="phone"><?php dashicon_e( 'phone' ); ?><?php echo html()->e( 'a', [ 'href' => 'tel:' . $contact->get_phone_number() ], $contact->get_phone_number() ) ?>
						<?php if ( $contact->get_phone_extension() ): ?>
							<span
								class="extension"><?php printf( __( 'ext. %s', 'groundhogg' ), $contact->get_phone_extension() ) ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( $contact->get_mobile_number() ): ?>
					<div
						class="mobile"><?php dashicon_e( 'smartphone' ); ?><?php echo html()->e( 'a', [ 'href' => 'tel:' . $contact->get_mobile_number() ], $contact->get_mobile_number() ) ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( count( $contact->get_address() ) > 0 ): ?>
			<div class="location" title="<?php esc_attr_e( 'Location', 'groundhogg' ); ?>">
				<?php dashicon_e( 'admin-site' ); ?>
				<div class="address">
					<?php echo html()->e( 'a', [
						'href'   => 'https://www.google.com/maps/place/' . implode( ',+', $contact->get_address() ),
						'target' => '_blank'
					], implode( ', ', $contact->get_address() ) ) ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="localtime" title="<?php esc_attr_e( 'Local time', 'groundhogg' ); ?>">
			<?php dashicon_e( 'clock' ); ?><?php echo date_i18n( get_date_time_format(), $contact->get_local_time() ) ?>
		</div>
		<?php do_action( 'groundhogg/admin/contact/basic_details', $contact ); ?>
	</div>
</div>