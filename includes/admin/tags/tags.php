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


class WPGH_Tags_Page
{
	function __construct()
	{
		if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_tags' ){

			add_action( 'init' , array( $this, 'process_action' )  );

		}
	}

	function get_tags()
	{
		$tags = isset( $_REQUEST['tag'] ) ? $_REQUEST['tag'] : null;

		if ( ! $tags )
			return false;

		return is_array( $tags )? array_map( 'intval', $tags ) : array( intval( $tags ) );
	}

	function get_action()
	{
		if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) )
			return false;

		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
			return $_REQUEST['action'];

		if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
			return $_REQUEST['action2'];

		return false;
	}

	function get_previous_action()
	{
		$action = get_transient( 'gh_last_action' );

		delete_transient( 'gh_last_action' );

		return $action;
	}

	function get_title()
	{
		switch ( $this->get_action() ){
			case 'edit':
				_e( 'Edit Tag' , 'groundhogg' );
				break;
			default:
				_e( 'Tags', 'groundhogg' );
		}
	}

	function process_action()
	{
		if ( ! $this->get_action() || ! $this->verify_action() || ! current_user_can( 'gh_manage_tags' ) )
			return;

		$base_url = remove_query_arg( array( '_wpnonce', 'action' ), wp_get_referer() );

		switch ( $this->get_action() )
		{
			case 'add':

				if ( isset( $_POST ) )
				{
					do_action( 'wpgh_add_tag' );

					wpgh_add_notice( 'create', __( 'Tag Created' ) );
				}

				break;

			case 'delete':

				foreach ( $this->get_tags() as $id ){
					wpgh_delete_tag( $id );
				}

				wpgh_add_notice( 'seleted', sprintf( '%d %s', count( $this->get_tags() ), __( 'tags deleted' ) ) );

				do_action( 'wpgh_delete_tags' );

				break;

			case 'edit':

				if ( isset( $_POST ) ){
					do_action( 'wpgh_update_tag', intval( $_GET[ 'tag' ] ) );

					wpgh_add_notice( 'create', __( 'Tag updated' ) );
				}

				break;
		}

		set_transient( 'gh_last_action', $this->get_action(), 30 );

		if ( $this->get_action() === 'edit' || $this->get_action() === 'add' )
			return;

		$base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_tags() ) ), $base_url );

		wp_redirect( $base_url );
		die();
	}


	function verify_action()
	{
        if ( ! isset( $_REQUEST['_wpnonce'] ) )
			return false;

//        var_dump( $_REQUEST['_wpnonce'] );

        return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-tags' );
	}

	function table()
	{
		if ( ! class_exists( 'WPGH_Tags_Table' ) ){
			include dirname( __FILE__ ) . '/class-tags-table.php';
		}

		$tags_table = new WPGH_Tags_Table(); ?>
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
						<?php $tags_table->prepare_items(); ?>
						<?php $tags_table->display(); ?>
                    </form>
                </div>
            </div>
        </div>
		<?php
	}

	function edit()
	{
		include dirname( __FILE__ ) . '/edit-tag.php';
	}

	function page()
	{
		?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action" href="<?php echo admin_url( 'admin.php?page=gh_tags' ); ?>"><?php _e( 'Add New' ); ?></a>
			<?php wpgh_notices(); ?>
            <hr class="wp-header-end">
			<?php switch ( $this->get_action() ){
				case 'edit':
					$this->edit();
					break;
				default:
					$this->table();
			} ?>
        </div>
		<?php
	}
}