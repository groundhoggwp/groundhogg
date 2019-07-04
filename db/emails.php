<?php

namespace Groundhogg\DB;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Email DB
 *
 * Store user emails
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Emails extends DB  {

    /**
     * The metadata type.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $meta_type = 'email';

    /**
     * The name of the cache group.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $cache_group = 'emails';

    /**
     * Get the DB suffix
     *
     * @return string
     */
    public function get_db_suffix()
    {
        return 'gh_emails';
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
        return '2.1';
    }

    /**
     * Get the object type we're inserting/updateing/deleting.
     *
     * @return string
     */
    public function get_object_type()
    {
        return 'email';
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
            'subject'       => '%s',
            'title'         => '%s',
            'pre_header'    => '%s',
            'content'       => '%s',
            'author'        => '%d',
            'from_user'     => '%d',
            'status'        => '%s',
            'is_template'   => '%d',
            'last_updated'  => '%s',
            'date_created'  => '%s',
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
            'ID'            => 0,
            'subject'       => '',
            'title'         => '',
            'pre_header'    => '',
            'content'       => '',
            'author'        => 0,
            'from_user'     => 0,
            'is_template'   => 0,
            'status'        => 'draft',
            'last_updated'  => current_time( 'mysql' ),
            'date_created'  => current_time( 'mysql' ),
        ];
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
        content longtext NOT NULL,
        subject text NOT NULL,
        title text NOT NULL,
        pre_header text NOT NULL,
        from_user bigint(20) unsigned NOT NULL,
        author bigint(20) unsigned NOT NULL,   
        is_template tinyint unsigned NOT NULL,   
        status VARCHAR(20) NOT NULL,
        last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (ID)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }
}