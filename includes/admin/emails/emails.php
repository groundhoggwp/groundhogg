<?php
/**
 * View Emails
 *
 * Allow the user to view & edit the emails
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WPGH_Emails_Page
{
    function __construct()
    {
        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_emails' ){

            add_action( 'init' , array( $this, 'process_action' )  );

        }
    }

    function get_emails()
    {
        $emails = isset( $_REQUEST['email'] ) ? $_REQUEST['email'] : null;

        if ( ! $emails )
            return false;

        return is_array( $emails )? array_map( 'intval', $emails ) : array( intval( $emails ) );
    }

    function get_action()
    {
        if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) )
            return false;

        if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
            return $_REQUEST['action'];

        if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
            return $_REQUEST['action2'];

        return false;
    }

    function get_previous_action()
    {
        $action = get_transient( 'gh_last_action' );

        delete_transient( 'gh_last_action' );

        return $action;
    }

    function get_title()
    {
        switch ( $this->get_action() ){
            case 'add':
                _e( 'Add Email' , 'groundhogg' );
                break;
            case 'edit':
                _e( 'Edit Email' , 'groundhogg' );
                break;
            default:
                _e( 'Emails', 'groundhogg' );
        }
    }

    function process_action()
    {
        if ( ! $this->get_action() || ! $this->verify_action() )
            return;

        $base_url = remove_query_arg( array( '_wpnonce', 'action' ), wp_get_referer() );

        switch ( $this->get_action() )
        {
            case 'add':

                if ( isset( $_POST ) )
                {
                    do_action( 'wpgh_add_email' );
                }
                break;

            case 'trash':

                foreach ( $this->get_emails() as $id ) {
                    wpgh_update_email($id, 'email_status', 'trash');
                }

	            wpgh_add_notice(
		            esc_attr( 'trashed' ),
		            sprintf( "%s %d %s",
			            __( 'Trashed' ),
			            count( $this->get_emails() ),
			            __( 'Emails', 'groundhogg' ) ),
		            'success'
	            );

                do_action( 'wpgh_trash_emails' );

                break;

            case 'delete':

                foreach ( $this->get_emails() as $id ){
                    wpgh_delete_email( $id );
                }

	            wpgh_add_notice(
		            esc_attr( 'deleted' ),
		            sprintf( "%s %d %s",
			            __( 'Deleted' ),
			            count( $this->get_emails() ),
			            __( 'Emails', 'groundhogg' ) ),
		            'success'
	            );

                do_action( 'wpgh_delete_emails' );

                break;

            case 'restore':

                foreach ( $this->get_emails() as $id )
                {
                    wpgh_update_email( $id, 'email_status', 'draft' );
                }

	            wpgh_add_notice(
		            esc_attr( 'restored' ),
		            sprintf( "%s %d %s",
			            __( 'Restored' ),
			            count( $this->get_emails() ),
			            __( 'Emails', 'groundhogg' ) ),
		            'success'
	            );

                do_action( 'wpgh_restore_emails' );

                break;

            case 'edit':

                if ( isset( $_POST ) ){
                    do_action( 'wpgh_update_email', intval( $_GET[ 'email' ] ) );
	                wpgh_add_notice(
		                esc_attr( 'trashed' ),
		                sprintf( "%s %s",
			                __( 'Email', 'groundhogg' ),
		                __( 'Updated' ) ),
		                'success'
	                );
                }

                break;
        }

        set_transient( 'gh_last_action', $this->get_action(), 30 );

        if ( $this->get_action() === 'edit' || $this->get_action() === 'add' )
            return;

        $base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_emails() ) ), $base_url );

        wp_redirect( $base_url );
        die();
    }


    function verify_action()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) )
            return false;

        return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() )|| wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-emails' );
    }

    function table()
    {
        if ( ! class_exists( 'WPGH_Emails_Table' ) ){
            include dirname( __FILE__ ) . '/class-emails-table.php';
        }

        $emails_table = new WPGH_Emails_Table();

        $emails_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Emails ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Emails ', 'groundhogg'); ?>">
            </p>
            <?php $emails_table->prepare_items(); ?>
            <?php $emails_table->display(); ?>
        </form>

        <?php
    }

    function edit()
    {
        include dirname( __FILE__ ) . '/email-editor.php';

    }

    function add()
    {
        include dirname( __FILE__ ) . '/add-email.php';
    }

    function page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_emails&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
            <?php wpgh_notices(); ?>
            <hr class="wp-header-end">
            <?php switch ( $this->get_action() ){
                case 'add':
                    $this->add();
                    break;
                case 'edit':
                    $this->edit();
                    break;
                default:
                    $this->table();
            } ?>
        </div>
        <?php
    }
}