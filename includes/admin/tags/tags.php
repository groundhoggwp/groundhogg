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

function wpfn_render_tags_table()
{

    if ( ! class_exists( 'WPFN_Emails_Table' ) ){
        include dirname( __FILE__ ) . '/class-tags-table.php';
    }

    $tags_table = new WPFN_Contact_Tags_Table();

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo __('Contact Tags', 'groundhogg');?></h1>
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

if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' ) {

    if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], 'delete' ) ){

        wpfn_delete_tag( intval( $_GET[ 'tag_id' ] ) );

        ?><div class="notice notice-success is-dismissible"><p><?php _e( 'Deleted tag', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_tags_table();

    } else {

        ?><div class="notice notice-error is-dismissible"><p><?php _e( 'Could not delete tag', 'groundhogg' ); ?>.</p></div><?php

        wpfn_render_tags_table();
    }
} else if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {

    include dirname( __FILE__ ) . '/edit-tag.php';

} else {

    if ( isset( $_POST[ 'add_tag' ] ) ){

        if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ] ) )
            wp_die( wpfn_get_random_groundhogday_quote() );

        $tagname = sanitize_text_field( wp_unslash( $_POST['tag_name'] ) );
        $tagdesc = sanitize_text_field( wp_unslash( $_POST['tag_description'] ) );

        $tagid = wpfn_insert_tag( $tagname, $tagdesc );

        if ( $tagid ){
            ?><div class="notice notice-success is-dismissible">
            <p><strong><?php _e( 'Created Tag' ); ?>.</strong></p>
            <p><a href="<?php echo admin_url( 'admin.php?page=tags&action=edit&tag_id=' . $tagid )?>"><?php _e('Edit Tag');?> &rarr;</a></p>
            </div><?php
        } else {
            ?><div class="notice notice-error">
            <p><strong><?php _e( 'Could Not Create Tag' ); ?>.</strong></p>
            </div><?php
        }
    }

    wpfn_render_tags_table();

}