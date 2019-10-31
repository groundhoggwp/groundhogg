<?php
namespace Groundhogg;


/**
 * Class Extension_Updater
 *
 * Old versions of plugins cannot update if they are not updated to 2.0
 * This class will allow them to update still, hopefully.
 *
 * @package Groundhogg
 */
class Extension_Upgrader
{

    protected static $file_map = [
        210     => 'groundhogg-wooc/groundhogg-wooc.php',
        216     => 'groundhogg-edd/groundhogg-edd.php',
        219     => 'groundhogg-gravity/groundhogg-gravity.php',
        251     => 'groundhogg-cf7/groundhogg-cf7.php',
        447     => 'groundhogg-contracts/groundhogg-contracts.php',
        777     => 'groundhogg-wp-simple-pay/groundhogg-wp-simple-pay.php',
        948     => 'groundhogg-email-countdown-timers/groundhogg-email-countdown-timers.php',
        954     => 'groundhogg-proof/groundhogg-proof.php',
        1167    => 'groundhogg-form-styling/groundhogg-form-styling.php',
        1342    => 'groundhogg-forminator/groundhogg-forminator.php',
        1350    => 'groundhogg-formidable/groundhogg-formidable.php',
        1358    => 'groundhogg-ninja/groundhogg-ninja.php',
        1529    => 'groundhogg-zapier/groundhogg-zapier.php',
        1595    => 'groundhogg-wpforms/groundhogg-wpforms.php',
        3008    => 'groundhogg-pipeline/groundhogg-pipeline.php',
        3461    => 'groundhogg-appointments/groundhogg-appointments.php',
        4631    => 'groundhogg-replacements/groundhogg-replacements.php',
        4707    => 'groundhogg-wpep/groundhogg-wpep.php',
        4754    => 'groundhogg-white-label/groundhogg-white-label.php',
        5535    => 'groundhogg-twilio/groundhogg-twilio.php',
        5617    => 'groundhogg-aws/groundhogg-aws.php',
        6355    => 'groundhogg-caldera/groundhogg-caldera.php',
        7132    => 'groundhogg-lead-scoring/groundhogg-lead-scoring.php',
        15036   => 'groundhogg-lifterlms/groundhogg-lifterlms.php',
        15028   => 'groundhogg-learndash/groundhogg-learndash.php',
        15016   => 'groundhogg-content-restriction/groundhogg-content-restriction.php',
    ];

    /**
     * Extension_Updater constructor.
     */
    public function __construct()
    {
        add_action( 'admin_init', [$this, 'check_for_updates' ] );
    }

    /**
     * Get the existing licenses from the licenses page
     *
     * @return array
     */
    protected function get_licensed_extensions()
    {
        return get_option("gh_extensions", array());
    }

    /**
     * Check for updates.
     */
    public function check_for_updates()
    {

        $extensions = $this->get_licensed_extensions();

        foreach ($extensions as $plugin_id => $extension) {

            $plugin_id = absint( $plugin_id );

            // Plugin is updated, leave alone.
            if ( in_array( $plugin_id, Extension::$extension_ids ) ){
                continue;
            }

            $license = get_array_var($extension, 'license');

            if ( ! isset_not_empty( self::$file_map, $plugin_id ) ){
                continue;
            }

            $subpath = self::$file_map[$plugin_id];
            $file_path = WP_PLUGIN_DIR . '/' . $subpath;

            if ( ! file_exists( $file_path ) ){
                continue;
            }

            $data = get_plugin_data( $file_path );

            if ( ! class_exists('\GH_EDD_SL_Plugin_Updater') ){
                require_once dirname(__FILE__) . '/lib/edd/GH_EDD_SL_Plugin_Updater.php';
            }

            $updater = new \GH_EDD_SL_Plugin_Updater(License_Manager::$storeUrl, $file_path, [
                'version' => $data[ 'Version' ],
                'license' => $license,
                'item_id' => $plugin_id,
                'author' => $data[ 'Author' ],
                'url' => home_url()
            ]);

        }

    }

    /**
     * Remotely install an extension
     *
     * @param $item_id
     * @param $license
     *
     * @return bool|\WP_Error
     */
    public static function remote_install( $item_id, $license='' )
    {
        if ( empty( $license ) ){
            // Get the first available license
            $license = License_Manager::get_license();
        }

        $plugin = get_array_var( self::$file_map, $item_id );

        if ( ! $plugin ){
            return new \WP_Error( 'invalid_plugin_id', 'Invalid plugin ID provided.' );
        }

        $is_installed = false;

        foreach ( get_plugins() as $path => $details ) {
            if ( false === strpos( $path, $plugin ) ) {
                continue;
            }

            $is_installed = true;

            $activate = activate_plugin( $path );

            if ( is_wp_error( $activate ) ) {
                return $activate;
            }

            break;
        }

        $install = null;

        if ( ! $is_installed ) {

            // Activate the download
            $activated = License_Manager::activate_license_quietly( $license, $item_id );

            if ( ! $activated || is_wp_error( $activated ) ){
                return $activated;
            }

            if ( ! class_exists( '\Plugin_Upgrader' ) ){
                include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
                include_once ABSPATH . 'wp-admin/includes/file.php';
                include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            }

            // Get the package info from the Store API
            $api = License_Manager::get_version( $item_id, $license );

            if ( is_wp_error( $api ) ) {
                return $api;
            }

            if ( ! get_array_var( $api, 'download_link' ) ){
                return new \WP_Error( 'error', 'Could not retrieve download package', $api );
            }

            // Use the AJAX upgrader skin to quietly install the plugin.
            $upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );

            $install  = $upgrader->install( get_array_var( $api, 'download_link' ) );

            if ( is_wp_error( $install ) ) {
                return $install;
            }

            $activate = activate_plugin( $upgrader->plugin_info() );

            if ( is_wp_error( $activate ) ) {
                return $activate;
            }
        }

        return true;


    }


}