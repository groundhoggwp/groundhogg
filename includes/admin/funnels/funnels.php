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

if ( isset( $_GET['action'] ) && $_GET['action'] === 'archive' ){
    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'archive' ) ){
        wpfn_update_funnel( intval( $_GET[ 'funnel' ] ), 'funnel_status', 'archived' );
    }
    $sendback = remove_query_arg( array('action','_wpnonce', 'funnel'), wp_get_referer() );
    wp_redirect( add_query_arg( array( 'notice' => 'archived', 'funnels' => intval( $_GET[ 'funnel' ] ) ), $sendback ) );
    die();
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {
    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'delete' ) ){
        wpfn_delete_funnel( intval( $_GET[ 'funnel' ] ) );
    }
    $sendback = remove_query_arg( array('action','_wpnonce', 'funnel'), wp_get_referer() );
    wp_redirect( add_query_arg( array( 'notice' => 'deleted', 'funnels' => intval( $_GET[ 'funnel' ] ) ), $sendback ) );
    die();
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'restore' ) {
    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'restore' ) ){
        wpfn_update_funnel( intval( $_GET[ 'funnel' ] ), 'funnel_status', 'inactive' );
    }
    $sendback = remove_query_arg( array('action','_wpnonce', 'funnel'), wp_get_referer() );
    wp_redirect( add_query_arg( array( 'notice' => 'restored', 'funnels' => intval( $_GET[ 'funnel' ] ) ), $sendback ) );
    die();
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
    include dirname( __FILE__ ) . '/funnel-builder.php';
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'add'  ) {
    include dirname( __FILE__ ) . '/add-funnel.php';
} else {
    if ( ! class_exists( 'WPFN_Funnel_Builder' ) ){
        include dirname( __FILE__ ) . '/class-funnels-table.php';
    }
    $funnels_table = new WPFN_Funnels_Table();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo __('Funnels', 'groundhogg');?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_funnels&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
        <?php $notice = isset( $_GET[ 'notice' ] )? $_GET[ 'notice' ] : '';
        $item_count = isset( $_GET['funnels'] )? count( explode( ',', urldecode( $_GET['funnels'] ) ) ) : 0;
        switch ( $notice ):
            case 'archived':
                ?><div class="notice notice-success is-dismissible"><p><?php echo $item_count . ' ' .  __( 'funnels archived.', 'groundhogg' );?></p></div><?php
                break;
            case 'restored':
                ?><div class="notice notice-success is-dismissible"><p><?php echo $item_count . ' ' .  __( 'funnels restored.', 'groundhogg' );?></p></div><?php
                break;
            case 'deleted':
                ?><div class="notice notice-success is-dismissible"><p><?php echo $item_count . ' ' .  __( 'funnels deleted permanently.', 'groundhogg' );?></p></div><?php
                break;
            default:
        endswitch; ?>
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