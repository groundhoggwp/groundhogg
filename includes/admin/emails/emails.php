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

if ( isset( $_GET['action'] ) && $_GET['action'] === 'trash' ){
    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'trash' ) ){
        wpfn_update_email( intval( $_GET[ 'email' ] ), 'email_status', 'trash' );
    }
    $sendback = remove_query_arg( array('action','_wpnonce'), wp_get_referer() );
    wp_redirect( add_query_arg( array( 'notice'=> 'trashed', 'emails' => intval( $_GET[ 'email' ] ) ), $sendback ) );
    die();

} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {
    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'delete' ) ){
        wpfn_delete_email( intval( $_GET[ 'email' ] ) );
    }
    $sendback = remove_query_arg( array('action','_wpnonce'), wp_get_referer() );
    wp_redirect( add_query_arg( array( 'notice'=> 'deleted', 'emails' => intval( $_GET[ 'email' ] ) ), $sendback ) );
    die();
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'restore' ) {
    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'restore' ) ){
        wpfn_update_email( intval( $_GET[ 'email' ] ), 'email_status', 'draft' );
    }
    $sendback = remove_query_arg( array('action','_wpnonce'), wp_get_referer() );
    wp_redirect( add_query_arg(array( 'notice'=> 'restored', 'emails' => intval( $_GET[ 'email' ] ) ), $sendback ) );
    die();
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
    include dirname( __FILE__ ) . '/email-editor.php';
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'add'  ) {
    include dirname( __FILE__ ) . '/add-email.php';
} else {
    if ( ! class_exists( 'WPFN_Emails_Table' ) ){
        include dirname( __FILE__ ) . '/class-emails-table.php';
    }

    $emails_table = new WPFN_Emails_Table();

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo __('Emails', 'groundhogg');?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=emails&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
        <?php $notice = isset( $_GET[ 'notice' ] )? $_GET[ 'notice' ] : '';
        $item_count = isset( $_GET['emails'] )? count( explode( ',', urldecode( $_GET['emails'] ) ) ) : 0;
        switch ( $notice ):
            case 'trashed':
                ?><div class="notice notice-success is-dismissible"><p><?php echo $item_count . ' ' .  __( 'emails trashed.', 'groundhogg' );?></p></div><?php
                break;
            case 'restored':
                ?><div class="notice notice-success is-dismissible"><p><?php echo $item_count . ' ' .  __( 'emails restored.', 'groundhogg' );?></p></div><?php
                break;
            case 'deleted':
                ?><div class="notice notice-success is-dismissible"><p><?php echo $item_count . ' ' .  __( 'emails deleted permanently.', 'groundhogg' );?></p></div><?php
                break;
            default:
        endswitch; ?>
        <hr class="wp-header-end">
        <?php $emails_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Emails ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="Search Contacts">
            </p>
            <?php $emails_table->prepare_items(); ?>
            <?php $emails_table->display(); ?>
        </form>
    </div>
    <?php
}