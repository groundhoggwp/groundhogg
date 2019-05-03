<?php
namespace Groundhogg\Admin\Network_settings;
use Groundhogg\Admin\Admin_Page;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Plugin Settings
 *
 * This  is your fairly typical settigns page.
 * It's a BIT of a mess, but I digress.
 *
 * @package     Admin
 * @subpackage  Admin/Settings
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Network_Settings_Page extends Admin_Page
{

    public $notices;

	public function __construct()
    {

        $this->notices = WPGH()->notices;

		add_action( 'network_admin_menu', array( $this, 'register' ) );

    }

    /* Register the page */
    public function register()
    {
        $page = add_menu_page(
            'Groundhogg',
            'Groundhogg',
            'manage_options',
            'groundhogg_network',
            array( $this, 'settings_options' )
        );

        add_action( "load-" . $page, array( $this, 'help' ) );

        add_action( 'network_admin_edit_groundhogg', array( $this, 'save_options' ) );

    }

    /* Display the help bar */
    public function help()
    {
        //todo
    }

	public function settings_options()
    {


        ?>
		<div class="wrap">
			<h1>Groundhogg <?php _e( 'Settings' ); ?></h1>
			<?php $this->notices->notices(); ?>
			<form method="POST" enctype="multipart/form-data" action="edit.php?action=groundhogg">
                <?php wp_nonce_field( 'groundhogg-validate' ); ?>

                <table class="form-table">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <?php _e( 'Enable Multisite Database', 'groundhogg' ); ?>
                        </th>
                        <td>
                            <?php echo WPGH()->html->checkbox( array(
                                'label' => 'Enable.',
                                'name'  => 'enable_global_db',
                                'value' => 1,
                                'checked' => get_site_option( 'gh_global_db_enabled' )
                            )); ?>
                            <p class="description"><?php _e( 'This will enable a global database for all your sites in this multisite installation. WARNING: This means the same information will be used among ALL subsites. Only enable if you do not host clients on your network.', 'groundhogg' ) ?></p>
                            <p class="description"><?php _e( 'You will have to manage all plugin settings from your MAIN blog, and any Groundhogg extensions should be made network active as well.', 'groundhogg' ) ?></p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php do_action( 'wpgh_multisite_options' ); ?>

                <p class="submit">
                    <?php submit_button( _x('Save Changes', 'action', 'groundhogg') ); ?>
                </p>

			</form>
		</div> <?php
	}

	public function save_options()
    {
        check_admin_referer( 'groundhogg-validate' ); // Nonce security check

        if ( isset( $_POST[ 'enable_global_db' ] ) ){
            update_site_option( 'gh_global_db_enabled', $_POST['enable_global_db'] );
        } else {
            delete_site_option( 'gh_global_db_enabled' );
        }

        do_action( 'wpgh_save_multisite_options' );

        $this->notices->add( 'updated', __( 'Settings Updated!' ) );

        wp_redirect( add_query_arg( array(
            'page' => 'groundhogg_network',
            'updated' => true ), network_admin_url('admin.php')
        ));

        exit;
    }

    protected function add_ajax_actions()
    {
        // TODO: Implement add_ajax_actions() method.
    }

    protected function add_additional_actions()
    {
        // TODO: Implement add_additional_actions() method.
    }

    public function get_slug()
    {
        // TODO: Implement get_slug() method.
    }

    public function get_name()
    {
        // TODO: Implement get_name() method.
    }

    public function get_cap()
    {
        // TODO: Implement get_cap() method.
    }

    public function get_item_type()
    {
        // TODO: Implement get_item_type() method.
    }

    public function scripts()
    {
        // TODO: Implement scripts() method.
    }

    public function view()
    {
        // TODO: Implement view() method.
    }


}