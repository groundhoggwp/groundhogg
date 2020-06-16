<?php

namespace Groundhogg\Admin\Tags;

use Groundhogg\Admin\Admin_Page;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use function Groundhogg\recount_tag_contacts_count;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View Tags
 *
 * @package     Admin
 * @subpackage  Admin/Tags
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Tags_Page extends Admin_Page {
	// UNUSED FUNCTIONS
	protected function add_ajax_actions() {
	}

	public function help() {
	}

	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );
	}

	protected function add_additional_actions() {
		if ( isset( $_GET['recount_contacts'] ) ) {
			add_action( 'init', array( $this, 'recount' ) );
		}
	}

	public function get_slug() {
		return 'gh_tags';
	}

	public function get_name() {
		return _x( 'Tags', 'page_title', 'groundhogg' );
	}

	public function get_cap() {
		return 'edit_tags';
	}

	public function get_item_type() {
		return 'tag';
	}

	public function get_priority() {
		return 10;
	}

	public function recount() {
		recount_tag_contacts_count();
	}

	/**
	 * @return string
	 */
	protected function get_title() {
		switch ( $this->get_current_action() ) {
			default:
			case 'add':
			case 'view':
				return $this->get_name();
				break;
			case 'edit':
				return _x( 'Edit Tag', 'page_title', 'groundhogg' );
				break;
		}
	}

	/**
	 * Add Tag Process
	 *
	 * @return \WP_Error|true|false
	 */
	public function process_add() {

		if ( ! current_user_can( 'add_tags' ) ) {
			$this->wp_die_no_access();
		}

		if ( isset( $_POST['bulk_add'] ) ) {

			$tag_names = array_filter( map_deep( explode( PHP_EOL, sanitize_textarea_field( get_post_var( 'bulk_tags' ) ) ), 'trim' ) );

			$ids = [];

			foreach ( $tag_names as $name ) {
				if ( $name && strlen( $name ) < 50 ) {
					$id = get_db( 'tags' )->add( [ 'tag_name' => $name ] );

					if ( $id ) {
						$ids[] = $id;

						do_action( 'groundhogg/admin/tags/add', $id );
					}
				}

			}

			if ( empty( $ids ) ) {
				return new \WP_Error( 'unable_to_add_tags', "Something went wrong adding the tags." );
			}

			$this->add_notice( 'new-tags', sprintf( _nx( '%d tag created', '%d tags created', count( $tag_names ), 'notice', 'groundhogg' ), count( $tag_names ) ) );

			return true;

		} else {

			$tag_name = trim( sanitize_text_field( get_post_var( 'tag_name' ) ) );
			if ( $tag_name && strlen( $tag_name ) > 50 ) {
				return new \WP_Error( 'too_long', __( "Maximum length for tag name is 50 characters.", 'groundhogg' ) );
			}

			$tag_desc = sanitize_textarea_field( get_post_var( 'tag_description' ) );
			$id       = Plugin::$instance->dbs->get_db( 'tags' )->add( [
				'tag_name'        => $tag_name,
				'tag_description' => $tag_desc
			] );

			if ( ! $id ) {
				return new \WP_Error( 'unable_to_add_tag', "Something went wrong adding the tag." );
			}

			do_action( 'groundhogg/admin/tags/add', $id );

			$this->add_notice( 'new-tag', _x( 'Tag created!', 'notice', 'groundhogg' ) );

		}

		return true;
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function process_edit() {

		if ( ! current_user_can( 'edit_tags' ) ) {
			$this->wp_die_no_access();
		}

		$id = absint( get_request_var( 'tag' ) );

		$tag_name        = sanitize_text_field( get_post_var( 'name' ) );
		$tag_description = sanitize_textarea_field( get_post_var( 'description' ) );
		if ( strlen( $tag_name ) > 50 ) {
			return new \WP_Error( 'too_long', __( "Maximum length for tag name is 50 characters.", 'groundhogg' ) );
		}
		$args = array(
			'tag_name'        => $tag_name,
			'tag_slug'        => sanitize_title( $tag_name ),
			'tag_description' => $tag_description,
		);

		Plugin::$instance->dbs->get_db( 'tags' )->update( absint( $_GET['tag'] ), $args );

		$this->add_notice( 'updated', _x( 'Tag updated.', 'notice', 'groundhogg' ) );

		do_action( 'groundhogg/admin/tags/edit', $id );

		// Return false to return to main page.
		return false;

	}

	/**
	 * @return bool
	 */
	public function process_recount() {
		recount_tag_contacts_count();

		$this->add_notice( 'recount', __( 'Tag associations reset.', 'groundhogg' ) );

		return false;
	}

	/**
	 * Delete tags from the admin
	 *
	 * @return bool|\WP_Error
	 */
	public function process_delete() {
		if ( ! current_user_can( 'delete_tags' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {
			if ( ! Plugin::$instance->dbs->get_db( 'tags' )->delete( $id ) ) {
				return new \WP_Error( 'unable_to_delete_tag', "Something went wrong deleting the tag." );
			}
		}

		$this->add_notice(
			'deleted',
			sprintf( _nx( '%d tag deleted', '%d tags deleted', count( $this->get_items() ), 'notice', 'groundhogg' ),
				count( $this->get_items() )
			)
		);

		return false;
	}

	public function view() {
		if ( ! class_exists( 'Tags_Table' ) ) {
			include __DIR__ . '/tags-table.php';
		}

		$tags_table = new Tags_Table();

		$this->search_form( __( 'Search Tags', 'groundhogg' ) );

		?>
        <div id="col-container" class="wp-clearfix">
            <div id="col-left">
                <div class="col-wrap">
                    <div class="form-wrap">
                        <h2><?php _e( 'Add New Tag', 'groundhogg' ) ?></h2>
                        <form id="addtag" method="post" action="">
                            <input type="hidden" name="action" value="add">
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
                                <label for="tag-bulk-toggle"><input name="bulk_add" id="tag-bulk-toggle"
                                                                    type="checkbox"><?php _e( 'Add tags in bulk?', 'groundhogg' ) ?>
                                </label>
                            </div>
                            <script>
                                jQuery(function ($) {
                                    $("#tag-bulk-toggle").change(function () {
                                        if ($(this).is(":checked")) {
                                            $(".term-name-wrap").addClass("hidden");
                                            $(".term-description-wrap").addClass("hidden");
                                            $(".term-bulk-wrap").removeClass("hidden");
                                        } else {
                                            $(".term-name-wrap").removeClass("hidden");
                                            $(".term-description-wrap").removeClass("hidden");
                                            $(".term-bulk-wrap").addClass("hidden");
                                        }
                                    });
                                });
                            </script>

							<?php do_action( 'groundhogg/admin/tags/add/form' ); ?>

							<?php submit_button( _x( 'Add New Tag', 'action', 'groundhogg' ), 'primary', 'add_tag' ); ?>
                        </form>
                    </div>
                </div>
            </div>
            <div id="col-right">
                <div class="col-wrap">
                    <form id="posts-filter" method="post">
						<?php $tags_table->prepare_items(); ?>
						<?php $tags_table->display(); ?>
                    </form>
                </div>
            </div>
        </div>
		<?php
	}

	public function edit() {
		if ( ! current_user_can( 'edit_tags' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/edit.php';
	}
}