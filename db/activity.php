<?php
namespace Groundhogg\DB;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Activity DB
 *
 * Stores information about a contact's site activity.
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Activity extends DB  {

    /**
     * Get the DB suffix
     *
     * @return string
     */
    public function get_db_suffix()
    {
        return 'gh_activity';
    }

    /**
     * Get the DB primary key
     *
     * @return string
     */
    public function get_primary_key()
    {
        return 'ID';
    }

    /**
     * Get the DB version
     *
     * @return mixed
     */
    public function get_db_version()
    {
        return '2.0';
    }

    /**
     * Listen for deletions for other objects since we don't want to hold clutter for previous things
     * to keep the DB small.
     */
    protected function add_additional_actions()
    {
        add_action( 'groundhogg/db/post_delete/contact', [ $this, 'contact_deleted' ] );
        add_action( 'groundhogg/db/post_delete/funnel',  [ $this, 'funnel_deleted' ] );
        add_action( 'groundhogg/db/post_delete/step',    [ $this, 'step_deleted' ] );
    }

    /**
     * Get the object type we're inserting/updateing/deleting.
     *
     * @return string
     */
    public function get_object_type()
    {
        return 'activity';
    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   2.1
     */
    public function get_columns() {
        return [
            'ID'            => '%d',
            'timestamp'     => '%d',
            'funnel_id'     => '%d',
            'step_id'       => '%d',
            'contact_id'    => '%d',
            'email_id'      => '%d',
            'event_id'      => '%d',
//            'event_type'    => '%d',
            'activity_type' => '%s',
            'referer'       => '%s',
        ];
    }

    /**
     * Get default column values
     *
     * @access  public
     * @since   2.1
     */
    public function get_column_defaults() {
        return array(
            'ID'            => 0,
            'timestamp'     => 0,
            'funnel_id'     => 0,
            'step_id'       => 0,
            'contact_id'    => 0,
            'email_id'      => 0,
            'event_id'      => 0,
//            'event_type'    => 0,
            'activity_type' => '',
            'referer'       => '',
        );
    }

    /**
     * Add a activity
     *
     * @access  public
     * @since   2.1
     */
    public function add( $data = array() ) {

        $args = wp_parse_args(
            $data,
            $this->get_column_defaults()
        );

        if( empty( $args['timestamp'] ) ) {
            return false;
        }

        return $this->insert( $args );
    }

    public function get_date_key() {
        return 'timestamp';
    }

    /**
     * Delete events for a contact that was just deleted...
     *
     * @param $id
     * @return false|int
     */
    public function contact_deleted( $id ){
        return $this->bulk_delete( [ 'contact_id' => $id ] );
    }

    /**
     * Delete events for a funnel that was just deleted...
     *
     * @param $id
     * @return false|int
     */
    public function funnel_deleted( $id ){
        return $this->bulk_delete( [ 'funnel_id' => $id ] );
    }

    /**
     * Delete events for a step that was just deleted...
     *
     * @param $id
     * @return false|int
     */
    public function step_deleted( $id ){
        return $this->bulk_delete( [ 'step_id' => $id ] );
    }

    /**
     * Create the table
     *
     * @access  public
     * @since   2.1
     */
    public function create_table() {

        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . $this->table_name . " (
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        timestamp bigint(20) unsigned NOT NULL,
        contact_id bigint(20) unsigned NOT NULL,
        funnel_id bigint(20) unsigned NOT NULL,
        step_id bigint(20) unsigned NOT NULL,
        activity_type VARCHAR(20) NOT NULL,
        email_id bigint(20) unsigned NOT NULL,
        event_id bigint(20) unsigned NOT NULL,
        referer text NOT NULL,
        PRIMARY KEY (ID),
        KEY timestamp (timestamp),
        KEY funnel_id (funnel_id),
        KEY step_id (step_id),
        KEY event_id (event_id)
		) $charset_collate;";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }
}