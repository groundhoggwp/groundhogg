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
class Extension_Updater
{

    protected $file_map = [
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

            if ( ! isset_not_empty( $this->file_map, $plugin_id ) ){
                continue;
            }

            $subpath = $this->file_map[$plugin_id];
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
                'url' => home_url()
            ]);

        }

    }

}