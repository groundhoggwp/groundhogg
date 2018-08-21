<?php
/**
 * View superlinks
 *
 * Allow the user to view & edit the superlinks
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

wp_enqueue_editor();
wp_enqueue_script('wplink');
wp_enqueue_style('editor-buttons');
wp_enqueue_script( 'link-picker', WPFN_ASSETS_FOLDER . '/js/admin/link-picker.js' );


if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {
    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'delete' ) ){
        wpfn_delete_superlink( intval( $_GET[ 'superlink_id' ] ) );
    }
    $sendback = remove_query_arg( array('action','_wpnonce'), wp_get_referer() );
    wp_redirect( add_query_arg( array( 'notice' => 'deleted', 'superlinks' => intval( $_GET[ 'superlink_id' ] ) ), $sendback ) );
    die();
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {

    include dirname( __FILE__ ) . '/edit-superlink.php';

} else if (  isset( $_POST[ 'add_superlink' ] ) ) {

    if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ] ) )
        wp_die( wpfn_get_random_groundhogday_quote() );

    $sendback = remove_query_arg( array('notice','_wpnonce'), wp_get_referer() );
    $superlink_name = sanitize_text_field( wp_unslash( $_POST['superlink_name'] ) );
    $superlink_target = sanitize_text_field( wp_unslash( $_POST['superlink_target'] ) );
    $superlink_tags = $_POST['superlink_tags'];
    $superlink_id = wpfn_insert_new_superlink( $superlink_name, $superlink_target, $superlink_tags );
    wp_redirect( add_query_arg( array( 'notice' => 'added', 'superlinks' => $superlink_id ), $sendback ) );
    die();

} else {
    if ( ! class_exists( 'WPFN_Emails_Table' ) ){
        include dirname( __FILE__ ) . '/class-superlinks-table.php';
    }
    $superlinks_table = new WPFN_Superlinks_Table();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo __('Contact superlinks', 'groundhogg');?></h1>
        <?php $notice = isset( $_GET[ 'notice' ] )? $_GET[ 'notice' ] : '';
        $item_count = isset( $_GET['superlinks'] )? count( explode( ',', urldecode( $_GET['superlinks'] ) ) ) : 0;
        switch ( $notice ):
            case 'deleted':
                ?><div class="notice notice-success is-dismissible"><p><strong><?php echo $item_count . ' ' . __( 'Superlinks deleted permanently.', 'groundhogg' );?></strong></p></div><?php
                break;
            case 'added':
                ?><div class="notice notice-success is-dismissible">
                <p><strong><?php _e( 'Created ' . $item_count . ' Superlinks.' ); ?></strong></p>
                </div><?php
                break;
            default:
        endswitch; ?>
        <hr class="wp-header-end">
        <form method="get" class="search-form wp-clearfix">
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search superlinks ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( __( 'Search Superlinks' ) )?>">
            </p>
        </form>
        <div id="col-container" class="wp-clearfix">
            <div id="col-left">
                <div class="col-wrap">
                    <div class="form-wrap">
                        <h2><?php _e( 'Add New Superlink', 'groundhogg' ) ?></h2>
                        <form id="addsuperlink" method="post" action="">
                            <?php wp_nonce_field(); ?>
                            <div class="form-field term-name-wrap">
                                <label for="superlink-name"><?php _e( 'Superlink Name', 'groundhogg' ) ?></label>
                                <input name="superlink_name" id="superlink-name" type="text" value="" maxlength="100" autocomplete="off" required>
                                <p><?php _e( 'Name a Superlink something simple so you do not forget it.', 'groundhogg' ); ?></p>
                            </div>
                            <div class="form-field term-target-wrap">
                                <label for="superlink-target"><?php _e( 'Target URL', 'groundhogg' ) ?></label>
                                <input name="superlink_target" id="superlink-target" type="url" value="" maxlength="100" autocomplete="off" required>
                                <p><a href="#" id="insert-link" data-target="superlink-target"><?php _e( 'Insert Link' ); ?></a> | <?php _e( 'Insert a url that this link will direct to. This link can contain simple replacement codes.', 'groundhogg' ); ?></p>
                                <script>
                                    jQuery( function($){
                                        $( '#insert-link' ).linkPicker();
                                    });
                                </script>
                            </div>
                            <div class="form-field term-tag-wrap">
                                <label for="superlink-description"><?php _e( 'Apply Tags When Clicked', 'groundhogg' ) ?></label>
                                <?php $tag_dropdown_id = 'tags';
                                $tag_dropdown_name = 'superlink_tags[]';
                                $tag_args = array();
                                $tag_args[ 'id' ] = $tag_dropdown_id;
                                $tag_args[ 'name' ] = $tag_dropdown_name;
                                $tag_args[ 'width' ] = '100%';
                                $tag_args[ 'class' ] = 'hidden'; ?>
                                <?php wpfn_dropdown_tags( $tag_args ); ?>
                                <p><?php _e( 'These tags will be applied to a contact whenever this link is clicked.', 'groundhogg' ); ?></p>
                            </div>
                            <?php submit_button( __( 'Add New Superlink', 'groundhogg' ), 'primary', 'add_superlink' ); ?>
                        </form>
                    </div>
                </div>
            </div>
            <div id="col-right">
                <div class="col-wrap">
                    <form id="posts-filter" method="post">
                        <?php wp_nonce_field(); ?>
                        <?php $superlinks_table->prepare_items(); ?>
                        <?php $superlinks_table->display(); ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php

}