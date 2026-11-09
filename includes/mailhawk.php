<?php

namespace Groundhogg;

use Groundhogg\Admin\Card_Sections;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Recommends MailHawk on the email settings, and installs it in one click
 *
 * Connecting is left to MailHawk's own screen, so that it always matches the version of MailHawk that is installed.
 */
class Mailhawk {

	/**
	 * The notice id the recommendation is dismissed by, per user
	 */
	const DISMISS_ID = 'mailhawk-settings';

	/**
	 * Constructor.
	 *
	 * @return void
	 * @since 3.36.1
	 *
	 */
	public function __construct() {

		if ( self::is_disabled() ) {
			return;
		}

		add_action( 'wp_ajax_groundhogg_mailhawk_remote_install', array( $this, 'ajax_callback_remote_install' ) );
	}

	/**
	 * Whether the recommendation is turned off, by white labeling or the groundhogg/disable_mailhawk filter
	 *
	 * @return bool
	 */
	public static function is_disabled() {

		/**
		 * Disable the MailHawk recommendation and installer
		 *
		 * @param bool $disabled Whether or not this class is disabled.
		 *
		 * @since 3.36.1
		 *
		 */
		return is_white_labeled() || apply_filters( 'groundhogg/disable_mailhawk', false );
	}

	/**
	 * Whether MailHawk is active and connected to the service
	 *
	 * @return bool
	 */
	public static function is_connected() {
		return function_exists( 'mailhawk_is_connected' ) && mailhawk_is_connected();
	}

	/**
	 * MailHawk's own page, which is where a site is connected when it isn't yet
	 *
	 * @return string
	 */
	public static function connect_url() {

		if ( function_exists( '\MailHawk\mailhawk_admin_page' ) ) {
			return \MailHawk\mailhawk_admin_page();
		}

		return admin_url( 'tools.php?page=mailhawk' );
	}

	/**
	 * Ajax callback for installing the MailHawk plugin.
	 *
	 * @return void
	 * @since 3.36.1
	 *
	 * @hook wp_ajax_groundhogg_mailhawk_remote_install
	 *
	 */
	public function ajax_callback_remote_install() {

		$ret = $this->do_remote_install();

		// the upgrader may have printed something, which would break the JSON
		if ( ob_get_level() ) {
			ob_clean();
		}

		wp_send_json( $ret, ! empty( $ret['status'] ) ? $ret['status'] : 200 );
	}

	/**
	 * Install and activate MailHawk, then say where to go to connect it
	 *
	 * @return array
	 * @since 3.36.1
	 *
	 */
	public function do_remote_install() {

		if ( ! current_user_can( 'install_plugins' ) || ! wp_verify_nonce( get_request_var( 'nonce' ), 'install_mailhawk' ) ) {
			return array(
				'code'    => 'mailhawk_install_unauthorized',
				'message' => __( 'You do not have permission to perform this action.', 'groundhogg' ),
				'status'  => 403,
			);
		}

		$install = $this->install();

		if ( is_wp_error( $install ) ) {
			return array(
				'code'    => $install->get_error_code(),
				'message' => $install->get_error_message(),
				'status'  => 400,
			);
		}

		return array(
			'redirect' => esc_url_raw( self::connect_url() ),
		);
	}

	/**
	 * Install / Activate the MailHawk plugin.
	 *
	 * @return \WP_Error|true
	 * @since 3.36.1
	 *
	 */
	private function install() {

		if ( defined( 'MAILHAWK_VERSION' ) ) {
			return true;
		}

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$is_mailhawk_installed = false;

		foreach ( get_plugins() as $path => $details ) {
			if ( false === strpos( $path, '/mailhawk.php' ) ) {
				continue;
			}
			$is_mailhawk_installed = true;
			$activate              = activate_plugin( $path );
			if ( is_wp_error( $activate ) ) {
				return $activate;
			}
			break;
		}

		$install = null;
		if ( ! $is_mailhawk_installed ) {

			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			include_once ABSPATH . 'wp-admin/includes/file.php';
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			// Use the WordPress Plugins API to get the plugin download link.
			$api = plugins_api(
				'plugin_information',
				array(
					'slug' => 'mailhawk',
				)
			);
			if ( is_wp_error( $api ) ) {
				return $api;
			}

			// Use the AJAX upgrader skin to quietly install the plugin.
			$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
			$install  = $upgrader->install( $api->download_link );
			if ( is_wp_error( $install ) ) {
				return $install;
			}

			if ( ! $install ) {
				return new \WP_Error( 'mailhawk_install_failed', __( 'MailHawk could not be installed. Please try again, or install it from the Plugins page.', 'groundhogg' ) );
			}

			$activate = activate_plugin( $upgrader->plugin_info() );
			if ( is_wp_error( $activate ) ) {
				return $activate;
			}
		}

		// Final check to see if MailHawk is available.
		if ( ! defined( 'MAILHAWK_VERSION' ) ) {
			return new \WP_Error( 'mailhawk_not_found', __( 'MailHawk plugin not found. Please try again.', 'groundhogg' ), $install );
		}

		return true;
	}

	/**
	 * The status of a connected site, or the recommendation and the button to connect one
	 *
	 * Prints nothing for someone who can't install plugins on a site that isn't connected, or who dismissed the
	 * recommendation, so the section is left out.
	 */
	public function settings_connect_ui() {

		if ( self::is_connected() ) {

			Card_Sections::row( __( 'Status', 'groundhogg' ), '', function () {

				if ( function_exists( '\MailHawk\mailhawk_is_suspended' ) && \MailHawk\mailhawk_is_suspended() ) {
					?>
                    <p><span class="pill red"><?php esc_html_e( 'Sending paused', 'groundhogg' ); ?></span></p>
                    <p><?php
						/* translators: 1: opening anchor tag, 2: closing anchor tag */
						printf( esc_html__( 'Your MailHawk account isn\'t sending email right now. %1$sGo to MailHawk%2$s to turn sending back on.', 'groundhogg' ),
							'<a href="' . esc_url( self::connect_url() ) . '">',
							'</a>'
						); ?></p>
					<?php
					return;
				}

				?>
                <p><span class="pill green"><?php esc_html_e( 'Connected', 'groundhogg' ); ?></span></p>
                <p><?php
					/* translators: 1: opening anchor tag to MailHawk's settings, 2: opening anchor tag to the MailHawk account, 3: closing anchor tag */
					printf( esc_html__( 'This site sends its email with MailHawk. See your email log in %1$sMailHawk%3$s, or %2$smanage your account%3$s.', 'groundhogg' ),
						'<a href="' . esc_url( self::connect_url() ) . '">',
						'<a href="https://mailhawk.io/account/" target="_blank" rel="noopener noreferrer">',
						'</a>'
					); ?></p>
				<?php
			} );

			return;
		}

		if ( ! current_user_can( 'install_plugins' ) || notices()->is_dismissed( self::DISMISS_ID ) ) {
			return;
		}

		Card_Sections::row( '', '', function () {
			?>
            <div class="display-flex column gap-10" style="max-width: 600px">
                <p>
                    <b><?php esc_html_e( 'Get your email to the inbox with MailHawk.', 'groundhogg' ); ?></b>
					<?php esc_html_e( 'MailHawk is a mail service built for delivery, from the team behind Groundhogg. Connect this site and your email stops depending on your web host.', 'groundhogg' ); ?>
                </p>
                <ul class="styled">
                    <li><?php esc_html_e( 'Authenticated email sent from your own domain, so it reaches the inbox.', 'groundhogg' ); ?></li>
                    <li><?php esc_html_e( 'Risky recipients are held back before bounces and spam complaints hurt your reputation.', 'groundhogg' ); ?></li>
                    <li><?php esc_html_e( 'A complete log of every email that was sent, and a way to resend it.', 'groundhogg' ); ?></li>
                </ul>
                <div class="display-flex gap-10 align-center">
					<?php if ( defined( 'MAILHAWK_VERSION' ) ): ?>
                        <a class="gh-button primary" href="<?php echo esc_url( self::connect_url() ); ?>"><?php esc_html_e( 'Connect MailHawk', 'groundhogg' ); ?></a>
					<?php else: ?>
                        <button type="button" class="gh-button primary" id="groundhogg-mailhawk-connect"><?php esc_html_e( 'Connect MailHawk', 'groundhogg' ); ?></button>
					<?php endif; ?>
                    <a class="gh-button secondary text" id="groundhogg-mailhawk-dismiss"
                       href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'notice', self::DISMISS_ID ), 'gh_dismiss_notice' ) ); ?>"><?php esc_html_e( 'No thanks', 'groundhogg' ); ?></a>
                </div>
                <p class="description"><?php
					if ( defined( 'MAILHAWK_VERSION' ) ) {
						esc_html_e( 'You\'ll sign in or create a MailHawk account, then come straight back. Until you connect, your site keeps sending email the way it does now.', 'groundhogg' );
					} else {
						esc_html_e( 'This installs the free MailHawk plugin from WordPress.org. Then you\'ll sign in or create a MailHawk account, and come straight back. Until you connect, your site keeps sending email the way it does now.', 'groundhogg' );
					}
					?></p>
            </div>
			<?php
		} );

		$this->output_js();
	}

	/**
	 * Dismiss the recommendation without leaving the page, and install MailHawk with ajax, then go to its page to connect
	 *
	 * @return void
	 * @since 3.36.1
	 *
	 */
	public function output_js() {
		?>
        <script>
          ( () => {
            // without the notices api the link dismisses it and comes back to the page
            document.getElementById('groundhogg-mailhawk-dismiss').addEventListener('click', e => {
              if (!window.Groundhogg?.notices) {
                return
              }

              e.preventDefault()
              Groundhogg.notices.dismiss(<?php echo wp_json_encode( self::DISMISS_ID ); ?>)
              e.currentTarget.closest('.gh-settings-section').remove()
            })

            const btn = document.getElementById('groundhogg-mailhawk-connect')

            // MailHawk is installed already, the button is a link to connect it
            if (!btn) {
              return
            }

            btn.addEventListener('click', e => {
              e.preventDefault()

              btn.disabled = true
              btn.classList.add('loading-dots')
              btn.textContent = <?php echo wp_json_encode( __( 'Installing MailHawk', 'groundhogg' ) ); ?>

              const fail = message => {
                btn.disabled = false
                btn.classList.remove('loading-dots')
                btn.textContent = <?php echo wp_json_encode( __( 'Connect MailHawk', 'groundhogg' ) ); ?>

                if (window.Groundhogg?.element?.dialog) {
                  Groundhogg.element.dialog({ type: 'error', message })
                }
                else {
                  window.alert(message)
                }
              }

              jQuery.post(ajaxurl, {
                action: 'groundhogg_mailhawk_remote_install',
                nonce : <?php echo wp_json_encode( wp_create_nonce( 'install_mailhawk' ) ); ?>,
              }, res => {
                if (res && res.redirect) {
                  window.location.href = res.redirect
                  return
                }

                fail(<?php echo wp_json_encode( __( 'MailHawk could not be installed. Please try again, or install it from the Plugins page.', 'groundhogg' ) ); ?>)
              }).fail(jqxhr => {
                fail(jqxhr.responseJSON?.message || <?php echo wp_json_encode( __( 'MailHawk could not be installed. Please try again, or install it from the Plugins page.', 'groundhogg' ) ); ?>)
              })
            })
          } )()
        </script>
		<?php
	}

	/**
	 * @var Mailhawk;
	 */
	public static $instance;

	/**
	 * Instance.
	 *
	 * Ensures only one instance of the plugin class is loaded or can be loaded.
	 *
	 * @return Mailhawk An instance of the plugin class.
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Clone.
	 *
	 * Disable class cloning and throw an error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object. Therefore, we don't want the object to be cloned.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'groundhogg' ), '2.0.0' );
	}

	/**
	 * Wakeup.
	 *
	 * Disable unserializing of the class.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Something went wrong.', 'groundhogg' ), '2.0.0' );
	}

}
