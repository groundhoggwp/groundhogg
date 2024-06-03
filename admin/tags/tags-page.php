<?php

namespace Groundhogg\Admin\Tags;

use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Campaign;
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
class Tags_Page extends Tabbed_Admin_Page {
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

		if ( ! current_user_can( 'manage_campaigns' ) ) {
			return _x( 'Tags', 'page_title', 'groundhogg' );
		}

		return _x( 'Tags & Campaigns', 'page_title', 'groundhogg' );
	}

	protected function get_tabs() {

		if ( current_user_can( 'edit_tags' ) ) {
			$tabs[] = [
				'name' => __( 'Tags' ),
				'slug' => 'tags'
			];
		}

		if ( current_user_can( 'manage_campaigns' ) ) {
			$tabs[] = [
				'name' => __( 'Campaigns' ),
				'slug' => 'campaigns'
			];
		}

		return $tabs;
	}

	public function get_cap() {
		return 'edit_tags';
	}

	public function get_item_type() {
		return $this->get_current_tab() === 'campaigns' ? 'campaign' : 'tag';
	}

	public function get_priority() {
		return 10;
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
				return $this->get_current_tab() === 'tags' ? _x( 'Edit Tag', 'page_title', 'groundhogg' ) : _x( 'Edit Campaign', 'page_title', 'groundhogg' );
				break;
		}
	}

	public function view() {

		$tags_table = new Tags_Table();

		?>
        <p></p>
        <div class="display-flex" style="gap: 40px">
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

	public function campaigns_view() {

		$campaigns_table = new Campaigns_Table();

		?>
        <p></p>
        <div class="display-flex" style="gap: 40px">
            <div class="left col-wrap">
                <h2><?php _e( 'Add a new Campaign', 'groundhogg' ); ?></h2>
                <form method="post" class="display-flex column gap-10 form-wrap">
					<?php

					action_input( 'add', true, true );

					echo html()->e( 'div', [
						'class' => 'display-flex column'
					], [
						html()->e( 'label', [ 'for' => 'campaign-name' ], __( 'Name' ) ),
						html()->input( [
							'id'   => 'campaign-name',
							'name' => 'name'
						] ),
						html()->description( __( 'A recognizable name for the campaign.', 'groundhogg' ) )
					] );

					echo html()->e( 'div', [
						'class' => 'display-flex column'
					], [
						html()->e( 'label', [ 'for' => 'campaign-slug' ], __( 'Slug' ) ),
						html()->input( [
							'id'   => 'campaign-slug',
							'name' => 'slug'
						] ),
						html()->description( __( 'The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens' ) )
					] );

					?>
                    <script>
                      ( ($) => {
                        $('#campaign-name').on('input', e => {
                          $('#campaign-slug').val(e.target.value.toLowerCase().replace(/[^a-z0-9]/g, '-'))
                        })
                      } )(jQuery)
                    </script>
					<?php

					echo html()->e( 'div', [
						'class' => 'display-flex column'
					], [
						html()->e( 'label', [ 'for' => 'campaign-name' ], __( 'Description' ) ),
						html()->textarea( [
							'id'   => 'campaign-description',
							'name' => 'description',
							'rows' => 3
						] ),
						html()->description( __( 'A description of the assets that will be assigned to this campaign.', 'groundhogg' ) )
					] );

					echo html()->e( 'div', [
						'class' => 'space-between'
					], [
						html()->e( 'label', [
							'for' => 'is-public'
						], __( 'Make this campaign publicly available?', 'groundhogg' ), false ),
						html()->toggle( [
							'id'       => 'is-public',
							'name'     => 'public',
							'onLabel'  => __( 'Yes' ),
							'offLabel' => __( 'No' ),
						] )
					] );

					echo html()->e( 'div', [], html()->button( [
						'type'  => 'submit',
						'class' => 'gh-button primary',
						'text'  => __( 'Add Campaign', 'groundhogg' )
					] ) );

					?>

                </form>
            </div>
            <div>
				<?php
				$this->search_form( __( 'Search Campaigns', 'groundhogg' ) );
				?>

                <form id="posts-filter" method="post">
					<?php
					$campaigns_table->prepare_items();
					$campaigns_table->display();
					?>
                </form>
            </div>
        </div>
		<?php
	}

	public function process_campaigns_add() {
		if ( ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		$name        = sanitize_text_field( get_post_var( 'name' ) );
		$description = sanitize_textarea_field( get_post_var( 'description' ) );
		$slug        = sanitize_title( get_post_var( 'slug' ) );

		if ( empty( $name ) ) {
			return new \WP_Error( 'invalid', __( 'Name can\'t be empty', 'groundhogg' ) );
		}

		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
		}

		if ( get_db( 'campaigns' )->exists( [ 'slug' => $slug ] ) ) {
			return new \WP_Error( 'in_use', __( 'The given slug is already in use by another campaign.', 'groundhogg' ) );
		}

		$campaign = new Campaign( [
			'name'        => $name,
			'slug'        => $slug,
			'description' => $description,
			'visibility'  => get_post_var( 'public' ) ? 'public' : 'hidden'
		] );

		if ( ! $campaign->exists() ) {
			return new \WP_Error( 'oops', __( 'Something went wrong.' ) );
		}

		$this->add_notice( 'new-campaign', __( 'Campaign created!', 'groundhogg' ) );

		return false;
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function process_campaigns_edit() {

		if ( ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		$id = absint( get_request_var( 'campaign' ) );

		// The current campaign
		$campaign = new Campaign( $id );

		$name        = sanitize_text_field( get_post_var( 'name' ) );
		$description = sanitize_textarea_field( get_post_var( 'description' ) );
		$slug        = sanitize_title( get_post_var( 'slug' ) );

		if ( empty( $name ) ) {
			return new \WP_Error( 'invalid', __( 'Name can\'t be empty', 'groundhogg' ) );
		}

		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
		}

		// Slug changed
		if ( $slug !== $campaign->get_slug() ) {

			// Make sure it's not too long
			if ( strlen( $slug ) > get_db( 'campaigns' )->get_max_index_length() ) {
				return new \WP_Error( 'too_long', __( sprintf( "Maximum length for a campaign name is %d characters.", get_db( 'campaigns' )->get_max_index_length() ), 'groundhogg' ) );
			}

			// Check if the slug is in use
			if ( get_db( 'campaigns' )->exists( [ 'slug' => $slug ] ) ) {
				return new \WP_Error( 'in_use', __( 'The given slug is already in use by another campaign.', 'groundhogg' ) );
			}
		}

		$campaign->update( [
			'name'        => $name,
			'description' => $description,
			'slug'        => $slug,
			'visibility'  => get_post_var( 'public' ) ? 'public' : 'hidden'
		] );

		$this->add_notice( 'updated', _x( 'Campaign updated.', 'notice', 'groundhogg' ) );

		do_action( 'groundhogg/admin/tags/edit', $id );

		return true;

	}

	/**
	 * Delete campaigns from the admin
	 *
	 * @return bool|\WP_Error
	 */
	public function process_campaigns_delete() {
		if ( ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {

			$campaign = new Campaign( $id );

			if ( ! $campaign->delete() ) {
				return new \WP_Error( 'unable_to_delete', "Something went wrong deleting the campaign." );
			}
		}

		$this->add_notice(
			'deleted',
			sprintf( _nx( '%d campaign deleted', '%d campaigns deleted', count( $this->get_items() ), 'notice', 'groundhogg' ),
				count( $this->get_items() )
			)
		);

		return false;
	}

	public function edit_campaigns() {
		if ( ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/edit-campaign.php';
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
