<?php

namespace Groundhogg\Admin\Superlinks;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Plugin;

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Superlinks Page
 *
 * This is the superlinks page, it also contains the add form since it's the same layout as the terms.php
 *
 * @package     Admin
 * @subpackage  Admin/Supperlinks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Superlinks_Page extends Admin_Page
{

    //UNUSED FUNCTIONS
    protected function add_ajax_actions(){}
    protected function add_additional_actions(){}
    public function scripts(){}

    public function get_slug()
    {
        return 'gh_superlinks';
    }

    public function get_name()
    {
        return _x('Superlinks', 'page_title', 'groundhogg');
    }

    public function get_cap()
    {
        return 'edit_superlinks';
    }

    public function get_item_type()
    {
        return 'superlink';
    }

    public function get_priority()
    {
        return 15;
    }

    /* Register the help bar */
    public function help()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'gh_overview',
                'title' => __('Overview'),
                'content' => '<p>' . __("Superlinks are special superlinks that allow you to apply/remove tags whenever clicked and then take the contact to a page of your choice. To use them, just copy the replacement code and paste in in email, button, or link.", 'groundhogg') . '</p>'
            )
        );
    }

    /**
     * @return string
     */
    protected function get_title()
    {
        switch ($this->get_current_action()) {
            default:
            case 'add':
            case 'view':
                return $this->get_name();
                break;
            case 'edit':
                return _x('Edit Superlink', 'page_title', 'groundhogg');
                break;
        }
    }


    /**
     * Add new superlink
     *
     * @return bool|\WP_Error
     */
    public function process_add()
    {

        if (!current_user_can('add_superlinks')) {
            $this->wp_die_no_access();
        }

        $superlink_name = sanitize_text_field(wp_unslash($_POST['superlink_name']));
        $superlink_target = sanitize_text_field(wp_unslash($_POST['superlink_target']));

        $superlink_tags = isset($_POST['superlink_tags']) ? Plugin::$instance->dbs->get_db('tags' )->validate( wp_unslash( $_POST['superlink_tags'] ) ) : [];

        $args = array(
            'name' => $superlink_name,
            'target' => $superlink_target,
            'tags' => $superlink_tags
        );

        $superlink_id = Plugin::$instance->dbs->get_db('superlinks')->add($args);

        if (!$superlink_id) {
            return new \WP_Error('unable_to_add_superlink', "Something went wrong adding the superlink.");
        }

        $this->add_notice('new-superlink', _x('Superlink created!', 'notice', 'groundhogg'));
        return true;
    }

    /**
     * Edit superlink from the admin
     *
     * @return bool|\WP_Error
     */
    public function process_edit()
    {
        if (!current_user_can('edit_superlinks')) {
            $this->wp_die_no_access();
        }

        $id = absint($_GET['superlink']);
        $superlink_name = sanitize_text_field(wp_unslash($_POST['superlink_name']));
        $superlink_target = sanitize_text_field(wp_unslash($_POST['superlink_target']));
        $superlink_tags =Plugin::$instance->dbs->get_db('tags' )->validate( wp_unslash( $_POST['superlink_tags'] ) );

        $args = array(
            'name' => $superlink_name,
            'target' => $superlink_target,
            'tags' => $superlink_tags
        );

        $result = Plugin::$instance->dbs->get_db('superlinks')->update($id, $args);

        if (!$result) {
            return new \WP_Error('unable_to_update_superlink', "Something went wrong while updating the Superlink..." );
        }

        $this->add_notice('updated', _x('Superlink updated!', 'notice', 'groundhogg'));

        // Return false to return to main page.
        return false;

    }

    /**
     * Delete superlink from the admin
     *
     * @return bool|\WP_Error
     */
    public function process_delete()
    {

        if (!current_user_can('delete_superlinks')) {
            $this->wp_die_no_access();
        }

        foreach( $this->get_items() as $id) {

            if (!Plugin::$instance->dbs->get_db('superlinks')->delete( $id ) ) {
                return new \WP_Error('unable_to_delete_superlink', "Something went wrong while deleting the superlink.");
            }
        }

        $this->add_notice(
            'deleted',
            sprintf(_nx('%d superlink deleted', '%d superlinks deleted', count($this->get_items()), 'notice', 'groundhogg'),
                count($this->get_items())
            )
        );
        return true;
    }

    public function view()
    {
        if (!class_exists('Superlinks_Table')) {
            include dirname(__FILE__) . '/superlinks-table.php';
        }

        $superlinks_table = new Superlinks_Table(); ?>
        <form method="post" class="search-form wp-clearfix">
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text"
                       for="post-search-input"><?php _e('Search Superlinks', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button"
                       value="<?php esc_attr_e('Search Superlinks', 'groundhogg') ?>">
            </p>
        </form>
        <div id="col-container" class="wp-clearfix">
            <div id="col-left">
                <div class="col-wrap">
                    <div class="form-wrap">
                        <h2><?php _e('Add New Superlink', 'groundhogg') ?></h2>
                        <form id="addsuperlink" method="post" action="">
                            <input type="hidden" name="action" value="add">
                            <?php wp_nonce_field(); ?>
                            <div class="form-field term-name-wrap">
                                <label for="superlink-name"><?php _e('Superlink Name', 'groundhogg') ?></label>
                                <input name="superlink_name" id="superlink-name" type="text" value="" maxlength="100"
                                       autocomplete="off" required>
                                <p><?php _e('Name a Superlink something simple so you do not forget it.', 'groundhogg'); ?></p>
                            </div>
                            <div class="form-field term-target-wrap">
                                <label for="superlink-target"><?php _e('Target URL', 'groundhogg') ?></label>
                                <?php
                                $args = array(
                                    'type' => 'url',
                                    'id' => 'superlink_target',
                                    'name' => 'superlink_target',
                                    'title' => __('Superlink target'),
                                );

                                echo Plugin::$instance->utils->html->link_picker($args); ?>
                                <p><?php _e('Insert a url that this link will direct to. This link can contain simple replacement codes.', 'groundhogg'); ?></p>
                            </div>
                            <div class="form-field term-tag-wrap">
                                <label for="superlink-description"><?php _e('Apply Tags When Clicked', 'groundhogg') ?></label>
                                <?php $tag_args = array();
                                $tag_args['id'] = 'superlink_tags';
                                $tag_args['name'] = 'superlink_tags[]';
                                //                                $tag_args[ 'width' ] = '100%';
                                ?>
                                <?php echo Plugin::$instance->utils->html->tag_picker($tag_args); ?>
                                <p><?php _e('These tags will be applied to a contact whenever this link is clicked. To create a new tag hit [Enter] or [,]', 'groundhogg'); ?></p>
                            </div>
                            <?php submit_button(_x('Add New Superlink', 'action', 'groundhogg'), 'primary', 'add_superlink'); ?>
                        </form>
                    </div>
                </div>
            </div>
            <div id="col-right">
                <div class="col-wrap">
                    <form id="posts-filter" method="post">
                        <?php $superlinks_table->prepare_items(); ?>
                        <?php $superlinks_table->display(); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    function edit()
    {
        if (!current_user_can('edit_superlinks')) {
            $this->wp_die_no_access();
        }
        include dirname(__FILE__) . '/edit.php';
    }
}