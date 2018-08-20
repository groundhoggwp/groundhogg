<?php
/**
 * View Tags
 *
 * Allow the user to view & edit the tags
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {
    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'delete' ) ){
        wpfn_delete_tag( intval( $_GET[ 'tag_id' ] ) );
    }
    $sendback = remove_query_arg( array('action','_wpnonce'), wp_get_referer() );
    wp_redirect( add_query_arg( array( 'notice' => 'deleted', 'tags' => intval( $_GET[ 'tag_id' ] ) ), $sendback ) );
    die();
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {

    include dirname( __FILE__ ) . '/edit-tag.php';

} else if (  isset( $_POST[ 'add_tag' ] ) ) {

    if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ] ) )
        wp_die( wpfn_get_random_groundhogday_quote() );

    $sendback = remove_query_arg( array('notice','_wpnonce'), wp_get_referer() );

    if ( isset( $_POST['bulk_add'] ) ){

        $tag_names = explode( PHP_EOL, trim( sanitize_textarea_field( wp_unslash( $_POST['bulk_tags'] ) ) ) );

        foreach ($tag_names as $name)
        {
            $tagid = wpfn_insert_tag( $name );
            $tags[] = $tagid;
        }
        wp_redirect( add_query_arg( array( 'notice' => 'added', 'tags' => urlencode( implode( ',', $tags ) ) ), $sendback ) );
    } else {
        $tagname = sanitize_text_field( wp_unslash( $_POST['tag_name'] ) );
        $tagdesc = sanitize_text_field( wp_unslash( $_POST['tag_description'] ) );
        $tagid = wpfn_insert_tag( $tagname, $tagdesc );
        wp_redirect( add_query_arg( array( 'notice' => 'added', 'tags' => $tagid ), $sendback ) );
    }

    die();
} else {
    if ( ! class_exists( 'WPFN_Emails_Table' ) ){
        include dirname( __FILE__ ) . '/class-tags-table.php';
    }
    $tags_table = new WPFN_Contact_Tags_Table();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo __('Contact Tags', 'groundhogg');?></h1>
        <?php $notice = isset( $_GET[ 'notice' ] )? $_GET[ 'notice' ] : '';
        $item_count = isset( $_GET['tags'] )? count( explode( ',', urldecode( $_GET['tags'] ) ) ) : 0;
        switch ( $notice ):
            case 'deleted':
                ?><div class="notice notice-success is-dismissible"><p><strong><?php echo $item_count . ' ' . __( 'tags deleted permanently.', 'groundhogg' );?></strong></p></div><?php
                break;
            case 'added':
                ?><div class="notice notice-success is-dismissible">
                <p><strong><?php _e( 'Created ' . $item_count . ' Tags.' ); ?></strong></p>
                </div><?php
                break;
            default:
        endswitch; ?>
        <hr class="wp-header-end">
        <form method="get" class="search-form wp-clearfix">
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Tags ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( __( 'Search Tags' ) )?>">
            </p>
        </form>
        <div id="col-container" class="wp-clearfix">
            <div id="col-left">
                <div class="col-wrap">
                    <div class="form-wrap">
                        <h2><?php _e( 'Add New Tag', 'groundhogg' ) ?></h2>
                        <form id="addtag" method="post" action="">
                            <?php wp_nonce_field(); ?>
                            <div class="form-field term-name-wrap">
                                <label for="tag-name"><?php _e( 'Tag Name', 'groundhogg' ) ?></label>
                                <input name="tag_name" id="tag-name" type="text" value="" size="40">
                                <p><?php _e( 'Name a tag something simple so you do not forget it.', 'groundhogg' ); ?></p>
                            </div>
                            <div class="form-field term-description-wrap">
                                <label for="tag-description"><?php _e( 'Description', 'groundhogg' ) ?></label>
                                <textarea name="tag_description" id="tag-description" rows="5" cols="40"></textarea>
                                <p><?php _e( 'Tag descriptions are only visible to admins and will never be seen by contacts.', 'groundhogg' ); ?></p>
                            </div>
                            <div class="form-field term-bulk-wrap hidden">
                                <label for="tag-bulk"><?php _e( 'Bulk Add Tags', 'groundhogg' ) ?></label>
                                <textarea name="bulk_tags" id="tag-bulk" rows="5" cols="40" maxlength="1000"></textarea>
                                <p><?php _e( 'Enter 1 tag per line.', 'groundhogg' ); ?></p>
                            </div>
                            <div class="form-field term-toggle-bulk-wrap">
                                <label for="tag-bulk-toggle"><input name="bulk_add" id="tag-bulk-toggle" type="checkbox"><?php _e( 'Add tags in bulk?', 'groundhogg' ) ?></label>
                            </div>
                            <script>
                                jQuery(function($){
                                    $( '#tag-bulk-toggle' ).change(function(){
                                        if ( $(this).is( ':checked' ) ){
                                            $( '.term-name-wrap' ).addClass( 'hidden' );
                                            $( '.term-description-wrap' ).addClass( 'hidden' );
                                            $( '.term-bulk-wrap' ).removeClass( 'hidden' );
                                        } else {
                                            $( '.term-name-wrap' ).removeClass( 'hidden' );
                                            $( '.term-description-wrap' ).removeClass( 'hidden' );
                                            $( '.term-bulk-wrap' ).addClass( 'hidden' );
                                        }
                                    });
                                });
                            </script>
                            <?php submit_button( __( 'Add New Tag', 'groundhogg' ), 'primary', 'add_tag' ); ?>
                        </form>
                    </div>
                </div>
            </div>
            <div id="col-right">
                <div class="col-wrap">
                    <form id="posts-filter" method="post">
                        <?php wp_nonce_field(); ?>
                        <?php $tags_table->prepare_items(); ?>
                        <?php $tags_table->display(); ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php

}