<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Mailhawk {

	const PARTNER_ID = 1;

	/**
	 * Constructor.
	 *
	 * @return void
	 * @since 3.36.1
	 *
	 */
	public function __construct() {

		/**
		 * Disable the SendWP Connector class and settings
		 *
		 * @param bool $disabled Whether or not this class is disabled.
		 *
		 * @since 3.36.1
		 *
		 */
		if ( apply_filters( 'groundhogg/disable_mailhawk', false ) ) {
			return;
		}

		add_action( 'wp_ajax_groundhogg_mailhawk_remote_install', array( $this, 'ajax_callback_remote_install' ) );
	}


	/**
	 * Ajax callback for installing SendWP Plugin.
	 *
	 * @return void
	 * @since 3.36.1
	 *
	 * @hook wp_ajax_groundhogg_mailhawk_remote_install
	 *
	 */
	public function ajax_callback_remote_install() {

		$ret = $this->do_remote_install();
		ob_clean();
		wp_send_json( $ret, ! empty( $ret['status'] ) ? $ret['status'] : 200 );

	}

	/**
	 * Remote installation method.
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

		if ( ! defined( 'MAILHAWK_VERSION' ) ) {
			return array(
				'code'    => 'mailhawk_missing',
				'message' => 'MailHawk not installed.',
				'status'  => 400,
			);
		}

		$redirect = guided_setup_finished() ? \MailHawk\get_admin_mailhawk_uri() : admin_page_url( 'gh_guided_setup', [ 'step' => '6' ] );

		return array(
			'partner_id'   => self::PARTNER_ID,
			'register_url' => esc_url( trailingslashit( MAILHAWK_LICENSE_SERVER_URL ) ),
			'redirect_uri' => esc_url( $redirect ),
			'client_state' => esc_attr( \MailHawk\Keys::instance()->state() ),
		);

	}

	/**
	 * Install / Activate SendWP plugin.
	 *
	 * @return \WP_Error|true
	 * @since 3.36.1
	 *
	 */
	private function install() {

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

			$activate = activate_plugin( $upgrader->plugin_info() );
			if ( is_wp_error( $activate ) ) {
				return $activate;
			}
		}

		// Final check to see if SendWP is available.
		if ( ! defined( 'MAILHAWK_VERSION' ) ) {
			return new \WP_Error( 'mailhawk_not_found', __( 'MailHawk plugin not found. Please try again.', 'groundhogg' ), $install );
		}

		return true;

	}

	/**
	 * Get the "Connect" Setting field html.
	 */
	public function output_connect_button() {

		if ( function_exists( '\MailHawk\mailhawk_is_connected' ) && \MailHawk\mailhawk_is_connected() ) {

			$ret = array(
				__( 'Your site is connected to MailHawk.', 'groundhogg' ),
			);

			if ( function_exists( '\MailHawk\mailhawk_is_suspended' ) && ! \MailHawk\mailhawk_is_suspended() ) {
				$ret[] = sprintf(
				// Translators: %1$s = Opening anchor tag; %2$s = Closing anchor tag.
					esc_html__( '%1$sManage your account%2$s.', 'groundhogg' ),
					'<a href="https://mailhawk.io/account/" target="_blank" rel="noopener noreferrer">',
					'</a>'
				);
			} else {
				$ret[] = sprintf(
				// Translators: %1$s = Opening anchor tag; %2$s = Closing anchor tag.
					'<em>' . esc_html__( 'Email sending is currently disabled. %1$sVisit the MailHawk Settings%2$s to enable sending..', 'groundhogg' ) . '</em>',
					'<a href="' . admin_url( '/tools.php?page=mailhawk' ) . '">',
					'</a>'
				);
			}

			echo '<p>' . implode( ' ', $ret ) . '</p>';

			return;

		}

		echo sprintf( '<button type="button" class="button button-primary" id="groundhogg-mailhawk-connect">%s %s</button>', dashicon( 'email-alt' ), esc_html__( 'Connect MailHawk', 'groundhogg' ) );
	}

	/**
	 * Output some quick and dirty inline CSS.
	 *
	 * @return void
	 * @since 3.36.1
	 *
	 */
	public function output_css() {
		?>
        <style type="text/css">
            #wpbody #groundhogg-mailhawk-connect {
                font-size: 16px;
                height: auto;
                margin: 0 0 6px;
                padding: 8px 14px;
            }

            #groundhogg-mailhawk-connect .dashicons {
                /*vertical-align: middle;*/
                font-size: 26px;
                margin: 5px 10px 0 0;
            }
        </style>
		<?php
	}

	/**
	 * Settings UI output.
	 */
	public function settings_connect_ui() {

		$this->output_css();

		?>
        <table class="form-table">
            <tbody>
            <tr>
                <th><?php esc_html_e( 'A better way to send email.', 'groundhogg' ); ?></th>
                <td><?php $this->output_connect_button(); ?>

					<?php if ( ! function_exists( '\MailHawk\mailhawk_is_connected' ) || ! \MailHawk\mailhawk_is_connected() ) : ?>
                        <p class="description"><?php printf( esc_html__( 'Never worry about sending email again! %s takes care of everything for you for starting at $1/month!', 'groundhogg' ), '<a href="https://mailhawk.io/" target="_blank">MailHawk</a>' ); ?></p>
					<?php endif; ?>
                </td>
            </tr>
            </tbody>
        </table>
		<?php

		$this->output_js();
	}

	/**
	 * Output some quick and dirty inline JS.
	 *
	 * @return void
	 * @since 3.36.1
	 *
	 */
	public function output_js() {
		?>
        <script>
            var btn = document.getElementById("groundhogg-mailhawk-connect");
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                groundhogg_mailhawk_remote_install();
            });

            /**
             * Perform AJAX request to install SendWP plugin.
             *
             * @since 3.36.1
             *
             * @return void
             */
            function groundhogg_mailhawk_remote_install() {
                var data = {
                    "action": "groundhogg_mailhawk_remote_install",
                    "nonce": '<?php echo wp_create_nonce( 'install_mailhawk' ); ?>'
                };

                jQuery.post(ajaxurl, data, function (res) {

                    groundhogg_mailhawk_register_client(res.register_url, res.client_state, res.redirect_uri, res.partner_id);
                }).fail(function (jqxhr) {
                    if (jqxhr.responseJSON && jqxhr.responseJSON.message) {
                        alert("Error: " + jqxhr.responseJSON.message);
                        console.log(jqxhr);
                    }
                });
            }

            /**
             * Register client with SendWP.
             *
             * @since 3.36.1
             *
             * @param {string} register_url Registration URL.
             * @param {string} client_state string state for oauth.
             * @param {string} redirect_uri Client redirect URL.
             * @param {int} partner_id SendWP partner ID.
             * @return {void}
             */
            function groundhogg_mailhawk_register_client(register_url, client_state, redirect_uri, partner_id) {

                var form = document.createElement("form");
                form.setAttribute("method", "POST");
                form.setAttribute("action", register_url);

                function groundhogg_mailhawk_append_form_input(name, value) {
                    var input = document.createElement("input");
                    input.setAttribute("type", "hidden");
                    input.setAttribute("name", name);
                    input.setAttribute("value", value);
                    form.appendChild(input);
                }

                groundhogg_mailhawk_append_form_input("mailhawk_plugin_signup", "yes");
                groundhogg_mailhawk_append_form_input("state", client_state);
                groundhogg_mailhawk_append_form_input("redirect_uri", redirect_uri);
                groundhogg_mailhawk_append_form_input("partner_id", partner_id);

                document.body.appendChild(form);
                form.submit();

            }
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
	 * @return Mailhawk An instance of the class.
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
