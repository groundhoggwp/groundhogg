<?php

/**
 * If the transactional email system in Groundhogg is set to something other than the WordPress default, load the email service.
 *
 * @return bool
 */
function gh_is_wp_mail_set_to_default() {
	return Groundhogg_Email_Services::get_wordpress_service() === 'wp_mail';
}

if ( function_exists( 'wp_mail' ) && ! gh_is_wp_mail_set_to_default() ) :
	add_action( 'admin_notices', 'gh_wp_mail_already_defined_notice' );
elseif ( ! function_exists( 'wp_mail' ) && ! gh_is_wp_mail_set_to_default() ) :

	/**
	 * Wrapper for wp_mail, only used in the context of marketing emails, not transactional ones.
	 *
	 * @param string|array $to          Array or comma-separated list of email addresses to send message.
	 * @param string       $subject     Email subject
	 * @param string       $message     Message contents
	 * @param string|array $headers     Optional. Additional headers.
	 * @param string|array $attachments Optional. Files to attach.
	 *
	 * @return bool Whether the email contents were sent successfully.
	 */
	function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
		return Groundhogg_Email_Services::send_wordpress( $to, $subject, $message, $headers, $attachments );
	}
endif;

/**
 * This notice will show to let the user know multiple email plugins are installed and may be conflicting.
 * Solutions:
 *  - Deactivate offending plugin.
 *  - Only use SendGrid for marketing emails.
 */
function gh_wp_mail_already_defined_notice() {

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$plugin_file = \Groundhogg\extrapolate_wp_mail_plugin();

	$is_pluggable_file = strpos( $plugin_file, '/wp-includes/pluggable.php' ) !== false;

	$deactivate_link = '';

	if ( $plugin_file && ! $is_pluggable_file && current_user_can( 'deactivate_plugin', $plugin_file ) ) {

		$deactivate_link = sprintf(
			'<a href="%s" class="button" aria-label="%s">%s</a>',
			wp_nonce_url( 'plugins.php?action=deactivate&amp;plugin=' . urlencode( $plugin_file ), 'deactivate-plugin_' . $plugin_file ),
			/* translators: %s: Plugin name. */
			esc_attr( __( 'Deactivate the conflicting plugin', 'groundhogg' ) ),
			__( 'Deactivate the conflicting plugin', 'groundhogg' )
		);

	}

	$disable_in_settings_link = sprintf(
		'<a href="%s" class="button" aria-label="%s">%s</a>',
		\Groundhogg\admin_page_url( 'gh_settings', [ 'tab' => 'email' ] ),
		/* translators: %s: Plugin name. */
		esc_attr( __( "Set WordPress email service to WordPress Default", 'groundhogg' ) ),
		__( "Set WordPress email service to <b>WordPress Default</b>", 'groundhogg' )
	);

	$current_service_name = Groundhogg_Email_Services::get_name( Groundhogg_Email_Services::get_wordpress_service() );

	?>
    <div class="notice notice-warning is-dismissible">
        <img class="alignleft" height="90" style="margin: 10px 10px 3px 3px"
             src="<?php echo esc_url( GROUNDHOGG_ASSETS_URL . 'images/phil-oops.png' ); ?>" alt="Phil">
        <p>
			<?php printf( __( '<b>Attention:</b> It looks like another plugin is overwriting the <code>wp_mail</code> function. This means <b>%s</b> will not be able to send your WordPress emails.', 'groundhogg' ), $current_service_name ); ?>
        </p>
        <p>
			<?php _e( '<code>wp_mail</code> is defined in:', 'groundhogg' ); ?>
            <code><?php echo esc_html( $plugin_file ); ?></code>
        </p>
        <p><?php echo $deactivate_link; ?>&nbsp;<?php echo $disable_in_settings_link ?></p>
		<?php if ( $is_pluggable_file ) : ?>
            <p>
				<?php _e( 'One of your plugins is including pluggable functions from WordPress before it should. This is causing a conflict with <b>Groundhogg/b> and potentially other plugins you are using. You will have to deactivate your plugins one-by-one until this notice goes away to discover which plugin is causing the issue.', 'groundhogg' ); ?>
            </p>
		<?php endif; ?>
        <div class="wp-clearfix"></div>
    </div>
	<?php
}
