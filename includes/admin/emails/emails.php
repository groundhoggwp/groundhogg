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

function wpfn_render_emails_table()
{

    if ( ! class_exists( 'WPFN_Emails_Table' ) ){
        include dirname( __FILE__ ) . '/class-emails-table.php';
    }

    $emails_table = new WPFN_Emails_Table();

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo __('Emails', 'groundhogg');?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=emails&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
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


if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_GET['action'] ) && $_GET['action'] === 'trash' ){

    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'trash' ) ){

        wpfn_update_email( intval( $_GET[ 'email' ] ), 'email_status', 'trash' );

        ?><div class="notice notice-success is-dismissible"><p><?php _e( 'Trashed email', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_emails_table();

    } else {

        ?><div class="notice notice-error is-dismissible"><p><?php _e( 'Could not trash email', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_emails_table();

    }

} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {

    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'delete' ) ){

        wpfn_delete_email( intval( $_GET[ 'email' ] ) );

        ?><div class="notice notice-success is-dismissible"><p><?php _e( 'Deleted email', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_emails_table();

    } else {

        ?><div class="notice notice-error is-dismissible"><p><?php _e( 'Could not delete email', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_emails_table();
    }
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'restore' ) {

    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'restore' ) ){

        wpfn_update_email( intval( $_GET[ 'email' ] ), 'email_status', 'draft' );

        ?><div class="notice notice-success is-dismissible"><p><?php _e( 'Restored email', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_emails_table();

    } else {

        ?><div class="notice notice-error is-dismissible"><p><?php _e( 'Could not restore email', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_emails_table();
    }
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {

    include dirname( __FILE__ ) . '/email-editor.php';

} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'add'  ) {

    include dirname( __FILE__ ) . '/add-email.php';

} else {

    wpfn_render_emails_table();

}