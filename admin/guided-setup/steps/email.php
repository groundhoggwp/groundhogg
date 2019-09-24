<?php

namespace Groundhogg\Admin\Guided_Setup\Steps;

use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\License_Manager;
use Groundhogg\Plugin;
use function Groundhogg\isset_not_empty;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */
class Email extends Step {

	public function get_title() {
		return _x( 'Sending Email', 'guided_setup', 'groundhogg' );
	}

	public function get_slug() {
		return 'email_info';
	}

	public function get_description() {
		if ( ! Plugin::$instance->sending_service->has_dns_records() ):
			return _x( 'See below to setup the Groundhogg Sending Service.', 'guided_setup', 'groundhogg' );
		else:
			return _x( 'There are different ways to send email with Groundhogg! Choose one below.', 'guided_setup', 'groundhogg' );
		endif;
	}

	public function get_content() {

		/* Will check to see if they've gone through the process */
		if ( ! Plugin::$instance->sending_service->has_dns_records() ):
			?>
            <h3><?php _e( 'Send Email & SMS With The Groundhogg Sending Service!' ); ?></h3>
            <img src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/email-credits.png'; ?>" width="300"
                 style="float: left; margin: 5px 20px 0 0;border: 1px solid #ededed">
            <p><?php _ex( 'You can send your emails <b>and text messages</b> using our Groundhogg Sending Service to get faster delivery times and improved deliverability. Get your <b>first 1000 credits free!</b>', 'guided_setup', 'groundhogg' ); ?></p>
            <p><a target="_blank" class="button button-secondary"
                  href="https://www.groundhogg.io/downloads/email-credits/"><?php _ex( 'Learn more...', 'guided_setup', 'groundhogg' ); ?></a>
            </p>
            <div style="margin-top: 60px;">
                <p>
					<?php submit_button( _x( 'Already have an account? Connect & Activate!', 'guided_setup', 'groundhogg' ), 'primary', 'gh_active_email', false ); ?>
					<?php echo html()->wrap( _x( 'Don\'t have an account? Sign Up Now!', 'guided_setup', 'groundhogg' ), 'a', [
						'href'   => 'https://www.groundhogg.io/register/',
						'target' => '_blank',
						'class'  => 'button button-secondary'
					] ); ?>
                </p>
            </div>
            <!--        <p class="description">--><?php //_ex( '', 'guided_setup', 'groundhogg' );
			?><!--</p>-->
            <div class="wp-clearfix"></div>
            <hr>
            <h3><?php _e( 'Premium' ); ?></h3>
            <style>
                .premium-smtp-plugins .postbox{
                    width: 49%;
                    display: inline-block;
                }

            </style>
            <div class="premium-smtp-plugins">
			<?php

            $smtp_plugins = License_Manager::get_store_products( array(
	            'tag' => [ 150 ],
            ) );

            foreach ( $smtp_plugins->products as $plugin ){
                License_Manager::extension_to_html( $plugin );
            }

            ?>
            </div>
            <hr>
            <h3><?php _e( 'Alternatives' ); ?></h3>
            <div class="postbox" style="padding: 10px">
                <img src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/recommended/wp-mail-smtp.png'; ?>" width="300"
                     style="float: left; margin: 10px 20px 0 0;border: 1px solid #ededed">
                <p><?php _ex( 'You can send your emails using an <b>SMTP Service</b> using an SMTP plugin like WP Mail SMTP. This is recommended if you do not use our service.', 'guided_setup', 'groundhogg' ); ?></p>
                <p>
                    <a target="_blank" class="button button-primary"
                       href="<?php echo admin_url( 'plugin-install.php?s=WP+Mail+SMTP&tab=search&type=term' ); ?>"><?php _ex( 'Get WP Mail SMTP', 'guided_setup', 'groundhogg' ); ?></a>
                    <a target="_blank" class="button button-secondary"
                       href="<?php echo admin_url( 'plugin-install.php?s=SMTP&tab=search&type=term' ); ?>"><?php _ex( 'Browse Others...', 'guided_setup', 'groundhogg' ); ?></a>
                </p>
                <div class="wp-clearfix"></div>
            </div>
            <div class="postbox" style="padding: 10px">
                <img src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/recommended/wp-ses.jpg'; ?>" width="300"
                     style="float: left; margin: 10px 20px 0 0;border: 1px solid #ededed">
                <p><?php _ex( 'You can send your emails using <b>Amazon SES</b> which is very cost effective and provides a high deliverability rating, although is more difficult to setup.', 'guided_setup', 'groundhogg' ); ?></p>
                <p>
                    <a target="_blank" class="button button-primary"
                       href="<?php echo admin_url( 'plugin-install.php?s=wp+ses&tab=search&type=term' ); ?>"><?php _ex( 'Get WP SES', 'guided_setup', 'groundhogg' ); ?></a>
                    <a target="_blank" class="button button-secondary"
                       href="<?php echo admin_url( 'plugin-install.php?s=ses&tab=search&type=term' ); ?>"><?php _ex( 'Browse Others...', 'guided_setup', 'groundhogg' ); ?></a>
                </p>
                <div class="wp-clearfix"></div>
            </div>
		<?php

		/* They have */
		else:

			Plugin::$instance->sending_service->get_dns_table();

		endif;
	}

	/**
	 * Listen for the que to redirect to Groundhogg's Oauth Method.
	 *
	 * @return bool
	 */
	public function save() {
		if ( get_request_var( 'gh_active_email' ) ) {
			$redirect_to = sprintf( 'https://www.groundhogg.io/wp-login.php?doing_oauth=true&redirect_to=%s', urlencode( admin_url( 'admin.php?page=gh_guided_setup&action=connect_to_gh&step=' . $this->get_current_step_id() ) ) );
			set_transient( 'gh_listen_for_connect', 1, HOUR_IN_SECONDS );
			wp_redirect( $redirect_to );
			die();
		}

		return true;
	}

}