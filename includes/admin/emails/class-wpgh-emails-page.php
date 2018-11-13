<?php
/**
 * View Emails
 *
 * Allow the user to view & edit the emails
 * Contains add, save, delete, etc for the admin functions...
 *
 * @package     Admin
 * @subpackage  Admin/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WPGH_Emails_Page
{
    /**
     * @var WPGH_Notices
     */
    public $notices;

    /**
     * WPGH_Emails_Page constructor.
     */
    function __construct()
    {

        add_action( 'admin_menu', array( $this, 'register' ) );

        add_action( 'wp_ajax_gh_update_email', array( $this, 'update_email_ajax' ) );

        $this->notices = WPGH()->notices;

        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_emails' ){

            add_action( 'init' , array( $this, 'process_action' )  );
            add_action( 'admin_enqueue_scripts' , array( $this, 'scripts' )  );

        }
    }

    public function scripts()
    {
        if ( $this->get_action() === 'edit' ){

            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-draggable' );

            wp_enqueue_script( 'sticky-sidebar', WPGH_ASSETS_FOLDER . '/lib/sticky-sidebar/sticky-sidebar.js' );
//            wp_enqueue_script( 'jquery-sticky-sidebar', WPGH_ASSETS_FOLDER . '/lib/sticky-sidebar/jquery.sticky-sidebar.js' );

            wp_enqueue_script( 'email-editor', WPGH_ASSETS_FOLDER . 'js/admin/email-editor.js', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/email-editor.js' ) );
            wp_enqueue_style('email-editor', WPGH_ASSETS_FOLDER . 'css/admin/email-editor.css', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/css/admin/email-editor.css' ) );

        } else if ( $this->get_action() === 'add' || $this->get_action() === 'edit' ){
	        wp_enqueue_script( 'iframe-checker', WPGH_ASSETS_FOLDER . 'js/admin/iframe-checker.js', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/js/admin/iframe-checker.js' ) );
	        wp_enqueue_style( 'iframe-checker', WPGH_ASSETS_FOLDER . 'css/admin/iframe.css', array(), filemtime( WPGH_PLUGIN_DIR . 'assets/css/admin/iframe.css' ) );
        }
    }


    public function register()
    {
        $page = add_submenu_page(
            'groundhogg',
            'Emails',
            'Emails',
            'edit_emails',
            'gh_emails',
            array($this, 'page')
        );

        add_action("load-" . $page, array($this, 'help'));
    }

    public function help()
    {
        $screen = get_current_screen();

        $screen->add_help_tab(
            array(
                'id' => 'gh_overview',
                'title' => __('Overview'),
                'content' => '<p>' . __('Unlike most marketing automation platforms Groundhogg made the decision to <strong>store emails globally</strong>. That means that you can use the same email across different funnels without ever having to re-write it.
                From this screen you can <strong>View/Edit/Delete</strong> emails and see their respective open rates across different funnels.', 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_add',
                'title' => __('Add New'),
                'content' => '<p>' . __('When you add a new email you can either select a pre-written email template created by our in house digital marketing specialists or you can select one of your own past written emails to copy.', 'groundhogg') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_edit',
                'title' => __('Editing'),
                'content' => '<p>' . __('When you are editing an email, you can drag in new blocks from the right hand side into your email content. You can edit the appearance of the email with the settings on the left hand side of the email content. When designing our
                email builder we made the decision to keep features sparse. Hence no columns and no frilly html stuff. Just the basics. The reason for this being 80% of email is read on mobile, so our email builder is optimized for better mobile consumption.', 'groundhogg') . '</p>' .
                    '<p>' . __('When you believe an email is ready for sending set the status to <strong>Ready</strong> and then you can use it in any broadcast or funnel. An email which is in draft mode will not be sent.') . '</p>'
            )
        );

        $screen->add_help_tab(
            array(
                'id' => 'gh_test',
                'title' => __('Testing'),
                'content' => '<p>' . __('To test an email simply check off the <strong>Send Test</strong> checkbox and select the user account you\'d like to send the test to. For best results, use a minimal number of images, make text easy to read and write good content. Tests will be sent regardless of the email\'s current status.', 'groundhogg') . '</p>'
            )
        );
    }


    /**
     * Get affected emails
     *
     * @return array|bool
     */
    function get_emails()
    {
        $emails = isset( $_REQUEST['email'] ) ? $_REQUEST['email'] : null;

        if ( ! $emails )
            return false;

        return is_array( $emails )? array_map( 'intval', $emails ) : array( intval( $emails ) );
    }

    /**
     * Get the action
     *
     * @return bool|string
     */
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

    /**
     * Get the last completed action
     *
     * @return mixed
     */
    function get_previous_action()
    {
        $action = get_transient( 'gh_last_action' );

        delete_transient( 'gh_last_action' );

        return $action;
    }

    /**
     * Get the title of the current page
     */
    function get_title()
    {
        switch ( $this->get_action() ){
            case 'add':
                _e( 'Add Email' , 'groundhogg' );
                break;
            case 'edit':
                _e( 'Edit Email' , 'groundhogg' );
                break;
            default:
                _e( 'Emails', 'groundhogg' );
        }
    }

    /**
     * Process the current action based on the admin view and any post variables
     */
    function process_action()
    {
        if ( ! $this->get_action() || ! $this->verify_action() )
            return;

        $base_url = remove_query_arg( array( '_wpnonce', 'action' ), wp_get_referer() );

        switch ( $this->get_action() )
        {
            case 'add':

                if ( ! current_user_can( 'add_emails' ) ){
                    wp_die( WPGH()->roles->error( 'add_emails' ) );
                }

                if ( ! empty( $_POST ) ) {

                    $this->add_email();

                }

                break;

            case 'edit':

                if ( ! current_user_can( 'edit_emails' ) ){
                    wp_die( WPGH()->roles->error( 'edit_emails' ) );
                }

                if ( ! empty( $_POST ) ){

                    $this->update_email();

                }

                break;

            case 'trash':

                if ( ! current_user_can( 'edit_emails' ) ){
                    wp_die( WPGH()->roles->error( 'edit_emails' ) );
                }

                foreach ( $this->get_emails() as $id ) {

                    $args = array( 'status' => 'trash' );

                    WPGH()->emails->update( $id, $args );

                }

	            $this->notices->add(
		            esc_attr( 'trashed' ),
		            sprintf( "%s %d %s",
			            __( 'Trashed' ),
			            count( $this->get_emails() ),
			            __( 'Emails', 'groundhogg' ) ),
		            'success'
	            );

                do_action( 'wpgh_trash_emails' );

                break;

            case 'delete':

                if ( ! current_user_can( 'delete_emails' ) ){
                    wp_die( WPGH()->roles->error( 'delete_emails' ) );
                }

                foreach ( $this->get_emails() as $id ){
                    WPGH()->emails->delete( $id );
                }

                $this->notices->add(
		            esc_attr( 'deleted' ),
		            sprintf( "%s %d %s",
			            __( 'Deleted' ),
			            count( $this->get_emails() ),
			            __( 'Emails', 'groundhogg' ) ),
		            'success'
	            );

                do_action( 'wpgh_delete_emails' );

                break;

            case 'empty_trash':

                if ( ! current_user_can( 'delete_emails' ) ){
                    wp_die( WPGH()->roles->error( 'delete_emails' ) );
                }

                $emails = WPGH()->emails->get_emails( array( 'status' => 'trash' ) );

                foreach ( $emails as $email ){
                    WPGH()->emails->delete( $email->ID );
                }

                $this->notices->add(
                    esc_attr( 'deleted' ),
                    sprintf( "%s %d %s",
                        __( 'Deleted' ),
                        count( $emails ),
                        __( 'Emails', 'groundhogg' ) ),
                    'success'
                );

                break;

            case 'restore':

                if ( ! current_user_can( 'edit_emails' ) ){
                    wp_die( WPGH()->roles->error( 'edit_emails' ) );
                }

                foreach ( $this->get_emails() as $id )
                {
                    $args = array( 'status' => 'draft' );

                    WPGH()->emails->update( $id, $args );                }

                $this->notices->add(
		            esc_attr( 'restored' ),
		            sprintf( "%s %d %s",
			            __( 'Restored' ),
			            count( $this->get_emails() ),
			            __( 'Emails', 'groundhogg' ) ),
		            'success'
	            );

                do_action( 'wpgh_restore_emails' );

                break;

        }

        set_transient( 'gh_last_action', $this->get_action(), 30 );

        if ( $this->get_action() === 'edit' || $this->get_action() === 'add' )
            return;

        $this->get_emails();

        if ( $this->get_emails() ){
            $base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_emails() ) ), $base_url );
        }

        wp_redirect( $base_url );
        die();
    }

    /**
     * Create an email and then redirect to the edit page
     */
    private function add_email()
    {
        if ( ! current_user_can( 'add_emails' ) ){
            wp_die( WPGH()->roles->error( 'add_emails' ) );
        }

        if ( isset( $_POST[ 'email_template' ] ) ){

            include_once WPGH_PLUGIN_DIR . '/templates/email-templates.php';

            /**
             * @var $email_templates array
             * @see /templates/email-templates.php
             */
            $email_content = $email_templates[ $_POST[ 'email_template' ] ][ 'content' ];

        } else if ( isset( $_POST[ 'email_id' ] ) ) {

            $email = WPGH()->emails->get( intval( $_POST['email_id'] ) );
            $email_content = $email->content;

        } else {

            $this->notices->add( 'ooops', __( 'Could not create email.', 'groundhogg' ), 'error' );
            return;

        }

        $email = array(
            'content'   => $email_content,
            'status'    => 'draft',
            'author'    => get_current_user_id(),
            'from_user' => get_current_user_id(),
        );

        $email_id = WPGH()->emails->add( $email );

        if ( ! $email_id ){

            $this->notices->add( 'ooops', __( 'Could not create email.', 'groundhogg' ), 'error' );
            return;

        }

        $return_path = admin_url( 'admin.php?page=gh_emails&action=edit&email=' .  $email_id );

        if ( isset( $_GET['return_step'] ) ){

            /* Make it easy to return back to the funnel editing screen */
            $step_id = intval( $_GET['return_step'] );
            $funnel_id = intval( $_GET['return_funnel'] );
            $return_path .= sprintf( "&return_funnel=%s&return_step=%s", $funnel_id, $step_id );

            WPGH()->step_meta->update_meta( $step_id, 'email_id', $email_id );

        }

        do_action( 'wpgh_add_email', $email_id );

        wp_redirect( $return_path );

        die();
    }

    public function update_email_ajax()
    {

        if ( ! wp_doing_ajax() ){
            return;
        }

        $this->update_email();

        ob_start();

        $this->notices->notices();

        $notices = ob_get_clean();

        $response = array(
            'notices'   => $notices
        );

        wp_die( json_encode( $response ) );

    }

    /**
     * Update the current email
     */
    private function update_email()
    {
        if ( ! current_user_can( 'edit_emails' ) ){
            wp_die( WPGH()->roles->error( 'edit_emails' ) );
        }

        $id = intval( $_REQUEST[ 'email' ] );

        do_action( 'wpgh_email_update_before', $id );

        $args = array();

        $status = ( isset( $_POST['email_status'] ) )? sanitize_text_field( trim( stripslashes( $_POST['email_status'] ) ) ): 'draft';
        $args[ 'status' ] = $status;

        if ( $status === 'draft' ) {
            $this->notices->add( 'email-in-draft-mode', __( 'This email will not be sent while in DRAFT mode.', 'groundhogg' ), 'info' );
        }

        $from_user =  ( isset( $_POST['from_user'] ) )? intval( $_POST['from_user'] ): -1;
        $args[ 'from_user' ] = $from_user;

        $subject =  ( isset( $_POST['subject'] ) )? wp_strip_all_tags( sanitize_text_field( trim( stripslashes( $_POST['subject'] ) ) ) ): '';
        $args[ 'subject' ] = $subject;

        $pre_header =  ( isset( $_POST['pre_header'] ) )? wp_strip_all_tags( sanitize_text_field( trim( stripslashes( $_POST['pre_header'] ) ) ) ): '';
        $args[ 'pre_header' ] = $pre_header;

        $content =  ( isset( $_POST['content'] ) )? apply_filters( 'wpgh_sanitize_email_content', wpgh_minify_html( trim( stripslashes( $_POST['content'] ) ) ) ): '';
        $args[ 'content' ] = $content;

        $args[ 'last_updated' ] = current_time( 'mysql' );

        WPGH()->emails->update( $id, $args );

        $alignment =  ( isset( $_POST['email_alignment'] ) )? sanitize_text_field( trim( stripslashes( $_POST['email_alignment'] ) ) ): '';
        WPGH()->email_meta->update_meta( $id, 'alignment', $alignment );

        $browser_view =  ( isset( $_POST['browser_view'] ) )? 1 : false;
        WPGH()->email_meta->update_meta( $id, 'browser_view', $browser_view );

        do_action( 'wpgh_email_update_after', $id );

        $this->notices->add( 'email-updated', __( 'Email Updated.', 'groundhogg' ), 'success' );

        if ( isset( $_POST['send_test'] ) ){

            if ( ! current_user_can( 'send_emails' ) ){
                wp_die( WPGH()->roles->error( 'send_emails' ) );
            }

            do_action( 'wpgh_before_send_test_email', $id );

            $test_email_uid =  ( isset( $_POST['test_email'] ) )? intval( $_POST['test_email'] ): '';
            WPGH()->email_meta->update_meta( $id, 'test_email', $test_email_uid );

            $email = new WPGH_Email( $id );

            $email->enable_test_mode();

            $user = get_userdata( $test_email_uid );

            $contact = new WPGH_Contact( $user->user_email );

            $sent = $contact->exists() ? $email->send( $contact ) : false;

            if ( ! $sent || is_wp_error( $sent ) ){
                if ( is_wp_error( $sent ) ){
                    $this->notices->add( 'oops', __( 'Failed to send test: ' . $sent->get_error_message() ), 'error' );
                } else {
                    $this->notices->add( 'oops', __( 'Failed to send test: ' . $email->get_error_message() ), 'error' );
                }
            } else {
                $this->notices->add(
                    esc_attr( 'sent-test' ),
                    sprintf( "%s %s",
                        __( 'Sent test email to', 'groundhogg' ),
                        get_userdata( $test_email_uid )->user_email ),
                    'success'
                );
            }

            do_action( 'wpgh_after_send_test_email', $id );
        }

    }

    function verify_action()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) )
            return false;

        return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() )|| wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-emails' );
    }

    function table()
    {
        if ( ! class_exists( 'WPGH_Emails_Table' ) ){
            include dirname(__FILE__) . '/class-wpgh-emails-table.php';
        }

        $emails_table = new WPGH_Emails_Table();

        $emails_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Emails ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Emails ', 'groundhogg'); ?>">
            </p>
            <?php $emails_table->prepare_items(); ?>
            <?php $emails_table->display(); ?>
        </form>

        <?php
    }

    /**
     * Include the blocks...
     */
    private function include_blocks()
    {
        require_once dirname( __FILE__ ) . '/blocks/wpgh-email-block.php';
        require_once dirname( __FILE__ ) . '/blocks/wpgh-text-block.php';
        require_once dirname( __FILE__ ) . '/blocks/wpgh-image-block.php';
        require_once dirname( __FILE__ ) . '/blocks/wpgh-divider-block.php';
        require_once dirname( __FILE__ ) . '/blocks/wpgh-spacer-block.php';
        require_once dirname( __FILE__ ) . '/blocks/wpgh-button-block.php';
        require_once dirname( __FILE__ ) . '/blocks/wpgh-html-block.php';
    }

    /**
     * init the blocks
     *
     * @return array
     */
    private function get_blocks()
    {

        $blocks = array();

        $blocks[] = new WPGH_Text_Block();
        $blocks[] = new WPGH_Image_Block();
        $blocks[] = new WPGH_Divider_Block();
        $blocks[] = new WPGH_Spacer_Block();
        $blocks[] = new WPGH_Button_Block();
        $blocks[] = new WPGH_HTML_Block();

        return apply_filters( 'wpgh_setup_email_blocks', $blocks );

    }

    function edit()
    {

        if ( ! current_user_can( 'edit_emails' ) ){
            wp_die( WPGH()->roles->error( 'edit_emails' ) );
        }

        $this->include_blocks();
        $this->get_blocks();

        include dirname( __FILE__ ) . '/email-editor.php';

    }

    function add()
    {
        if ( ! current_user_can( 'add_emails' ) ){
            wp_die( WPGH()->roles->error( 'add_emails' ) );
        }

        include dirname( __FILE__ ) . '/add-email.php';
    }

    function page()
    {
        if ( ! current_user_can( 'edit_emails' ) ){
            wp_die( WPGH()->roles->error( 'edit_emails' ) );
        }

        if ( $this->get_action() === 'edit' ){

            $this->edit();

        } else {
            ?>
            <div class="wrap">
                <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_emails&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
                <?php $this->notices->notices(); ?>
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
}