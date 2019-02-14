<?php
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

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WPGH_Superlinks_Page
{

    /**
     * @var WPGH_Notices
     */
    public $notices;

	function __construct()
	{

	    add_action( 'admin_menu', array( $this, 'register' ) );

		if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_superlinks' ){

			add_action( 'init' , array( $this, 'process_action' )  );

			$this->notices = WPGH()->notices;
		}
	}

	/* Register the page */
	public function register()
    {
        $page = add_submenu_page(
            'groundhogg',
            _x( 'Superlinks', 'page_title', 'groundhogg' ),
            _x( 'Superlinks', 'page_title', 'groundhogg' ),
            'edit_superlinks',
            'gh_superlinks',
            array($this, 'page')
        );

        add_action("load-" . $page, array($this, 'help'));
    }

    /* Register the help bar */
    public function help()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'gh_overview',
                'title' => __('Overview'),
                'content' => '<p>' . __( "Superlinks are special superlinks that allow you to apply/remove tags whenever clicked and then take the contact to a page of your choice. To use them, just copy the replacement code and paste in in email, button, or link.", 'groundhogg' ) . '</p>'
            )
        );
    }

	function get_superlinks()
	{
		$superlinks = isset( $_REQUEST['superlink'] ) ? $_REQUEST['superlink'] : null;

		if ( ! $superlinks )
			return false;

		return is_array( $superlinks )? array_map( 'intval', $superlinks ) : array( intval( $superlinks ) );
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
				_e( 'Edit Superlink' , 'groundhogg' );
				break;
			default:
				_e( 'Superlinks', 'groundhogg' );
		}
	}

	function process_action()
	{
		if ( ! $this->get_action() || ! $this->verify_action() )
			return;

		$base_url = remove_query_arg( array( '_wpnonce', 'action' ), wp_get_referer() );

		switch ( $this->get_action() )
		{
			case 'add':

                if ( ! current_user_can( 'add_superlinks' ) ){
                    wp_die( WPGH()->roles->error( 'add_superlinks' ) );
                }

				if ( isset( $_POST ) ) {
					$this->add_superlink();
				}
				
				break;

            case 'edit':

                if ( ! current_user_can( 'edit_superlinks' ) ){
                    wp_die( WPGH()->roles->error( 'edit_superlinks' ) );
                }

                if ( isset( $_POST ) ){
                    $this->edit_superlink();
                }

                break;

            case 'delete':

                if ( ! current_user_can( 'delete_superlinks' ) ){
                    wp_die( WPGH()->roles->error( 'delete_superlinks' ) );
                }

				foreach ( $this->get_superlinks() as $id ){

					WPGH()->superlinks->delete( $id );

				}

                $this->notices->add( 'deleted', sprintf( '%d %s', count( $this->get_superlinks() ), __( 'superlinks deleted' ) ) );


                break;

        }

		set_transient( 'gh_last_action', $this->get_action(), 30 );

		if ( $this->get_action() === 'edit' || $this->get_action() === 'add' )
			return;

		$base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_superlinks() ) ), $base_url );

		wp_redirect( $base_url );
		die();
	}

	private function add_superlink()
    {
        if ( ! current_user_can( 'add_superlinks' ) ){
            wp_die( WPGH()->roles->error( 'add_superlinks' ) );
        }

        $superlink_name = sanitize_text_field( wp_unslash( $_POST['superlink_name'] ) );
        $superlink_target = sanitize_text_field( wp_unslash( $_POST['superlink_target'] ) );

        $superlink_tags = isset( $_POST['superlink_tags'] ) ? WPGH()->tags->validate( $_POST['superlink_tags'] ): array() ;

        $args = array(
            'name'      => $superlink_name,
            'target'    => $superlink_target,
            'tags'      => $superlink_tags
        );


        $superlink_id = WPGH()->superlinks->add( $args );

        if ( $superlink_id ){
            do_action( 'wpgh_superlink_created', $superlink_id );

            $this->notices->add( 'created', __( 'Superlink created.', 'groundhogg' ) );
        }
    }

    private function edit_superlink()
    {
        if ( ! current_user_can( 'edit_superlinks' ) ){
            wp_die( WPGH()->roles->error( 'edit_superlinks' ) );
        }

        $id = intval( $_GET[ 'superlink' ] );

        $superlink_name = sanitize_text_field( wp_unslash( $_POST['superlink_name'] ) );

        $superlink_target = sanitize_text_field( wp_unslash( $_POST['superlink_target'] ) );

        $superlink_tags = WPGH()->tags->validate( $_POST['superlink_tags'] );

        $args = array(
            'name'      => $superlink_name,
            'target'    => $superlink_target,
            'tags'      => $superlink_tags
        );

        $result = WPGH()->superlinks->update( $id, $args );

        if ( $result ) {
            $this->notices->add( 'updated', __( 'Updated superlink.', 'groundhogg' ) );
            do_action( 'wpgh_superlink_updated', $id );
        }

    }

	function verify_action()
	{
		if ( ! isset( $_REQUEST['_wpnonce'] ) )
			return false;

		return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-superlinks' );
	}

	function table()
	{
		if ( ! class_exists( 'WPGH_Superlinks_Table' ) ){
			include dirname(__FILE__) . '/class-wpgh-superlinks-table.php';
		}

		$superlinks_table = new WPGH_Superlinks_Table(); ?>
        <form method="post" class="search-form wp-clearfix">
        <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Superlinks ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Superlinks', 'groudhogg' )?>">
            </p>
        </form>
        <div id="col-container" class="wp-clearfix">
            <div id="col-left">
                <div class="col-wrap">
                    <div class="form-wrap">
                        <h2><?php _e( 'Add New Superlink', 'groundhogg' ) ?></h2>
                        <form id="addsuperlink" method="post" action="">
                            <input type="hidden" name="action" value="add">
                            <?php wp_nonce_field(); ?>
                            <div class="form-field term-name-wrap">
                                <label for="superlink-name"><?php _e( 'Superlink Name', 'groundhogg' ) ?></label>
                                <input name="superlink_name" id="superlink-name" type="text" value="" maxlength="100" autocomplete="off" required>
                                <p><?php _e( 'Name a Superlink something simple so you do not forget it.', 'groundhogg' ); ?></p>
                            </div>
                            <div class="form-field term-target-wrap">
                                <label for="superlink-target"><?php _e( 'Target URL', 'groundhogg' ) ?></label>
                                <input name="superlink_target" id="superlink-target" type="url" value="" autocomplete="off" required>
                                <p><a href="#" id="insert-link" data-target="superlink-target"><?php _e( 'Insert Link' ); ?></a> | <?php _e( 'Insert a url that this link will direct to. This link can contain simple replacement codes.', 'groundhogg' ); ?></p>
                                <script>
                                    jQuery( function($){
                                        $( '#insert-link' ).linkPicker();
                                    });
                                </script>
                            </div>
                            <div class="form-field term-tag-wrap">
                                <label for="superlink-description"><?php _e( 'Apply Tags When Clicked', 'groundhogg' ) ?></label>
                                <?php $tag_args = array();
                                $tag_args[ 'id' ] = 'superlink_tags';
                                $tag_args[ 'name' ] = 'superlink_tags[]';
//                                $tag_args[ 'width' ] = '100%'; ?>
                                <?php echo WPGH()->html->tag_picker( $tag_args ); ?>
                                <p><?php _e( 'These tags will be applied to a contact whenever this link is clicked. To create a new tag hit [Enter] or [,]', 'groundhogg' ); ?></p>
                            </div>
                            <?php submit_button( __( 'Add New Superlink', 'groundhogg' ), 'primary', 'add_superlink' ); ?>
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
        if ( ! current_user_can( 'edit_superlinks' ) ){
            wp_die( WPGH()->roles->error( 'edit_superlinks' ) );
        }

		include dirname( __FILE__ ) . '/edit-superlink.php';
	}

	function page()
	{

		wp_enqueue_editor();
		wp_enqueue_script('wplink');
		wp_enqueue_style('editor-buttons');
		wp_enqueue_script( 'link-picker', WPGH_ASSETS_FOLDER . '/js/admin/link-picker.js' );
		
		?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action" href="<?php echo admin_url( 'admin.php?page=gh_superlinks' ); ?>"><?php _e( 'Add New', 'groundhogg' ); ?></a>
			<?php $this->notices->notices(); ?>
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