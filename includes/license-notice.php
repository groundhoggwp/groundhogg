<?php

namespace Groundhogg;

class License_Notice {

	const CHECKOUT_URL = 'https://www.groundhogg.io/secure/checkout/';
	const HELPER_PLUGIN_ID = 48143;

	public function __construct() {
		add_action( 'admin_notices', [ $this, 'show_expired_license_nag' ] );
		add_action( 'admin_notices', [ $this, 'show_non_licensed_extensions_nag' ] );
	}

	/**
	 * Show an admin notice to nag users into renewing or updating their license.
	 */
	public function show_expired_license_nag() {

		if ( ! apply_filters( 'groundhogg/license_notice/show', true ) ) {
			return;
		}

		$licenses = License_Manager::get_expired_licenses();

		$license_key = array_pop( $licenses );

		if ( ! $license_key ) {
			return;
		}

		$renewal_url = add_query_arg( [
			'edd_license_key' => $license_key
		], self::CHECKOUT_URL );

		$deactivate_url = admin_url( wp_nonce_url( add_query_arg( [
			'action'  => 'deactivate_license',
			'license' => $license_key,
		], 'admin.php?page=gh_settings&tab=extensions' ) ) );

		$check_license_url = Plugin::instance()->bulk_jobs->check_licenses->get_start_url();

		?>
        <div class="notice notice-warning is-dismissible">
            <img style="float: left;margin: 12px 10px 10px 0" height="80"
                 src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/phil-340x340.png'; ?>" alt="Phil">
            <p><?php printf( __( "<b>Oops!</b> Your Groundhogg license <code>%s</code> has expired. Please renew it to continue receiving critical updates and support.", 'groundhogg' ), $license_key ) ?></p>
            <p>
                <a class="button button-primary" target="_blank"
                   href="<?php echo esc_url( $renewal_url ); ?>"><?php _e( "👉 Yes, I'd like to renew my license!", 'groundhogg' ); ?></a>
                <a class="button button-secondary"
                   href="<?php echo esc_url( $check_license_url ); ?>"><?php _e( "Verify my renewal", 'groundhogg' ); ?></a>
                <a class="button button-secondary"
                   href="<?php echo esc_url( $deactivate_url ); ?>"><?php _e( "Deactivate this license.", 'groundhogg' ); ?></a>
            </p>
            <p>
                <i><?php _e( "⚠️ By not updating your extensions, you leave your site at risk to bugs and plugin conflicts. These can cause user experience issues, or worse, crash your site and disable it altogether.", 'groundhogg' ) ?></i>
            </p>
        </div>
		<?php
	}

	/**
	 * Show a nag if there ar installed extensions that have yet to be licensed.
	 */
	public function show_non_licensed_extensions_nag() {

		if ( ! apply_filters( 'groundhogg/license_notice/show', true ) ) {
			return;
		}

		// Only check against official extensions
		$licensed  = array_intersect( array_keys( License_Manager::get_extension_licenses() ), Extension_Upgrader::get_extension_ids() );
		$installed = array_intersect( array_values( Extension::$extension_ids ), Extension_Upgrader::get_extension_ids() );

		// Licensed may be greater than installed if an extension was licensed then deactivated.
		if ( count( $installed ) <= count( $licensed ) ) {
			return;
		}

        // todo, show diff to see which extensions are missing licenses.

//		$license_page_url = in_array( self::HELPER_PLUGIN_ID, $installed ) ? admin_page_url( 'gh_extensions' ) : admin_page_url( 'gh_settings', [ 'tab' => 'extensions' ] );
		$license_page_url = admin_page_url( 'gh_settings', [ 'tab' => 'extensions' ] );

		?>
        <div class="notice notice-warning is-dismissible">
            <img style="float: left;margin: 12px 10px 10px 0" height="80"
                 src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/phil-340x340.png'; ?>" alt="Phil">
            <p><?php printf( __( "<b>License your extensions!</b> Some of your premium extensions are missing a license key. Remember to add your license key to receive critical updates and support.", 'groundhogg' ) ); ?></p>
            <p>
                <a class="button button-primary"
                   href="<?php echo esc_url( $license_page_url ); ?>"><?php _e( "Set my license key!", 'groundhogg' ); ?></a>
                <a class="button button-secondary" target="_blank"
                   href="<?php echo esc_url( 'https://groundhogg.io/pricing/' ); ?>"><?php _e( "Purchase a new license.", 'groundhogg' ); ?></a>
            </p>
            <p>
                <i><?php _e( "⚠️ By not licensing your extensions, you leave your site at risk to bugs and plugin conflicts. These can cause user experience issues, or worse, crash your site and disable it altogether.", 'groundhogg' ) ?></i>
            </p>
        </div>
		<?php

	}

}
