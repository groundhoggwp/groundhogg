<?php
/**
 * View Funnels
 *
 * Allow the user to view & edit the funnels
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function wpfn_render_funnel_table()
{
    if ( ! class_exists( 'WPFN_Funnel_Builder' ) ){
        include dirname( __FILE__ ) . '/class-funnels-table.php';
    }

    $funnels_table = new WPFN_Funnels_Table();

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo __('Funnels', 'groundhogg');?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=funnels&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
        <hr class="wp-header-end">
        <form method="post" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Funnels', 'groundhogg' ); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="Search Funnels">
            </p>
            <?php $funnels_table->views(); ?>
            <?php $funnels_table->prepare_items(); ?>
            <?php $funnels_table->display(); ?>
        </form>
    </div>
    <?php
}


if ( isset( $_GET['action'] ) && $_GET['action'] === 'archive' ){

    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'archive' ) ){

        wpfn_update_funnel( intval( $_GET[ 'funnel' ] ), 'funnel_status', 'archived' );

        ?><div class="notice notice-success is-dismissible"><p><?php _e( 'Archived Funnel', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_funnel_table();

    } else {

        ?><div class="notice notice-error is-dismissible"><p><?php _e( 'Could not archive funnel', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_funnel_table();

    }

} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {

    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'delete' ) ){

        wpfn_delete_funnel( intval( $_GET[ 'funnel' ] ) );

        ?><div class="notice notice-success is-dismissible"><p><?php _e( 'Deleted funnel', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_funnel_table();

    } else {

        ?><div class="notice notice-error is-dismissible"><p><?php _e( 'Could not delete funnel', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_funnel_table();
    }
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'restore' ) {

    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'restore' ) ){

        wpfn_update_funnel( intval( $_GET[ 'funnel' ] ), 'funnel_status', 'inactive' );

        ?><div class="notice notice-success is-dismissible"><p><?php _e( 'Restored funnel', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_funnel_table();

    } else {

        ?><div class="notice notice-error is-dismissible"><p><?php _e( 'Could not restore funnel', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_funnel_table();
    }
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {

    include dirname( __FILE__ ) . '/funnel-builder.php';

} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'add'  ) {

    include dirname( __FILE__ ) . '/add-funnel.php';

} else {

    wpfn_render_funnel_table();

}