<?php
namespace  Groundhogg\Admin\Broadcasts;
use Groundhogg\Admin\Admin_Page;
use Groundhogg\Broadcast;
use Groundhogg\Bulk_Jobs\Broadcast_Scheduler;
use Groundhogg\Plugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The page gh_broadcasts
 *
 * This class adds the broadcasts page to the menu and renders the output for the broadcasts page
 * IT also contains the private functions add() and cancel()
 * These are made private for good reason as the broadcasts function was decided to be kept a closed process.
 * If you are a developer, simply BUGGER OFF!
 *
 * @package     Admin
 * @subpackage  Admin/Broadcasts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

class Broadcasts_Page extends Admin_Page
{

    /**
     * @var Broadcast_Scheduler
     */
    public $scheduler;

    protected function add_ajax_actions() {}
    public function help() {}

    protected function add_additional_actions()
    {
        $this->scheduler = new Broadcast_Scheduler();
    }

    /**
     * enqueue editor scripts
     */
    public function scripts(){
        wp_enqueue_script('jquery-flot' );
        wp_enqueue_script('jquery-flot-pie' );
    }

    public function get_priority()
    {
        return 25;
    }

    public function get_slug()
    {
        return 'gh_broadcasts';
    }

    public function get_name()
    {
        return _x( 'Broadcasts', 'page_title', 'groundhogg' );
    }

    public function get_cap()
    {
        return 'view_broadcasts';
    }

    public function get_item_type()
    {
        return 'broadcast';
    }

    /**
     * Get the current screen title
     */
    function get_title()
    {
        switch ($this->get_current_action()) {
            case 'add':
                return _x('Schedule Broadcast', 'page_title', 'groundhogg');
                break;
            default:
                return _x('Broadcasts', 'page_title', 'groundhogg');
                break;
        }
    }


    public function process_cancel()
    {
        if (!current_user_can('cancel_broadcasts')) {
            $this->wp_die_no_access();
        }

        foreach ($this->get_items() as $id) {

            $broadcast = new Broadcast( $id );
            $broadcast->cancel();
        }

        $this->add_notice('cancelled', sprintf(_nx('%d broadcasts cancelled', '%d broadcast cancelled', count($this->get_items()), 'notice', 'groundhogg'), count($this->get_items())));

        return false;
    }

    /**
     * Schedule a new broadcast
     */
    public function process_add()
    {
        if (!current_user_can('schedule_broadcasts')) {
            $this->wp_die_no_access();
        }

        $config = [];

        $object_id = isset($_POST['object_id']) ? intval($_POST['object_id']) : null;
        if (!$object_id) {
            return new \WP_Error( 'unable_to_add_tags', __( 'Please select an email or SMS to send.' ,'groundhogg'));
        }

        /* Set the object  */
        $config['object_id'] = $object_id;
        $config['object_type'] = isset( $_REQUEST[ 'type' ] ) && $_REQUEST[ 'type' ] === 'sms' ? 'sms' : 'email';

        if ( $config[ 'object_type' ] === 'email' ){

            $email = Plugin::$instance->utils->get_email($object_id);
            if ( $email->is_draft() ){
                return new \WP_Error( 'email_in_draft_mode', __( 'You cannot schedule an email while it is in draft mode.' ,'groundhogg'));
            }
        }

        $tags = isset($_POST['tags']) ? Plugin::$instance->dbs->get_db('tags')->validate( wp_unslash($_POST['tags'])) : [];
        if (empty($tags) || !is_array($tags)) {
             return new \WP_Error( 'email_in_draft_mode', __( 'You cannot schedule an email while it is in draft mode.' ,'groundhogg'));
        }

        $exclude_tags = isset($_POST['exclude_tags']) ?  Plugin::$instance->dbs->get_db('tags' )->validate( wp_unslash( $_POST['exclude_tags'] ) ) : [];

        $contact_sum = 0;

        foreach ($tags as $tag) {
            $tag = Plugin::$instance->dbs->get_db('tags')->get(intval( $tag ));
            if ($tag) {
                $contact_sum += $tag->contact_count;
            }
        }

        if ($contact_sum === 0) {
             return new \WP_Error( 'no_contacts', __( 'Please select a tag with at least 1 contact','groundhogg'));
        }

        $send_date = isset($_POST['date']) ? $_POST['date'] : date('Y/m/d', strtotime('tomorrow'));
        $send_time = isset($_POST['time']) ? $_POST['time'] : '09:30';

        $time_string = $send_date . ' ' . $send_time;

        /* convert to UTC */
        $send_time = Plugin::$instance->utils->date_time->convert_to_utc_0(strtotime($time_string));

        if (isset($_POST['send_now'])) {
            $config['send_now'] = true;
            $send_time = time() + 10;
        }

        if ($send_time < time()) {
            return new \WP_Error( 'invalid_date', __( 'Please select a time in the future','groundhogg'));
        }

        /* Set the email */
        $config['send_time'] = $send_time;

        $args = array(
            'object_id' => $object_id,
            'object_type' => $config[ 'object_type' ],
            'tags' => $tags,
            'send_time' => $send_time,
            'scheduled_by' => get_current_user_id(),
            'status' => 'scheduled',
        );

        $broadcast_id = Plugin::$instance->dbs->get_db('broadcasts')->add($args);

        if (!$broadcast_id) {
            return new \WP_Error( 'unable_to_add_broadcast', __( 'Something went wrong while adding the broadcast.','groundhogg'));
        }

        $config['broadcast_id'] = $broadcast_id;

        $query = array(
            'tags_include' => $tags,
            'tags_exclude' => $exclude_tags
        );

        $config['contact_query'] = $query;

        if (isset($_POST['send_in_timezone'])) {
            $config['send_in_local_time'] = true;
        }

        set_transient('gh_get_broadcast_config', $config, HOUR_IN_SECONDS);

        $this->scheduler->start( $query );

        return false;
    }


    /**
     * @return array|array[]
     */
    protected function get_title_actions()
    {
        return [
            [
                'link' => $this->admin_url( [ 'action' => 'add'  , 'type' => 'email' ] ),
                'action' => __( 'Schedule Email Broadcast', 'groundhogg' ),
                'target' => '_self',
            ],
            [
                'link' => $this->admin_url( [ 'action' => 'add'  , 'type' => 'sms' ] ),
                'action' => __( 'Schedule SMS Broadcast', 'groundhogg' ),
                'target' => '_self',
            ]
        ];
    }

    /**
     * Display the table
     */
    public function view()
    {
        $broadcasts_table = new Broadcasts_Table();

        $broadcasts_table->views(); ?>
        <form method="post" class="search-form wp-clearfix">
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text"
                       for="post-search-input"><?php _ex('Search Broadcasts', 'search', 'groundhogg'); ?>:&nbsp;</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button"
                       value="<?php _ex('Search Broadcasts', 'search', 'groundhogg'); ?>">
            </p>
            <?php $broadcasts_table->prepare_items();  ?>
            <?php $broadcasts_table->display(); ?>
        </form>

        <?php
    }

    /**
     * Display the scheduling page
     */
    public function add()
    {
        if (!current_user_can('schedule_broadcasts')) {
            $this->wp_die_no_access();
        }
        include dirname(__FILE__) . '/add.php';
    }

    /**
     * Display the reporting page
     */
    public function report()
    {
        if (!current_user_can('view_broadcasts')) {
           $this->wp_die_no_access();
        }
        include dirname(__FILE__) . '/report.php';
    }

}