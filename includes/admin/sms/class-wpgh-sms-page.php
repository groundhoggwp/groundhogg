<?php
/**
 * SMS Page
 *
 * This is the sms page, it also contains the add form since it's the same layout as the terms.php
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


class WPGH_SMS_Page
{

    /**
     * @var WPGH_Notices
     */
    public $notices;
    public $order = 26;

    const MAX_LENGTH = 280;

    function __construct()
    {

        add_action('admin_menu', array($this, 'register'), $this->order);
	    add_action( 'wp_ajax_gh_sms_broadcast_schedule', [ $this, 'ajax_bulk_schedule' ] );

	    $this->notices = WPGH()->notices;
	    if (isset($_GET['page']) && $_GET['page'] === 'gh_sms') {
	        add_action('init', array($this, 'process_action'));
        }
    }

    /* Register the page */
    public function register()
    {
        $page = add_submenu_page(
            'groundhogg',
            _x('Sms', 'page_title', 'groundhogg'),
            _x('Sms', 'page_title', 'groundhogg'),
            'edit_sms',
            'gh_sms',
            array($this, 'page')
        );

        add_action("load-" . $page, array($this, 'help'));
    }

    /* Register the help bar */
    public function help()
    {

    }

    function get_sms()
    {
        $sms = isset($_REQUEST['sms']) ? $_REQUEST['sms'] : null;

        if (!$sms)
            return false;

        return is_array($sms) ? array_map('intval', $sms) : array(intval($sms));
    }

    function get_action()
    {
        if (isset($_REQUEST['filter_action']) && !empty($_REQUEST['filter_action']))
            return false;

        if (isset($_REQUEST['action']) && -1 != $_REQUEST['action'])
            return $_REQUEST['action'];

        if (isset($_REQUEST['action2']) && -1 != $_REQUEST['action2'])
            return $_REQUEST['action2'];

        return false;
    }

    function get_previous_action()
    {
        $action = get_transient('gh_last_action');

        delete_transient('gh_last_action');

        return $action;
    }

    function get_title()
    {
        switch ($this->get_action()) {
            case 'broadcast':
                _ex('SMS Broadcast', 'page_title', 'groundhogg');
                break;
	        case 'schedule':
		        _ex('Scheduling...', 'page_title', 'groundhogg');
		        break;
            case 'edit':
                _ex('Edit SMS', 'page_title', 'groundhogg');
                break;
            default:
                _ex('SMS', 'page_title', 'groundhogg');
        }
    }

    function process_action()
    {
        if (!$this->get_action() || !$this->verify_action())
            return;

        $base_url = remove_query_arg(array('_wpnonce', 'action'), wp_get_referer());

        switch ($this->get_action()) {
            case 'add':

                if (!current_user_can('add_sms')) {
                    wp_die(WPGH()->roles->error('add_sms'));
                }

                if (isset($_POST)) {
                    $this->add_sms();
                }

                break;

            case 'edit':

                if (!current_user_can('edit_sms')) {
                    wp_die(WPGH()->roles->error('edit_sms'));
                }

                if (isset($_POST)) {
                    $this->edit_sms();
                }

                break;

            case 'broadcast':
                if (!current_user_can('edit_sms')) {
                    wp_die(WPGH()->roles->error('edit_sms'));
                }

                $this->schedule_broadcast();

                break;

            case 'delete':

                if (!current_user_can('delete_sms')) {
                    wp_die(WPGH()->roles->error('delete_sms'));
                }

                foreach ($this->get_sms() as $id) {

                    WPGH()->sms->delete($id);

                }

                $this->notices->add('deleted', sprintf(_nx('%d sms deleted', '%d sms deleted', count($this->get_sms()), 'notice', 'groundhogg'), count($this->get_sms())));

                break;

        }

        set_transient('gh_last_action', $this->get_action(), 30);

        if ($this->get_action() === 'edit' || $this->get_action() === 'add' || $this->get_action() == 'broadcast' )
            return;

        if( $this->get_action() !== 'broadcast' ){
            $base_url = add_query_arg('ids', urlencode(implode(',', $this->get_sms())), $base_url);
        }

        wp_redirect($base_url);
        die();
    }

    private function add_sms()
    {
        if (!current_user_can('add_sms')) {
            wp_die(WPGH()->roles->error('add_sms'));
        }

        $title = sanitize_text_field(stripslashes($_POST['title']));
        $message = sanitize_textarea_field(wp_strip_all_tags(stripslashes($_POST['message'])));

        $args = array(
            'title' => $title,
            'message' => $message,
        );

        $sms_id = WPGH()->sms->add($args);

        if ($sms_id) {
            do_action('wpgh_sms_created', $sms_id);
            $this->notices->add('created', _x('SMS created', 'notice', 'groundhogg'));
        }
    }

    private function edit_sms()
    {
        if (!current_user_can('edit_sms')) {
            wp_die(WPGH()->roles->error('edit_sms'));
        }

        $id = intval($_GET['sms']);
        $title = sanitize_text_field(stripslashes($_POST['title']));
        $message = sanitize_textarea_field(wp_strip_all_tags(stripslashes($_POST['message'])));

        $args = array(
            'title' => $title,
            'message' => $message,
        );

        $result = WPGH()->sms->update($id, $args);

        if ($result) {
            $this->notices->add('updated', _x('Updated SMS.', 'notice', 'groundhogg'));
            do_action('wpgh_sms_updated', $id);
        }

    }

	function verify_action()
	{
		if ( ! isset( $_REQUEST['_wpnonce'] ) )
			return false;

		return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-sms' );
	}

	function table()
	{
		if ( ! class_exists( 'WPGH_SMS_Table' ) ){
			include dirname(__FILE__) . '/class-wpgh-sms-table.php';
		}

		$sms_table = new WPGH_SMS_Table(); ?>
        <form method="post" class="search-form wp-clearfix">
        <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search SMS', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search SMS', 'groundhogg' )?>">
            </p>
        </form>
        <div id="col-container" class="wp-clearfix">
            <div id="col-left">
                <div class="col-wrap">
                    <div class="form-wrap">
                        <h2><?php _e( 'Add New SMS', 'groundhogg' ) ?></h2>
                        <form id="addsms" method="post" action="">
                            <input type="hidden" name="action" value="add">
                            <?php wp_nonce_field(); ?>
                            <div class="form-field term-name-wrap">
                                <label for="sms-title"><?php _e( 'Title', 'groundhogg' ) ?></label>
                                <input name="title" id="sms-title" type="text" value="" maxlength="100" autocomplete="off" required>
                                <p><?php _e( 'Name it something simple so you do not forget it.', 'groundhogg' ); ?></p>
                            </div>
                            <div class="form-field term-target-wrap">
                                <label for="sms-message"><?php _e( 'Message', 'groundhogg' ) ?></label>
                                <textarea rows="5" name="message" id="sms-message" autocomplete="off" required></textarea>
                                <p class="description">
	                                <?php WPGH()->replacements->show_replacements_button(); ?>
                                    <?php _e( 'Use any valid replacement codes in your text message. You will be charged 1 credit per every 140 characters.', 'groundhogg' ); ?>&nbsp;<b>(<span id="characters">0</span>)</b>
                                </p>
                                <script>jQuery( '#sms-message' ).on( 'keydown', function () {
                                        jQuery( '#characters' ).text( jQuery( '#sms-message' ).val().length )
                                    } );</script>
                            </div>
                            <?php submit_button( _x( 'Add New SMS', 'action', 'groundhogg' ), 'primary', 'add_sms' ); ?>
                        </form>
                    </div>
                </div>
            </div>
            <div id="col-right">
                <div class="col-wrap">
                    <form id="posts-filter" method="post">
                        <?php $sms_table->prepare_items(); ?>
                        <?php $sms_table->display(); ?>
                    </form>
                </div>
            </div>
        </div>
		<?php
	}

	function edit()
	{
        if ( ! current_user_can( 'edit_sms' ) ){
            wp_die( WPGH()->roles->error( 'edit_sms' ) );
        }

		include dirname(__FILE__) . '/edit-sms.php';
	}

	function page()
	{

	    if ( $this->get_action() === 'broadcast' ){
	        $this->notices->add( 'no_cancel', _x( 'Warning: There is currently no ability to cancel SMS broadcasts at this time.', 'notice', 'groundhogg' ), 'warning' );
        }

		?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1>
            <a class="page-title-action" href="<?php echo admin_url( 'admin.php?page=gh_sms' ); ?>"><?php _ex( 'Add New', 'page_tile_action','groundhogg' ); ?></a>
            <a class="page-title-action" href="<?php echo admin_url( 'admin.php?page=gh_broadcasts&action=add&type=sms' ); ?>"><?php _ex( 'SMS Broadcast', 'page_tile_action','groundhogg' ); ?></a>
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