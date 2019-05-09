<?php
namespace Groundhogg\DB;

use function Groundhogg\isset_not_empty;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Superlinks DB
 *
 * Store and manipulate superlinks
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Superlinks extends DB  {

    protected function add_additional_actions()
    {
        add_filter( 'groundhogg/db/pre_insert/superlink', [ $this, 'serialize_tags' ] );
        add_filter( 'groundhogg/db/pre_update/superlink', [ $this, 'serialize_tags' ] );

        add_filter( 'groundhogg/db/get/superlink', [ $this, 'unserialize_tags' ] );
    }

    /**
     * Get the DB suffix
     *
     * @return string
     */
    public function get_db_suffix()
    {
        return 'gh_superlinks';
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
        return 'superlink';
    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   2.1
     */
    public function get_columns() {
        return array(
            'ID'            => '%d',
            'name'          => '%s',
            'target'        => '%s',
            'tags'          => '%s',
            'clicks'        => '%d',
        );
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
            'name'          => '',
            'target'        => '',
            'tags'          => '',
            'clicks'        => 0,
        );
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
        name mediumtext NOT NULL,
        target mediumtext NOT NULL,
        tags longtext NOT NULL,
        clicks bigint(20) NOT NULL,
        PRIMARY KEY  (ID)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }
}