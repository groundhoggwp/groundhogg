<?php

use Groundhogg\Plugin;
use Groundhogg\Preferences;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_date_time_format;
use function Groundhogg\html;

/**
 * @var $contact \Groundhogg\Contact
 */
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
	    <?php if ( trim( $contact->get_full_name() ) ): ?>
        <div class="full-name align-left-space-between">
			<?php dashicon_e( 'admin-users' ); ?>
            <span><?php esc_html_e( $contact->get_full_name() ); ?></span>
        </div>
	    <?php endif; ?>
        <div class="email align-left-space-between">
			<?php dashicon_e( 'email' ); ?>
            <div>
				<?php echo html()->e( 'a', [
					'id'   => 'send-email',
					'href' => 'mailto:' . $contact->get_email(),
				], $contact->get_email() ) ?>
                <span class="pill <?php echo $contact->is_marketable() ? 'green' : 'red'; ?>">
                    <?php echo Preferences::get_preference_pretty_name( $contact->get_optin_status() ); ?>
                </span>
				<?php if ( ! $contact->is_marketable() ): ?>
                    <p class="marketable-reason">
						<?php echo Plugin::instance()->preferences->get_optin_status_text( $contact ) ?>
                    </p>
				<?php endif; ?>
            </div>
        </div>
		<?php if ( $contact->get_phone_number() || $contact->get_mobile_number() ): ?>
            <div class="align-left-space-between">
				<?php if ( $contact->get_phone_number() ): ?>
                    <div class="phone align-left-space-between">
						<?php dashicon_e( 'phone' ); ?>
						<?php echo html()->e( 'a', [ 'href' => 'tel:' . $contact->get_phone_number() ], $contact->get_phone_number() ) ?>
						<?php if ( $contact->get_phone_extension() ): ?>
                            <span class="extension">
                                <?php printf( __( 'ext. %s', 'groundhogg' ), $contact->get_phone_extension() ) ?>
                            </span>
						<?php endif; ?>
                    </div>
				<?php endif; ?>
				<?php if ( $contact->get_mobile_number() ): ?>
                    <div class="mobile align-left-space-between">
						<?php dashicon_e( 'smartphone' ); ?>
						<?php echo html()->e( 'a', [ 'href' => 'tel:' . $contact->get_mobile_number() ], $contact->get_mobile_number() ) ?>
                    </div>
				<?php endif; ?>
            </div>
		<?php endif; ?>
		<?php if ( count( $contact->get_address() ) > 0 ): ?>
            <div class="location align-left-space-between" title="<?php esc_attr_e( 'Location', 'groundhogg' ); ?>">
				<?php dashicon_e( 'admin-site' ); ?>
                <div class="address">
					<?php echo html()->e( 'a', [
						'href'   => 'https://www.google.com/maps/place/' . implode( ',+', $contact->get_address() ),
						'target' => '_blank'
					], implode( ', ', $contact->get_address() ) ) ?>
                </div>
            </div>
		<?php endif; ?>
        <div class="localtime align-left-space-between" title="<?php esc_attr_e( 'Local time', 'groundhogg' ); ?>">
			<?php dashicon_e( 'clock' ); ?><?php echo date_i18n( get_date_time_format(), $contact->get_local_time() ) ?>
        </div>
		<?php do_action( 'groundhogg/admin/contact/basic_details', $contact ); ?>
    </div>
</div>
