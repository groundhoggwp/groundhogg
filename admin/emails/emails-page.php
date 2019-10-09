<?php
namespace Groundhogg\Admin\Emails;

use Groundhogg;
use Groundhogg\Admin\Admin_Page;
use Groundhogg\Plugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * View Emails
 *
 * Allow the user to view & edit the emails
 * Contains add, save, delete, etc for the admin functions...
 *
 * @package     Admin
 * @subpackage  Admin/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Emails_Page extends Admin_Page
{

    protected function add_ajax_actions()
    {
        do_action( 'groundhogg/admin/email/add_ajax_actions', $this );
    }

    protected function add_additional_actions()
    {
        if ( $this->get_current_action() === 'edit' ){
            add_action( 'in_admin_header' , array( $this, 'prevent_notices' )  );
        }

        do_action( 'groundhogg/admin/email/add_additional_actions', $this );
    }

	public function admin_title($admin_title, $title)
    {
        switch ( $this->get_current_action() ){
            case 'add':
                $admin_title = sprintf( "%s &lsaquo; %s", __( 'Add' ),  $admin_title );
                break;
            case 'edit':
                $email_id = Groundhogg\get_request_var( 'email' );

                if ( ! $email_id ){
                    wp_die( 'Invalid Email Id.' );
                }

                $email = Plugin::$instance->utils->get_email( absint( $email_id ) );
                $admin_title = sprintf( "%s &lsaquo; %s &lsaquo; %s", $email->get_title(),  __( 'Edit' ),  $admin_title );
                break;
        }

        return $admin_title;
    }

    public function get_slug()
    {
        return 'gh_emails';
    }

    public function get_name()
    {
        return _x( 'Emails', 'page_title', 'groundhogg' );
    }

    public function get_cap()
    {
        return 'edit_emails';
    }

    public function get_item_type()
    {
        return 'email';
    }

    public function get_priority()
    {
        return 15;
    }

    public function scripts()
    {
        if ( $this->get_current_action() === 'edit' ){
	        wp_enqueue_style( 'groundhogg-admin-email-editor-plain' );
	        wp_enqueue_script( 'groundhogg-admin-email-editor-plain' );

            wp_localize_script( 'groundhogg-admin-email-editor-plain', 'Email', [
		        'send_test_prompt' => __( 'Send test email to...', 'groundhogg' ),
                'email_id' => absint( Groundhogg\get_request_var( 'email' ) ),
	        ] );

            remove_editor_styles();

            add_filter( 'mce_css', function ( $mce_css ){
                return $mce_css . ', ' . GROUNDHOGG_ASSETS_URL . 'css/admin/email-wysiwyg-style.css';
            } );
        }

        if ( in_array( $this->get_current_action(), ['add', 'edit' ] ) ){
            wp_enqueue_script( 'groundhogg-admin-iframe' );
            wp_enqueue_style( 'groundhogg-admin-iframe' );
        }

	    remove_editor_styles();

	    wp_enqueue_style( 'groundhogg-admin' );
    }

    /**
     * Add help tab at top of screen
     *
     * @return mixed|void
     */
    public function help(){}

    /**
     * Get the title of the current page
     */
    function get_title()
    {
        switch ( $this->get_current_action() ){
            case 'add':
                return _x( 'Add Email' , 'page_title', 'groundhogg' );
                break;
            case 'edit':
                return _x( 'Edit Email' ,'page_title', 'groundhogg' );
                break;
            case 'view':
            default:
                return _x( 'Emails', 'page_title', 'groundhogg' );
                break;
        }
    }

    /**
     * @return array|array[]
     */
    protected function get_title_actions()
    {
        $broadcast_args =  [ 'action' => 'add', 'type' => 'email' ];

        if ( $email = Groundhogg\get_request_var( 'email' ) ){
            $broadcast_args[ 'email' ] = absint( $email );
        }

        return [
            [
                'link' => $this->admin_url( [ 'action' => 'add' ] ),
                'action' => __( 'Add New', 'groundhogg' ),
                'target' => '_self',
            ],
            [
                'link' => Plugin::$instance->admin->get_page( 'broadcasts' )->admin_url( $broadcast_args ),
                'action' => __( 'Broadcast', 'groundhogg' ),
                'target' => '_self',
            ]
        ];
    }

    public function process_restore()
    {
        if ( ! current_user_can( 'edit_emails' ) ){
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ) //todo
        {
            Plugin::$instance->dbs->get_db('emails')->update( $id , [ 'status' => 'draft' ] );
        }

        $this->add_notice(
            esc_attr( 'restored' ),
            sprintf( "%s %d %s",
                __( 'Restored' ),
                count( $this->get_items() ),
                __( 'Emails', 'groundhogg' ) ),
            'success'
        );

        return false;
    }

    public function process_empty_trash()
    {

        if ( ! current_user_can( 'delete_emails' ) ){
            $this->wp_die_no_access();
        }

        $emails = Plugin::$instance->dbs->get_db('emails')->query( [ 'status' => 'trash' ] );

        foreach ( $emails as $email ){
            Plugin::$instance->dbs->get_db('emails')->delete( $email->ID );
        }

        $this->add_notice(
            esc_attr( 'deleted' ),
            sprintf( "%s %d %s",
                __( 'Deleted' ),
                count( $emails ),
                __( 'Emails', 'groundhogg' ) ),
            'success'
        );

        return false;
    }

    public function process_delete()
    {
        if ( ! current_user_can( 'delete_emails' ) ){
            $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ){
            if ( ! Plugin::$instance->dbs->get_db( 'emails' )->delete( $id ) ){
                return new \WP_Error( 'unable_to_delete_email', "Something went wrong deleting the email." );
            }
        }

        $this->add_notice(
            esc_attr('deleted'),
            sprintf(_nx('Deleted %d email', 'Deleted %d emails', count($this->get_items()), 'notice', 'groundhogg'), count($this->get_items())),
            'success'
        );

        return false;
    }

    public function process_trash()
    {
        if ( ! current_user_can( 'edit_emails' ) ){
           $this->wp_die_no_access();
        }

        foreach ( $this->get_items() as $id ) {
            Plugin::$instance->dbs->get_db('emails')->update( $id , [ 'status' => 'trash' ] );
        }

        $this->add_notice(
            esc_attr( 'trashed' ),
            sprintf( "%s %d %s",
                __( 'Trashed' ),
                count( $this->get_items() ),
                __( 'Emails', 'groundhogg' ) ),
            'success'
        );

        return false;
    }

	/**
     * Process the editing actions of the email
     *
	 * @return bool|\WP_Error
	 */
    public function process_edit()
    {
        if ( ! current_user_can( 'edit_emails' ) ){
            $this->wp_die_no_access();
        }

        $id = absint( Groundhogg\get_request_var( 'email' ) );
        $email = Plugin::$instance->utils->get_email( $id );

        $args = array();

        $status = sanitize_text_field( Groundhogg\get_request_var( 'email_status', 'draft' ) );

        if ( $status === 'draft' ) {
            $this->add_notice( 'email-in-draft-mode', __( 'Emails cannot be sent while in DRAFT mode.', 'groundhogg' ), 'warning' );
        }

        $from_user = absint( Groundhogg\get_request_var( 'from_user' ) );

        if ( $from_user > 0 ){
            $user = get_userdata( $from_user );
            if ( !  Groundhogg\email_is_same_domain( $user->user_email ) ){ //todo
                $this->add_notice( 'email-cross-domain-warning', sprintf( __( 'You are sending this email from an email address (%s) which does not belong to this server. This may cause deliverability issues and harm your sender reputation.', 'groundhogg' ), $user->user_email ), 'warning' );
            }
        }

        $subject = sanitize_text_field( Groundhogg\get_request_var( 'subject' ) );
        $pre_header = sanitize_text_field( Groundhogg\get_request_var( 'pre_header' ) );
        $content = apply_filters( 'groundhogg/admin/emails/sanitize_email_content', Groundhogg\get_request_var( 'email_content' ) );

        $args[ 'status' ] = $status;
        $args[ 'from_user' ] = $from_user;
        $args[ 'subject' ] = $subject;
        $args[ 'title' ] = sanitize_text_field( Groundhogg\get_request_var( 'email_title', $subject ) );
        $args[ 'pre_header' ] = $pre_header;
        $args[ 'content' ] = $content;

        $args[ 'last_updated' ] = current_time( 'mysql' );
        $args[ 'is_template' ] = key_exists( 'save_as_template', $_POST ) ? 1 : 0;


        if ( $email->update( $args ) ){
            $this->add_notice( 'email-updated', __( 'Email Updated.', 'groundhogg' ), 'success' );
        } else {
            return new \WP_Error( 'unable_to_update_email', 'Unable to update email!' );
        }

        $email->update_meta( 'alignment', sanitize_text_field( Groundhogg\get_request_var( 'email_alignment' ) ) );
        $email->update_meta( 'browser_view', boolval( Groundhogg\get_request_var( 'browser_view' ) ) );
        $email->update_meta( 'reply_to_override', sanitize_email( Groundhogg\get_request_var( 'reply_to_override' ) ) );

        if ( Groundhogg\get_request_var( 'use_custom_alt_body' ) ){
            $email->update_meta( 'use_custom_alt_body', 1 );
            $email->update_meta( 'alt_body', wp_strip_all_tags( Groundhogg\get_request_var( 'alt_body' ) ) );
        } else {
            $email->delete_meta( 'use_custom_alt_body' );
            $email->delete_meta( 'alt_body' );
        }

        if ( Groundhogg\get_request_var( 'test_email' ) ){

            if ( ! current_user_can( 'send_emails' ) ){
                $this->wp_die_no_access();
            }

            $test_email = sanitize_email( Groundhogg\get_request_var( 'test_email', wp_get_current_user()->user_email ) );

            $contact = new Groundhogg\Contact( [ 'email' => $test_email ] );

            $email->enable_test_mode();

            $sent = $email->send( $contact );

            update_user_meta( get_current_user_id(), 'preferred_test_email', $test_email );

            if ( ! $sent || is_wp_error( $sent ) ){
                $error = is_wp_error( $sent ) ? $sent : new \WP_Error( 'oops', "Failed to send test." );
                $this->add_notice( $error );
            } else {
                $this->add_notice(
                    esc_attr( 'sent-test' ),
                    sprintf( "%s %s",
                        __( 'Sent test email to', 'groundhogg' ),
                        $contact->get_email() ),
                    'success'
                );
            }
        }

        return true;
    }

    public function view()
    {
        if ( ! class_exists( 'Emails_Table' ) ){
            include dirname(__FILE__) . '/emails-table.php';
        }

        $emails_table = new Emails_Table();

        $emails_table->views();

        $this->search_form( __( 'Search Emails', 'groundhogg' ) );

        ?>
        <form method="post">
            <?php $emails_table->prepare_items(); ?>
            <?php $emails_table->display(); ?>
        </form>
        <?php
    }

    public function edit()
    {
        if ( ! current_user_can( 'edit_emails' ) ){
            $this->wp_die_no_access();
        }

        include dirname(__FILE__) . '/email-editor.php';
    }

    /**
     * Prevent notices from other plugins appearing on the edit funnel screen as the break the format.
     */
    public function prevent_notices()
    {
        remove_all_actions( 'network_admin_notices' );
        remove_all_actions( 'user_admin_notices' );
        remove_all_actions( 'admin_notices' );
    }
}