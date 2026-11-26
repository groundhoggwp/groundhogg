<?php

namespace Groundhogg\Admin\Campaigns;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Campaign;
use WP_Error;
use function Groundhogg\action_input;
use function Groundhogg\get_db;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\managed_page_url;
use function Groundhogg\verify_admin_ajax_nonce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
class Campaigns_Page extends Admin_Page {

	public function get_slug() {
		return 'gh_campaigns';
	}

	public function get_name() {
		return 'Campaigns';
	}

	public function get_cap() {
		return 'manage_campaigns';
	}

	public function get_item_type() {
		return 'campaign';
	}

	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );
		wp_enqueue_style( 'groundhogg-admin-sidebar-editor' );
		wp_enqueue_script( 'groundhogg-admin-campaigns' );
	}

	public function help() {
		// TODO: Implement help() method.
	}

	public function get_priority() {
		return 70;
	}

	public function view() {

		$table = new Campaigns_Table();

		?>
        <p></p>
        <div class="display-flex" style="gap: 40px">
            <div class="left col-wrap">
                <h2><?php esc_html_e( 'Add a new Campaign', 'groundhogg' ); ?></h2>
                <form method="post" class="display-flex column gap-10 form-wrap">
					<?php

					action_input( 'add', true, true );

					html()->div( [
						'class' => 'display-flex column'
					], [
						html()->e( 'label', [ 'for' => 'campaign-name' ], esc_html_x( 'Name', 'campaign name', 'groundhogg' ) ),
						html()->input( [
							'id'   => 'campaign-name',
							'name' => 'name'
						] ),
						html()->description( esc_html__( 'A recognizable name for the campaign.', 'groundhogg' ) )
					], true );

					html()->div( [
						'class' => 'display-flex column'
					], [
						html()->e( 'label', [ 'for' => 'campaign-slug' ], esc_html_x( 'Slug', 'campaign slug', 'groundhogg' ) ),
						html()->input( [
							'id'   => 'campaign-slug',
							'name' => 'slug'
						] ),
						html()->description( esc_html__( 'The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'groundhogg' ) )
					],true );

					?>
                    <script>
                      ( ($) => {
                        $('#campaign-name').on('input', e => {
                          $('#campaign-slug').val(e.target.value.toLowerCase().replace(/[^a-z0-9]/g, '-'))
                        })
                      } )(jQuery)
                    </script>
					<?php

					html()->div( [
						'class' => 'display-flex column'
					], [
						html()->e( 'label', [ 'for' => 'campaign-name' ], esc_html_x( 'Description', 'campaign description', 'groundhogg' ) ),
						html()->textarea( [
							'id'   => 'campaign-description',
							'name' => 'description',
							'rows' => 3
						] ),
						html()->description( esc_html__( 'A description of the assets that will be assigned to this campaign.', 'groundhogg' ) )
					], true );

					html()->div( [
						'class' => 'space-between'
					], [
						html()->e( 'label', [
							'for' => 'is-public'
						], esc_html__( 'Make this campaign publicly available?', 'groundhogg' ), false ),
						html()->toggle( [
							'id'       => 'is-public',
							'name'     => 'public',
							'onLabel'  => __( 'Yes', 'groundhogg' ),
							'offLabel' => __( 'No', 'groundhogg' ),
						] )
					], true );

					html()->div( [], html()->button( [
						'type'  => 'submit',
						'class' => 'gh-button primary',
						'text'  => esc_html__( 'Add Campaign', 'groundhogg' )
					] ), true );

					?>

                </form>
            </div>
            <div>
				<?php
				$this->search_form( esc_html__( 'Search Campaigns', 'groundhogg' ) );
				?>

                <form id="posts-filter" method="post">
					<?php
					$table->prepare_items();
					$table->display();
					?>
                </form>
            </div>
        </div>
		<?php
	}

	public function process_add() {
		if ( ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		$name        = sanitize_text_field( get_post_var( 'name' ) );
		$description = sanitize_textarea_field( get_post_var( 'description' ) );
		$slug        = sanitize_title( get_post_var( 'slug' ) );

		if ( empty( $name ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Name can\'t be empty', 'groundhogg' ) );
		}

		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
		}

		if ( get_db( 'campaigns' )->exists( [ 'slug' => $slug ] ) ) {
			return new WP_Error( 'in_use', esc_html__( 'The given slug is already in use by another campaign.', 'groundhogg' ) );
		}

		$campaign = new Campaign( [
			'name'        => $name,
			'slug'        => $slug,
			'description' => $description,
			'visibility'  => get_post_var( 'public' ) ? 'public' : 'hidden'
		] );

		if ( ! $campaign->exists() ) {
			return new WP_Error( 'oops', esc_html__( 'Something went wrong.', 'groundhogg' ) );
		}

		$this->add_notice( 'new-campaign', esc_html__( 'Campaign created!', 'groundhogg' ) );

		return false;
	}

	/**
	 * @return bool|WP_Error
	 */
	public function process_edit() {

		if ( ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		$updated = $this->update_campaign( new Campaign( absint( get_request_var( 'campaign' ) ) ) );

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		$this->add_notice( 'updated', _x( 'Campaign updated.', 'notice', 'groundhogg' ) );

		return true;
	}

	/**
	 * Update a campaign from the posted fields, for the edit screen and the editor beside the table
	 *
	 * @param Campaign $campaign
	 *
	 * @return true|WP_Error
	 */
	protected function update_campaign( Campaign $campaign ) {

		if ( ! $campaign->exists() ) {
			return new WP_Error( 'not_found', esc_html__( 'That campaign no longer exists.', 'groundhogg' ) );
		}

		$name        = sanitize_text_field( get_post_var( 'name' ) );
		$description = sanitize_textarea_field( get_post_var( 'description' ) );
		$slug        = sanitize_title( get_post_var( 'slug' ) );

		if ( empty( $name ) ) {
			return new WP_Error( 'invalid', esc_html__( 'Name can\'t be empty', 'groundhogg' ) );
		}

		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
		}

		// Slug changed
		if ( $slug !== $campaign->get_slug() ) {

			// Make sure it's not too long
			if ( strlen( $slug ) > get_db( 'campaigns' )->get_max_index_length() ) {
                /* translators: %d: the maximum index length */
				return new WP_Error( 'too_long', sprintf( esc_html__( "Maximum length for a campaign name is %d characters.", 'groundhogg' ), get_db( 'campaigns' )->get_max_index_length() ) );
			}

			// Check if the slug is in use
			if ( get_db( 'campaigns' )->exists( [ 'slug' => $slug ] ) ) {
				return new WP_Error( 'in_use', esc_html__( 'The given slug is already in use by another campaign.', 'groundhogg' ) );
			}
		}

		$campaign->update( [
			'name'        => $name,
			'description' => $description,
			'slug'        => $slug,
			'visibility'  => get_post_var( 'public' ) ? 'public' : 'hidden'
		] );

		/**
		 * After a campaign is saved from the admin
		 *
		 * @param int $campaign_id
		 */
		do_action( 'groundhogg/admin/campaigns/edit', $campaign->get_id() );

		return true;
	}

	/**
	 * What the editor beside the table shows for a campaign
	 *
	 * @param Campaign $campaign
	 *
	 * @return array
	 */
	protected function get_editor_item( Campaign $campaign ) {
		return [
			'ID'          => $campaign->get_id(),
			'name'        => $campaign->get_name(),
			'slug'        => $campaign->get_slug(),
			'description' => $campaign->get_description(),
			'visibility'  => $campaign->is_public() ? 'public' : 'hidden',
			'archive_url' => managed_page_url( sprintf( '/campaigns/%s', $campaign->get_slug() ) ),
			'assets'      => Campaigns_Table::get_asset_links( $campaign ),
			'row'         => ( new Campaigns_Table() )->get_row_html( $campaign ),
		];
	}

	/**
	 * Load a campaign into the editor beside the table
	 *
	 * @return void
	 */
	public function ajax_campaign_editor() {

		if ( ! verify_admin_ajax_nonce() || ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		$campaign = new Campaign( absint( get_post_var( 'campaign' ) ) );

		if ( ! $campaign->exists() ) {
			wp_send_json_error( [ 'message' => esc_html__( 'That campaign no longer exists.', 'groundhogg' ) ] );
		}

		wp_send_json_success( $this->get_editor_item( $campaign ) );
	}

	/**
	 * Save a campaign from the editor beside the table
	 *
	 * @return void
	 */
	public function ajax_save_campaign() {

		if ( ! verify_admin_ajax_nonce() || ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		$campaign = new Campaign( absint( get_post_var( 'campaign' ) ) );
		$updated  = $this->update_campaign( $campaign );

		if ( is_wp_error( $updated ) ) {
			wp_send_json_error( [ 'message' => $updated->get_error_message() ] );
		}

		wp_send_json_success( $this->get_editor_item( new Campaign( $campaign->get_id() ) ) );
	}

	/**
	 * Delete campaigns from the admin
	 *
	 * @return bool|WP_Error
	 */
	public function process_delete() {
		if ( ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		foreach ( $this->get_items() as $id ) {

			$campaign = new Campaign( $id );

			if ( ! $campaign->delete() ) {
				return new WP_Error( 'unable_to_delete', "Something went wrong deleting the campaign." );
			}
		}

		$this->add_notice(
			'deleted',
			/* translators: %d: the number of campaigns deleted */
			sprintf( _nx( '%d campaign deleted', '%d campaigns deleted', count( $this->get_items() ), 'notice', 'groundhogg' ),
				count( $this->get_items() )
			)
		);

		return false;
	}

	public function edit() {
		if ( ! current_user_can( 'manage_campaigns' ) ) {
			$this->wp_die_no_access();
		}

		include __DIR__ . '/edit.php';
	}

	protected function add_ajax_actions() {
		add_action( 'wp_ajax_groundhogg_campaign_editor', [ $this, 'ajax_campaign_editor' ] );
		add_action( 'wp_ajax_groundhogg_save_campaign', [ $this, 'ajax_save_campaign' ] );
	}

	protected function add_additional_actions() {
		// TODO: Implement add_additional_actions() method.
	}
}
