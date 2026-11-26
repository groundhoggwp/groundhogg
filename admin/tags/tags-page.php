<?php

namespace Groundhogg\Admin\Tags;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Plugin;
use Groundhogg\Tag;
use WP_Error;
use function Groundhogg\action_input;
use function Groundhogg\admin_page_url;
use function Groundhogg\db;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\verify_admin_ajax_nonce;

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

	protected function add_ajax_actions() {
		add_action( 'wp_ajax_groundhogg_tag_editor', [ $this, 'ajax_tag_editor' ] );
		add_action( 'wp_ajax_groundhogg_save_tag', [ $this, 'ajax_save_tag' ] );
	}

	public function help() {
	}

	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );
		wp_enqueue_style( 'groundhogg-admin-sidebar-editor' );
		wp_enqueue_script( 'groundhogg-admin-tags' );
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
				'action' => esc_html__( 'Add New', 'groundhogg' ),
				'target' => '_self',
			]
		];
	}

	public function view() {

		$tags_table = new Tags_Table();

		?>
        <div class="display-flex" style="gap: 40px;">
            <div class="left col-wrap">
                <h2><?php esc_html_e( 'Add a new tag', 'groundhogg' ); ?></h2>
                <form class="display-flex column gap-10 form-wrap" method="post">
					<?php

					action_input( 'add', true, true );

					html( 'div', [
						'class' => 'display-flex column'
					], [
						html()->e( 'label', [ 'for' => 'tag-name' ], esc_html__( 'Name' , 'groundhogg' ) ),
						html()->input( [
							'id'   => 'tag-name',
							'name' => 'tag_name'
						] ),
						html()->description( esc_html__( 'Name a tag something simple so you do not forget it.', 'groundhogg' ) )
					] );

					html('div', [
						'class' => 'display-flex column'
					], [
						html()->e( 'label', [ 'for' => 'tag-name' ], esc_html__( 'Description' , 'groundhogg' ) ),
						html()->textarea( [
							'id'   => 'tag-description',
							'name' => 'tag_description',
							'rows' => 3
						] ),
						html()->description( esc_html__( 'What the tag signifies when applied to a contact.', 'groundhogg' ) )
					] );

					do_action( 'groundhogg/admin/tags/add/form' );

					html('div', [], html()->button( [
						'type'  => 'submit',
						'class' => 'gh-button primary',
						'text'  => esc_html__( 'Add Tag', 'groundhogg' )
					] ) );

					?>

                </form>
                <div class="spacer" style="height: 40px"></div>
                <h2><?php esc_html_e( 'Add multiple tags', 'groundhogg' ); ?></h2>
                <form class="display-flex column gap-10 form-wrap" method="post">
					<?php

					action_input( 'add', true, true );

					html( 'div', [
						'class' => 'display-flex column'
					], [
						html()->e( 'label', [ 'for' => 'bulk-tags' ], esc_html__( 'Tag names', 'groundhogg' ) ),
						html()->textarea( [
							'id'   => 'bulk-tags',
							'name' => 'bulk_tags',
							'rows' => 5
						] ),
						html()->description( esc_html__( 'Enter 1 tag name per line.', 'groundhogg' ) )
					] );

					html( 'div', [], html()->button( [
						'type'  => 'submit',
						'class' => 'gh-button primary',
						'text'  => esc_html__( 'Add Tags', 'groundhogg' )
					] ) );

					?>
                </form>
            </div>
            <div>
				<?php
				$this->search_form( esc_html__( 'Search Tags', 'groundhogg' ) );
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
	 * @return WP_Error|true|false
	 */
	public function process_add() {

		if ( ! current_user_can( 'add_tags' ) ) {
			$this->wp_die_no_access();
		}

		if ( get_post_var( 'bulk_tags' ) ) {

			$tag_names = array_filter( map_deep( explode( PHP_EOL, sanitize_textarea_field( get_post_var( 'bulk_tags' ) ) ), 'trim' ) );

			$ids = [];

			foreach ( $tag_names as $name ) {
				$id = db()->tags->add( [ 'tag_name' => $name ] );

				if ( $id ) {
					$ids[] = $id;

					do_action( 'groundhogg/admin/tags/add', $id );
				}
			}

			if ( empty( $ids ) ) {
				return new WP_Error( 'unable_to_add_tags', "Something went wrong adding the tags." );
			}

            /* translators: %d: the number of tags created */
			$this->add_notice( 'new-tags', sprintf( _nx( '%d tag created', '%d tags created', count( $tag_names ), 'notice', 'groundhogg' ), count( $tag_names ) ) );

		} else {

			$tag_name = trim( sanitize_text_field( get_post_var( 'tag_name' ) ) );

			if ( $tag_name && strlen( $tag_name ) > db()->tags->get_max_index_length() ) {
				/* translators: %d: the maximum allowed length of a tag */
				return new WP_Error( 'too_long', sprintf( esc_html__(  "Maximum length for tag name is %d characters.", 'groundhogg' ), db()->tags->get_max_index_length() ) );
			}

			$tag_desc = sanitize_textarea_field( get_post_var( 'tag_description' ) );

			$id = db()->tags->add( [
				'tag_name'        => $tag_name,
				'tag_description' => $tag_desc
			] );

			if ( ! $id ) {
				return new WP_Error( 'unable_to_add_tag', "Something went wrong adding the tag." );
			}

			do_action( 'groundhogg/admin/tags/add', $id );

			$this->add_notice( 'new-tag', _x( 'Tag created!', 'notice', 'groundhogg' ) );

		}

		return false;
	}

	/**
	 * @return bool|WP_Error
	 */
	public function process_edit() {

		if ( ! current_user_can( 'edit_tags' ) ) {
			$this->wp_die_no_access();
		}

		$updated = $this->update_tag( new Tag( absint( get_request_var( 'tag' ) ) ) );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$this->add_notice( 'updated', _x( 'Tag updated.', 'notice', 'groundhogg' ) );

		// Return false to return to main page.
		return true;
	}

	/**
	 * Update a tag from the posted name and description, for the edit screen and the editor beside the table
	 *
	 * Add-ons save their own fields from the edit form on groundhogg/admin/tags/edit
	 *
	 * @param Tag $tag
	 *
	 * @return true|WP_Error
	 */
	protected function update_tag( Tag $tag ) {

		if ( ! $tag->exists() ) {
			return new WP_Error( 'not_found', esc_html__( 'That tag no longer exists.', 'groundhogg' ) );
		}

		$tag_name        = trim( sanitize_text_field( get_post_var( 'name' ) ) );
		$tag_description = sanitize_textarea_field( get_post_var( 'description' ) );

		if ( empty( $tag_name ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Name can\'t be empty', 'groundhogg' ) );
		}

		if ( strlen( $tag_name ) > db()->tags->get_max_index_length() ) {
			/* translators: %d: the maximum allowed length of a tag */
			return new WP_Error( 'too_long', sprintf( esc_html__(  "Maximum length for tag name is %d characters.", 'groundhogg' ), db()->tags->get_max_index_length() ) );
		}

		$tag_slug = sanitize_title( $tag_name );

		// the slug is unique, so the update would fail
		if ( $tag_slug !== $tag->get_slug() && db()->tags->exists( $tag_slug, 'tag_slug' ) ) {
			return new WP_Error( 'in_use', esc_html__( 'Another tag already has that name.', 'groundhogg' ) );
		}

		db()->tags->update( $tag->get_id(), [
			'tag_name'        => $tag_name,
			'tag_slug'        => $tag_slug,
			'tag_description' => $tag_description,
		] );

		do_action( 'groundhogg/admin/tags/edit', $tag->get_id() );

		return true;
	}

	/**
	 * What the editor beside the table shows for a tag
	 *
	 * @param Tag $tag
	 *
	 * @return array
	 */
	protected function get_editor_item( Tag $tag ) {

		$count = $tag->get_contact_count();

		ob_start();

		/**
		 * Fields for the tag's edit form, as rows of a form table. They're saved on groundhogg/admin/tags/edit
		 *
		 * @param int $tag_id
		 */
		do_action( 'groundhogg/admin/tags/edit/form', $tag->get_id() );

		$fields = trim( ob_get_clean() );

		return [
			'fields'          => $fields ? sprintf( '<table class="form-table"><tbody>%s</tbody></table>', $fields ) : '',
			'ID'              => $tag->get_id(),
			'tag_name'        => $tag->get_name(),
			'tag_description' => $tag->get_description(),
			'contacts'        => $count,
			'contacts_text'   => sprintf(
			/* translators: %s: the number of contacts */
				_n( '%s contact has this tag.', '%s contacts have this tag.', $count, 'groundhogg' ),
				number_format_i18n( $count )
			),
			'contacts_url'    => admin_page_url( 'gh_contacts', [ 'tags_include' => $tag->get_id() ] ),
			'row'             => ( new Tags_Table() )->get_row_html( $tag ),
		];
	}

	/**
	 * Load a tag into the editor beside the table, with the fields add-ons put in the edit screen
	 *
	 * @return void
	 */
	public function ajax_tag_editor() {

		if ( ! verify_admin_ajax_nonce() || ! current_user_can( 'edit_tags' ) ) {
			$this->wp_die_no_access();
		}

		$tag = new Tag( absint( get_post_var( 'tag' ) ) );

		if ( ! $tag->exists() ) {
			wp_send_json_error( [ 'message' => esc_html__( 'That tag no longer exists.', 'groundhogg' ) ] );
		}

		wp_send_json_success( $this->get_editor_item( $tag ) );
	}

	/**
	 * Save a tag from the editor beside the table
	 *
	 * @return void
	 */
	public function ajax_save_tag() {

		if ( ! verify_admin_ajax_nonce() || ! current_user_can( 'edit_tags' ) ) {
			$this->wp_die_no_access();
		}

		$tag     = new Tag( absint( get_post_var( 'tag' ) ) );
		$updated = $this->update_tag( $tag );

		if ( is_wp_error( $updated ) ) {
			wp_send_json_error( [ 'message' => $updated->get_error_message() ] );
		}

		// what add-ons changed too
		$tag = new Tag( $tag->get_id() );

		wp_send_json_success( $this->get_editor_item( $tag ) );
	}

	/**
	 * Delete tags from the admin
	 *
	 * @return bool|WP_Error
	 */
	public function process_delete() {
		if ( ! current_user_can( 'delete_tags' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {

			$tag = new Tag( $id );

			if ( ! $tag->delete() ) {
				return new WP_Error( 'unable_to_delete_tag', "Something went wrong deleting the tag." );
			}
		}

		$this->add_notice(
			'deleted',
            /* translators: %d: the number of tags that were deleted */
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
