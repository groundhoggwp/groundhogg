<?php
/**
 * Reports
 *
 * This class is a readonly format for easily access data of a customer.
 *
 * @package     groundhogg
 * @subpackage  Includes/Contacts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Report
{

    /**
     * @var $table string the table to query
     */
    public $table;

    /**
     * @var $where_args array|string array of where statements
     */
    public $where_args;

    /**
     * @var $and_or string AND or OR modifier
     */
    public $and_or;

    function __construct( $table, $where_args='', $and_or='AND' )
    {
        global $wpdb;

        $this->table = $wpdb->prefix . $table;
        $this->where_args = $where_args;
        $this->and_or = $and_or;
    }

    function count()
    {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if ( is_string( $this->where_args ) ){
            $sql .= " $this->where_args ";
        } else if ( is_array( $this->where_args ) ){
            $sql .= implode( " $this->and_or ", $this->where_args);
        }

        $sql = trim( $sql );

        return $wpdb->get_var( $sql );
    }

    function query()
    {
        global $wpdb;

        $sql = "SELECT * FROM {$this->table}";
        if ( is_string( $this->where_args ) ){
            $sql .= " $this->where_args ";
        } else if ( is_array( $this->where_args ) ){
            $sql .= implode( " $this->and_or ", $this->where_args);
        }

        $sql = trim( $sql );

        return $wpdb->get_results( $sql );
    }

}