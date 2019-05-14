<?php
namespace Groundhogg\DB;

// Exit if accessed directly
use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Broadcasts DB
 *
 * Stores information about broadcasts
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Broadcasts extends DB  {

    protected function add_additional_actions()
    {
        add_filter( 'groundhogg/db/pre_insert/broadcast', [ $this, 'serialize_tags' ] );
        add_filter( 'groundhogg/db/pre_update/broadcast', [ $this, 'serialize_tags' ] );

        add_filter( 'groundhogg/db/get/broadcast', [ $this, 'unserialize_tags' ] );
    }

    /**
     * Get the DB suffix
     *
     * @return string
     */
    public function get_db_suffix()
    {
        return 'gh_broadcasts';
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
     * Get the object type we're inserting/updateing/deleting.
     *
     * @return string
     */
    public function get_object_type()
    {
        return 'broadcast';
    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   2.1
     */
    public function get_columns() {
        return [
            'ID'                => '%d',
            'object_id'         => '%d',
            'object_type'       => '%s',
            'scheduled_by'      => '%d',
            'send_time'         => '%d',
            'tags'              => '%s',
            'status'            => '%s',
            'date_scheduled'    => '%s',
        ];
    }

    /**
     * Get default column values
     *
     * @access  public
     * @since   2.1
     */
    public function get_column_defaults() {
        return [
            'ID'                => 0,
            'object_id'         => 0,
            'object_type'       => 'email',
            'scheduled_by'      => 0,
            'send_time'         => 0,
            'tags'              => '',
            'status'            => 'scheduled',
            'date_scheduled'    => current_time( 'mysql' ),
        ];
    }

    /**
     * Given a data set, if tags are present make sure the end up serialized
     *
     * @param array $data
     * @return array
     */
    public function serialize_tags( $data = [] )
    {
        if ( isset_not_empty( $data, 'tags' ) ){
            $data[ 'tags' ] = maybe_serialize( $data[ 'tags' ] );
        }

        return $data;
    }

    /**
     * Given a data set, if tags are present make sure they end up unserialized
     *
     * @param null $obj
     * @return null
     */
    public function unserialize_tags( $obj = null )
    {
        if ( is_object( $obj ) && isset( $obj->tags ) ){
            $obj->tags = maybe_unserialize( $obj->tags );
        }

        return $obj;
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

        $sql = "CREATE TABLE " . $this->table_name . " (
		ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        object_id bigint(20) unsigned NOT NULL,
        object_type VARCHAR(20) NOT NULL,
        scheduled_by bigint(20) unsigned NOT NULL,
        send_time bigint(20) unsigned NOT NULL,
        tags longtext NOT NULL,
        status VARCHAR(20) NOT NULL,
        date_scheduled datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (ID)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }
}