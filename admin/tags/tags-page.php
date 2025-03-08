<?php

namespace Groundhogg\Admin\Tags;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Plugin;
use Groundhogg\Tag;
use function Groundhogg\action_input;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\html;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View Tags
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Tags
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
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
		return 15;
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

    protected function get_title_actions() {

        if ( $this->get_current_action() === 'view' ) {
            return [];
        }

	    return [
		    [
			    'link'   => $this->admin_url( [ 'action' => 'view' ] ),
			    'action' => __( 'Add New', 'groundhogg' ),
			    'target' => '_self',
		    ]
	    ];
    }

	public function view() {

		$tags_table = new Tags_Table();

		?>
        <div class="display-flex" style="gap: 40px;">
            <div class="left col-wrap">
                <h2><?php _e( 'Add a new tag', 'groundhogg' ); ?></h2>
                <form class="display-flex column gap-10 form-wrap" method="post">
					<?php

					action_input( 'add', true, true );

					echo html()->e( 'div', [
						'class' => 'display-flex column'
					], [
						html()->e( 'label', [ 'for' => 'tag-name' ], __( 'Name' ) ),
						html()->input( [
							'id'   => 'tag-name',
							'name' => 'tag_name'
						] ),
						html()->description( __( 'Name a tag something simple so you do not forget it.', 'groundhogg' ) )
					] );

					echo html()->e( 'div', [
						'class' => 'display-flex column'
					], [
						html()->e( 'label', [ 'for' => 'tag-name' ], __( 'Description' ) ),
						html()->textarea( [
							'id'   => 'tag-description',
							'name' => 'tag_description',
							'rows' => 3
						] ),
						html()->description( __( 'What the tag signifies when applied to a contact.', 'groundhogg' ) )
					] );

					do_action( 'groundhogg/admin/tags/add/form' );

					echo html()->e( 'div', [], html()->button( [
						'type'  => 'submit',
						'class' => 'gh-button primary',
						'text'  => __( 'Add Tag', 'groundhogg' )
					] ) );

					?>

                </form>
                <div class="spacer" style="height: 40px"></div>
                <h2><?php _e( 'Add multiple tags', 'groundhogg' ); ?></h2>
                <form class="display-flex column gap-10 form-wrap" method="post">
					<?php

					action_input( 'add', true, true );

					echo html()->e( 'div', [
						'class' => 'display-flex column'
					], [
						html()->e( 'label', [ 'for' => 'bulk-tags' ], __( 'Tag names', 'groundhogg' ) ),
						html()->textarea( [
							'id'   => 'bulk-tags',
							'name' => 'bulk_tags',
							'rows' => 5
						] ),
						html()->description( __( 'Enter 1 tag name per line.', 'groundhogg' ) )
					] );

					echo html()->e( 'div', [], html()->button( [
						'type'  => 'submit',
						'class' => 'gh-button primary',
						'text'  => __( 'Add Tags', 'groundhogg' )
					] ) );

					?>

                </form>

            </div>
            <div>
				<?php
				$this->search_form( __( 'Search Tags', 'groundhogg' ) );
				?>

                <form id="posts-filter" method="post">
					<?php
					$tags_table->prepare_items();
					$tags_table->display();
					?>
                </form>
            </div>
        </div>
		<?php
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

		if ( get_post_var( 'bulk_tags' ) ) {

			$tag_names = array_filter( map_deep( explode( PHP_EOL, sanitize_textarea_field( get_post_var( 'bulk_tags' ) ) ), 'trim' ) );

			$ids = [];

			foreach ( $tag_names as $name ) {
				$id = get_db( 'tags' )->add( [ 'tag_name' => $name ] );

				if ( $id ) {
					$ids[] = $id;

					do_action( 'groundhogg/admin/tags/add', $id );
				}
			}

			if ( empty( $ids ) ) {
				return new \WP_Error( 'unable_to_add_tags', "Something went wrong adding the tags." );
			}

			$this->add_notice( 'new-tags', sprintf( _nx( '%d tag created', '%d tags created', count( $tag_names ), 'notice', 'groundhogg' ), count( $tag_names ) ) );

		} else {

			$tag_name = trim( sanitize_text_field( get_post_var( 'tag_name' ) ) );

			if ( $tag_name && strlen( $tag_name ) > get_db( 'tags' )->get_max_index_length() ) {
				return new \WP_Error( 'too_long', __( sprintf( "Maximum length for tag name is %d characters.", get_db( 'tags' )->get_max_index_length() ), 'groundhogg' ) );
			}

			$tag_desc = sanitize_textarea_field( get_post_var( 'tag_description' ) );

			$id = get_db( 'tags' )->add( [
				'tag_name'        => $tag_name,
				'tag_description' => $tag_desc
			] );

			if ( ! $id ) {
				return new \WP_Error( 'unable_to_add_tag', "Something went wrong adding the tag." );
			}

			do_action( 'groundhogg/admin/tags/add', $id );

			$this->add_notice( 'new-tag', _x( 'Tag created!', 'notice', 'groundhogg' ) );

		}

		return false;
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
		if ( strlen( $tag_name ) > get_db( 'tags' )->get_max_index_length() ) {
			return new \WP_Error( 'too_long', __( sprintf( "Maximum length for tag name is %d characters.", get_db( 'tags' )->get_max_index_length() ), 'groundhogg' ) );
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
		return true;
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

			$tag = new Tag( $id );

			if ( ! $tag->delete() ) {
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

	public function edit() {
		if ( ! current_user_can( 'edit_tags' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/edit.php';
	}

	protected function add_additional_actions() {
		// TODO: Implement add_additional_actions() method.
	}
}
