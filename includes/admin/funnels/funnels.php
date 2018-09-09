<?php
/**
 * View Funnels
 *
 * Allow the user to view & edit the funnels
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */



// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WPGH_Funnels_Page
{
	function __construct()
	{
		if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_funnels' ){

			add_action( 'init' , array( $this, 'process_action' )  );

            foreach ( glob( dirname( __FILE__ ) . "/elements/*/*.php" ) as $filename )
            {
                include $filename;
            }
		}
	}

	function get_funnels()
	{
		$funnels = isset( $_REQUEST['funnel'] ) ? $_REQUEST['funnel'] : null;

		if ( ! $funnels )
			return false;

		return is_array( $funnels )? array_map( 'intval', $funnels ) : array( intval( $funnels ) );
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
			case 'add':
				_e( 'Add Funnel' , 'groundhogg' );
				break;
			case 'edit':
				_e( 'Edit Funnel' , 'groundhogg' );
				break;
			default:
				_e( 'Funnels', 'groundhogg' );
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

				if ( isset( $_POST ) )
				{
					wpgh_add_notice( esc_attr( 'created' ), __( 'Funnel Created', 'groundhogg' ), 'success' );

					do_action( 'wpgh_add_funnel' );
				}

				break;

			case 'archive':

				foreach ( $this->get_funnels() as $id ) {
					wpgh_update_funnel($id, 'funnel_status', 'archived');
				}

				wpgh_add_notice(
                    esc_attr( 'archived' ),
                    sprintf( "%s %d %s",
                        __( 'Archived', 'groundhogg' ),
                        count( $this->get_funnels() ),
                        'Funnels' ),
                    'success'
                );

				do_action( 'wpgh_archive_funnels' );

				break;

			case 'delete':

				foreach ( $this->get_funnels() as $id ){
					wpgh_delete_funnel( $id );
				}

				wpgh_add_notice(
					esc_attr( 'deleted' ),
					sprintf( "%s %d %s",
						__( 'Deleted', 'groundhogg' ),
						count( $this->get_funnels() ),
						'Funnels' ),
					'success'
				);

				do_action( 'wpgh_delete_funnels' );

				break;

			case 'restore':

				foreach ( $this->get_funnels() as $id )
				{
					wpgh_update_funnel( $id, 'funnel_status', 'inactive' );
				}

				wpgh_add_notice(
					esc_attr( 'restored' ),
					sprintf( "%s %d %s",
						__( 'Restored', 'groundhogg' ),
						count( $this->get_funnels() ),
						'Funnels' ),
					'success'
				);

				do_action( 'wpgh_restore_funnels' );

				break;

			case 'edit':

				if ( isset( $_POST ) ){
					do_action( 'wpgh_update_funnel', intval( $_GET[ 'funnel' ] ) );
					wpgh_add_notice( esc_attr( 'updated' ), __( 'Funnel Updated', 'groundhogg' ), 'success' );
				}

				break;
		}

		set_transient( 'gh_last_action', $this->get_action(), 30 );

		if ( $this->get_action() === 'edit' || $this->get_action() === 'add' )
			return;

		$base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_funnels() ) ), $base_url );

		wp_redirect( $base_url );
		die();
	}

	function verify_action()
	{
		if ( ! isset( $_REQUEST['_wpnonce'] ) )
			return false;

		return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-funnels' ) ;
	}

	function table()
	{
		if ( ! class_exists( 'WPGH_Funnels_Table' ) ){
			include dirname( __FILE__ ) . '/class-funnels-table.php';
		}

		$funnels_table = new WPGH_Funnels_Table();

		$funnels_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Funnels ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Funnels ', 'groundhogg'); ?>">
            </p>
			<?php $funnels_table->prepare_items(); ?>
			<?php $funnels_table->display(); ?>
        </form>

		<?php
	}

	function edit()
	{
		include dirname( __FILE__ ) . '/funnel-editor.php';

	}

	function add()
	{
		include dirname( __FILE__ ) . '/add-funnel.php';
	}

	function page()
	{
		?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_funnels&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
			<?php wpgh_notices(); ?>
            <hr class="wp-header-end">
			<?php switch ( $this->get_action() ){
				case 'add':
					$this->add();
					break;
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