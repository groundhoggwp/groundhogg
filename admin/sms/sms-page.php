<?php
namespace Groundhogg\Admin\SMS;
use Groundhogg\Admin\Admin_Page;
use Groundhogg\Plugin;
use Groundhogg\SMS;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * SMS Page
 *
 * This is the sms page, it also contains the add form since it's the same layout as the terms.php
 *
 * @package     Admin
 * @subpackage  Admin/Supperlinks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class SMS_Page extends Admin_Page
{

    //UNUSED FUNCTIONS
    public function help() {}
    public function scripts() {}
    protected function add_ajax_actions(){}
    protected function add_additional_actions() {}

    public function get_slug()
    {
        return 'gh_sms';
    }

    public function get_name()
    {
        return _x( 'SMS', 'page_title', 'groundhogg' );
    }

    public function get_cap()
    {
        return 'edit_sms';
    }

    public function get_item_type()
    {
        return 'sms';
    }

    public function get_title()
    {
        switch ($this->get_current_action()) {
            case 'edit':
                return _x( 'Edit SMS', 'page_title', 'groundhogg');
                break;
            case  'view':
            default:
                return _x('SMS', 'page_title', 'groundhogg');
        }
    }

    /**
     * @return array|array[]
     */
    protected function get_title_actions()
    {
        return [
            [
                'link' => $this->admin_url( [ 'action' => 'add' ] ),
                'action' => __( 'Add New', 'groundhogg' ),
                'target' => '_self',
            ],
            [
                'link' => Plugin::$instance->admin->get_page( 'broadcasts' )->admin_url( [ 'action' => 'add', 'type' => 'sms' ] ),
                'action' => __( 'Broadcast', 'groundhogg' ),
                'target' => '_self',
            ]
        ];
    }

    /**
     * Process add action of sms
     *
     * @return bool|\WP_Error
     */
    public function process_add()
    {
        if (!current_user_can('add_sms')) {
            $this->wp_die_no_access();
        }

        $title = sanitize_text_field(wp_unslash($_POST['title']));
        $message = sanitize_textarea_field(wp_strip_all_tags(wp_unslash($_POST['message'])));

        $args = array(
            'title' => $title,
            'message' => $message,
        );

        $sms_id = Plugin::$instance->dbs->get_db('sms')->add($args);

        if (!$sms_id) {
            return new \WP_Error( 'unable_to_add_sms', "Something went wrong while adding the SMS." );
        }
        $this->add_notice('created', _x('SMS created', 'notice', 'groundhogg'));
        return true;
    }

    /**
     * Edit SMS message.
     *
     * @return bool|\WP_Error
     */
    public function process_edit()
    {
        if (!current_user_can('edit_sms')) {
            $this->wp_die_no_access();
        }

        $id = absint( $_GET['sms'] );
        $title = sanitize_text_field(wp_unslash($_POST['title']));
        $message = sanitize_textarea_field(wp_strip_all_tags(wp_unslash($_POST['message'])));

        $args = array(
            'title' => $title,
            'message' => $message,
        );

        $result = Plugin::$instance->dbs->get_db('sms')->update($id, $args);

        if ( gisset_not_empty( $_POST, 'save_and_test' ) ){
            $sms = Plugin::$instance->utils->get_sms( $id );
            $contact = Plugin::$instance->utils->get_contact( get_current_user_id(), true );
            $result = $sms->send( $contact );

            if ( is_wp_error( $result ) ){
                $this->add_notice( $result );
            } else {
               $this->add_notice( 'test_sent', sprintf( 'Test sent to %s!', $contact->primary_phone ), 'info' );
            }
        }

        if (!$result) {
            return new \WP_Error( 'unable_to_update_sms', "Something went wrong while updating the SMS." );
        }

        $this->add_notice('updated', _x('Updated SMS.', 'notice', 'groundhogg'));

        //Return false to redirect to main page
        return false;

    }

    /**
     * Delete tags from the admin
     *
     * @return bool|\WP_Error
     */
    public function process_delete()
    {
        if (!current_user_can('delete_sms')) {
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ){
            if ( ! Plugin::$instance->dbs->get_db( 'sms' )->delete( $id ) ){
                return new \WP_Error( 'unable_to_delete_sms', "Something went wrong while deleting the SMS." );
            }
        }

        $this->add_notice(
            'deleted',
            sprintf( _nx( '%d sms deleted', '%d sms deleted', count( $this->get_items() ), 'notice', 'groundhogg' ), count( $this->get_items() ) )
        );

        return true;
    }

	public function view()
	{
		if ( ! class_exists( 'SMS_Table' ) ){
			include dirname(__FILE__) . '/sms-table.php';
		}

		$sms_table = new SMS_Table(); ?>
        <form method="post" class="search-form wp-clearfix">
        <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search SMS', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search SMS', 'groundhogg' )?>">
            </p>
        </form>
        <div id="col-container" class="wp-clearfix">
            <div id="col-left">
                <div class="col-wrap">
                    <div class="form-wrap">
                        <h2><?php _e( 'Add New SMS', 'groundhogg' ) ?></h2>
                        <form id="addsms" method="post" action="">
                            <input type="hidden" name="action" value="add">
                            <?php wp_nonce_field(); ?>
                            <div class="form-field term-name-wrap">
                                <label for="sms-title"><?php _e( 'Title', 'groundhogg' ) ?></label>
                                <input name="title" id="sms-title" type="text" value="" maxlength="100" autocomplete="off" required>
                                <p><?php _e( 'Name it something simple so you do not forget it.', 'groundhogg' ); ?></p>
                            </div>
                            <div class="form-field term-target-wrap">
                                <label for="sms-message"><?php _e( 'Message', 'groundhogg' ) ?></label>
                                <textarea rows="5" name="message" id="sms-message" autocomplete="off" required></textarea>
                                <p class="description">
	                                <?php WPGH()->replacements->show_replacements_button(); ?>
                                    <?php _e( 'Use any valid replacement codes in your text message.', 'groundhogg' ); ?>
                            </div>
                            <?php submit_button( _x( 'Add New SMS', 'action', 'groundhogg' ), 'primary', 'add_sms' ); ?>
                        </form>
                    </div>
                </div>
            </div>
            <div id="col-right">
                <div class="col-wrap">
                    <form id="posts-filter" method="post">
                        <?php $sms_table->prepare_items(); ?>
                        <?php $sms_table->display(); ?>
                    </form>
                </div>
            </div>
        </div>
		<?php
	}

	public function edit()
	{
        if ( ! current_user_can( 'edit_sms' ) ){
            $this->wp_die_no_access();
        }
		include dirname(__FILE__) . '/edit.php';
	}
}