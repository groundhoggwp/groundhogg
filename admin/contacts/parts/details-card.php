<?php

use Groundhogg\Contact;
use Groundhogg\Plugin;
use Groundhogg\Preferences;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_email_address_hostname;
use function Groundhogg\html;
use function Groundhogg\is_free_email_provider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @var $contact Contact
 */
?>

<div class="contact-details">
    <!-- Photo -->
    <div class="contact-picture">
	    <?php
	    html()->e( 'img', [
			'class'  => 'profile-picture has-box-shadow',
			'title' => esc_html__( 'Profile Picture', 'groundhogg' ),
			'width'  => 100,
			'height' => 100,
			'src'    => $contact->get_profile_picture()
	    ], null, true, true ); ?>
    </div>
    <!-- FIRST -->
    <h1 id="contact-full-name">
		<?php echo esc_html( trim( $contact->get_full_name() ) ?: $contact->get_email() ); ?></span>
    </h1>
    <div class="gh-panel">
        <div id="contact-more-actions" class="display-flex gap-5" style="padding: 20px 20px 20px 0"></div>
        <div class="clearfix"></div>
        <div class="basic-details inside display-flex column gap-10" style="padding-top: 0">
            <div id="contact-email" class="email align-left-space-between">
				<?php dashicon_e( 'email' ); ?>
                <div>
	                <?php html()->e( 'a', [
						'id'   => 'send-email',
						'href' => 'mailto:' . $contact->get_email(),
	                ], esc_html( $contact->get_email() ), false, true ) ?>
                    <span class="pill <?php echo $contact->is_marketable() ? 'green' : 'red'; ?>">
                    <?php echo esc_html( Preferences::get_preference_pretty_name( $contact->get_optin_status() ) ); ?>
                </span>
					<?php if ( ! $contact->is_marketable() ): ?>
                        <p class="description gh-text red">
	                        <?php echo esc_html( Plugin::instance()->preferences->get_optin_status_text( $contact ) ) ?>
                        </p>
					<?php endif; ?>
                </div>
            </div>
			<?php if ( ! is_free_email_provider( $contact->get_email() ) ):
				$hostname = get_email_address_hostname( $contact->get_email() );
				$url = 'https://' . $hostname;
				?>
                <div id="contact-website" class="url align-left-space-between"
                     title="<?php esc_attr_e( 'Website URL', 'groundhogg' ); ?>">
					<?php dashicon_e( 'admin-site' ); ?>
                    <div class="website-url">
	                    <?php html( 'a', [
							'href'   => $url,
							'target' => '_blank'
	                    ], esc_html( $hostname ) ); ?>
                    </div>
                </div>
			<?php endif; ?>
			<?php if ( $contact->get_phone_number() || $contact->get_mobile_number() ): ?>
                <div id="contact-phones" class="align-left-space-between">
					<?php if ( $contact->get_phone_number() ): ?>
                        <div class="phone align-left-space-between">
							<?php dashicon_e( 'phone' ); ?>
	                        <?php html( 'a', [ 'href' => 'tel:' . $contact->get_phone_number() ], esc_html( $contact->get_phone_number() ) ) ?>
							<?php if ( $contact->get_phone_extension() ): ?>
                                <span class="extension">
                                <?php
                                /* translators: 1: phone extension number */
                                echo esc_html( sprintf( __( 'ext. %s', 'groundhogg' ), $contact->get_phone_extension() ) ) ?>
                            </span>
							<?php endif; ?>
                        </div>
					<?php endif; ?>
					<?php if ( $contact->get_mobile_number() ): ?>
                        <div class="mobile align-left-space-between">
							<?php dashicon_e( 'smartphone' ); ?>
							<?php html( 'a', [ 'href' => 'tel:' . $contact->get_mobile_number() ], esc_html( $contact->get_mobile_number() ) ) ?>
                        </div>
					<?php endif; ?>
                </div>
			<?php endif; ?>
			<?php if ( count( $contact->get_address() ) > 0 ): ?>
                <div id="contact-location" class="location align-left-space-between"
                     title="<?php esc_attr_e( 'Location', 'groundhogg' ); ?>">
					<?php dashicon_e( 'location' ); ?>
                    <div class="address">
	                    <?php html( 'a', [
							'href'   => 'https://www.google.com/maps/place/' . implode( ',+', $contact->get_address() ),
							'target' => '_blank'
						], implode( ', ', $contact->get_address() ) ) ?>
                    </div>
                </div>
			<?php endif; ?>
            <div id="contact-localtime" class="localtime align-left-space-between"
                 title="<?php esc_attr_e( 'Local time', 'groundhogg' ); ?>">
				<?php dashicon_e( 'clock' ); ?><?php

				$today   = new DateTimeHelper();
				$local   = new DateTimeHelper( 'now', $contact->get_time_zone( false ) );
				$display = $today->wpDateFormat() === $local->wpDateFormat() ? $local->time_i18n() : $local->i18n();

				?><span><?php
		            /* translators: 1: local time as abbr element */
		            printf( esc_html__( 'Local time is %s', 'groundhogg' ),
			            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			            html()->e( 'abbr', [ 'title' => $local->wpDateTimeFormat() ], esc_html( $display ) ) );
					?></span>
            </div>
            <div id="contact-date-created" class="date-created align-left-space-between"
                 title="<?php esc_attr_e( 'Date created', 'groundhogg' ); ?>">
	            <?php dashicon_e( 'calendar-alt' ); ?><span><?php
		            /* translators: 1: subscription date as abbr element */
		            printf( esc_html__( 'Subscribed since %s', 'groundhogg' ),
			            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			            html()->e( 'abbr', [ 'title' => $contact->get_date_created( true )->wi18n() ], esc_html( $contact->get_date_created( true )->wpDateFormat() ) ) ); ?>
                </span>
            </div>
			<?php

			$birthday         = $contact->get_meta( 'birthday' );

			if ( $birthday ):

				$age = $contact->get_age();
				$age1         = $age + 1;
				$birthday     = new DateTimeHelper( $contact->get_meta( 'birthday' ) );
				$nextBirthday = ( clone $birthday )->modify( "+$age1 years" );

				?>
                <div id="contact-birthday" class="birthday align-left-space-between"
                     title="<?php esc_attr_e( 'Birthday', 'groundhogg' ); ?>">
					<?php dashicon_e( 'buddicons-community' ); ?><span>
                    <?php

                    /* translators: 1: human time until next birthday (abbr), 2: current age in years (abbr) */
                    printf( esc_html__( 'Birthday in %1$s, currently %2$s years old.', 'groundhogg' ),
	                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	                    html()->e( 'abbr', [ 'title' => $nextBirthday->wpDateFormat() ], esc_html( $nextBirthday->human_time_diff() ) ),
	                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	                    html()->e( 'abbr', [ 'title' => $birthday->wpDateFormat() ], esc_html( $age ) )
                    ); ?>
                </span>
                </div>
			<?php

			endif;

			do_action( 'groundhogg/admin/contact/basic_details', $contact ); ?>
        </div>
    </div>
</div>
