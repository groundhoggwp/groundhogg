<?php
namespace Groundhogg\DB;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Steps DB
 *
 * store steps that belong to funnels
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Steps extends DB  {

    /**
     * The metadata type.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $meta_type = 'step';

    /**
     * Get the DB suffix
     *
     * @return string
     */
    public function get_db_suffix()
    {
        return 'gh_events';
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
        return 'step';
    }

    protected function add_additional_actions()
    {
        add_action( 'groundhogg/db/post_delete/funnel', [ $this, 'delete_steps' ] );
        parent::add_additional_actions();
    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   2.1
     */
    public function get_columns() {
        return array(
            'ID'             => '%d',
            'funnel_id'      => '%d',
            'step_title'     => '%s',
            'step_status'    => '%s',
            'step_type'      => '%s',
            'step_group'     => '%s',
            'step_order'     => '%d',
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
            'ID'             => 0,
            'funnel_id'      => 0,
            'step_title'     => __( 'New Step' ),
            'step_status'    => 'ready',
            'step_type'      => 'send_email',
            'step_group'     => 'action',
            'step_order'     => 0,
        );
    }

    /**
     * Add a step
     *
     * @access  public
     * @since   2.1
     */
    public function add( $data = array() ) {

        $args = wp_parse_args(
            $data,
            $this->get_column_defaults()
        );

        if( empty( $args['step_type'] ) ) {
            return false;
        }

        return $this->insert( $args );
    }

    /**
     * Delete steps when a funnel is deleted...
     *
     * @param bool|int $id Funnel ID
     * @return bool|false|int
     */
    public function delete_steps( $id = false )
    {
        if ( empty( $id ) ) {
            return false;
        }

        $steps = $this->get_steps( array( 'funnel_id' => $id ) );

        $result = 0;

        if ( $steps ){
            foreach ( $steps as $step ){
                $result = $this->delete( $step->ID );
            }
        }

        return $result;
    }

    /**
     * Retrieves the step by the ID.
     *
     * @param $id
     *
     * @return mixed
     */
    public function get_step( $id )
    {
        return $this->get_step_by( 'ID', $id );
    }

    /**
     * Retrieves a single step from the database
     *
     * @access public
     * @since  2.3
     * @param  string $field id or email
     * @param  mixed  $value  The Customer ID or email to search
     * @return mixed          Upon success, an object of the step. Upon failure, NULL
     */
    public function get_step_by( $field = 'ID', $value = 0 ) {

        if ( empty( $field ) || empty( $value ) ) {
            return NULL;
        }

        return parent::get_by( $field, $value );
    }


    /**
     * Retrieve steps from the database
     *
     * @access  public
     * @since   2.1
     */
    public function get_steps( $data = array(), $order = 'step_order' ) {

        global  $wpdb;

        if ( ! is_array( $data ) )
            return false;

	    $data = (array) $data;

	    $extra = '';

	    if ( isset( $data[ 'search' ] ) ){

		    $extra .= sprintf( " AND (%s)", $this->generate_search( $data[ 'search' ] ) );

	    }

        // Initialise column format array
        $column_formats = $this->get_columns();

        // Force fields to lower case
        $data = array_change_key_case( $data );

        // White list columns
        $data = array_intersect_key( $data, $column_formats );

        $where = $this->generate_where( $data );

        if ( empty( $where ) ){

            $where = "1=1";

        }

        $results = $wpdb->get_results( "SELECT * FROM $this->table_name WHERE $where $extra ORDER BY `$order` ASC" );

        return $results;
    }

    /**
     * Count the total number of steps in the database
     *
     * @access  public
     * @since   2.1
     */
    public function count( $args = array() ) {

        return count( $this->get_steps( $args ) );

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
		funnel_id bigint(20) unsigned NOT NULL,
		step_title mediumtext NOT NULL,
		step_type varchar(50) NOT NULL,
		step_group varchar(20) NOT NULL,
		step_status varchar(20) NOT NULL,
		step_order int unsigned NOT NULL,
		PRIMARY KEY  (ID)
		) {$this->get_charset_collate()};";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }
}