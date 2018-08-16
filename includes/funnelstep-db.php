<?php
/**
 * Email database functions
 *
 * Functions to manipulate and retrieve data from the database.
 *
 * @package     groundhoggteps
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Insert a new Funnel Step into the database
 *
 * @param $funnel_id int the ID of the funnel which the step belongs to
 * @param $title  string The new funnelstep Title
 * @param $status string The status of the funnelstep. 'active' or 'inactive'
 * @param $group  string The group type of the step. 'benckmark' or 'action'
 * @param $type   string The type of the of step. ex. 'send_email' or 'wc_product_purchased'
 * @param $order  int    the order of the funnel step. I.E. where it appears in relation to other steps in the UI.
 *
 * @return bool|int the ID of the new funnelstep or false
 */
function wpfn_insert_new_funnel_step( $funnel_id, $title, $status, $group, $type, $order )
{
    global $wpdb;

    if ( ! $title || ! is_string( $title ) || ! $funnel_id || ! is_int( $funnel_id ) )
        return false;

    $funnel_id = absint( $funnel_id );
    if ( ! $funnel_id )
        return false;

    $success = $wpdb->insert(
        $wpdb->prefix . WPFN_FUNNELSTEPS,
        array(
            'funnel_id'         => $funnel_id,
            'funnelstep_title'  => $title,
            'funnelstep_status' => $status,
            'funnelstep_group'  => $group,
            'funnelstep_type'   => $type,
            'funnelstep_order'  => $order,
            'date_created'  => current_time( 'mysql' )
        )
    );

    if ( $success ){
        return $wpdb->insert_id;
    } else {
        return false;
    }
}

/**
 * Delete a funnel step
 *
 * @param $id int The step ID to delete
 *
 * @return false|int the number of rows updated, false on failure
 */
function wpfn_delete_funnel_step( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    $table_name = $wpdb->prefix . WPFN_FUNNELSTEPS;

    $delete_step = $wpdb->delete( $table_name, array( 'ID' => $id ), array( '%d' ) );

    return $wpdb->delete( $wpdb->funnelstepmeta, array( 'ID' => $id ), array( '%d' ) ) && $delete_step;
}

/**
 * Get an funnelstep row via the ID of the funnelstep
 *
 * @param int $id Email Id
 *
 * @return object|false the Email, false on failure.
 */
function wpfn_get_funnel_step_by_id( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    $table_name = $wpdb->prefix . WPFN_FUNNELSTEPS;

    $sql_prep1 = $wpdb->prepare("SELECT * FROM $table_name WHERE ID = %d", $id);
    $funnelstep = $wpdb->get_row( $sql_prep1 );

    return $funnelstep;
}

/**
 * Get the steps of a funnel sorted by their order from smallest to largest.
 *
 * @param $funnel_id int the ID of the funnel
 *
 * @return array|false list of funnel steps, false on failure.
 */
function wpfn_get_funnel_steps_by_funnel_id( $funnel_id )
{
    global $wpdb;

    if ( ! $funnel_id || ! is_int( $funnel_id) )
        return false;

    $funnel_id = absint( $funnel_id );

    if ( ! $funnel_id )
        return false;

    $table_name = $wpdb->prefix . WPFN_FUNNELSTEPS;

    return $wpdb->get_results(
        $wpdb->prepare(
            "
         SELECT * FROM $table_name
		 WHERE funnel_id = %d
		 ORDER BY funnelstep_order ASC
		",
            $funnel_id
        ), ARRAY_A
    );
}

/**
 * Get the list of funnel steps following the given order.
 *
 * @param $funnel_id int the ID of the funnel
 * @param $order int the Order of a particular step
 * @return array|false list of steps, false on error
 */
function wpfn_get_funnel_steps_by_order( $funnel_id, $order )
{
    global $wpdb;

    if ( ! $funnel_id || ! is_int( $funnel_id) || ! $order || ! is_int( $order )  )
        return false;

    $funnel_id = absint( $funnel_id );
    $order = absint( $order );

    if ( ! $funnel_id )
        return false;

    $table_name = $wpdb->prefix . WPFN_FUNNELSTEPS;

    return $wpdb->get_results(
        $wpdb->prepare(
            "
         SELECT * FROM $table_name
		 WHERE funnel_id = %d AND funnelstep_order > %d
		 ORDER BY funnelstep_order ASC",
            $funnel_id, $order
        ), ARRAY_A
    );
}

/**
 * Get the steps available starting steps for a particular benchmark
 *
 * @param $step_type string the type of funnel step
 *
 * @return array|false list of funnel steps, false on failure.
 */
function wpfn_get_funnel_steps_by_type( $step_type )
{
    global $wpdb;

    if ( ! in_array( $step_type, wpfn_get_funnel_benchmark_icons() ) )
        return false;

    $table_name = $wpdb->prefix . WPFN_FUNNELSTEPS;

    return $wpdb->get_results(
        $wpdb->prepare(
            "
         SELECT * FROM $table_name
		 WHERE funnelstep_type = %s
		 ORDER BY funnelstep_order ASC
		",
            $step_type
        ), ARRAY_A
    );
}

/**
 * Update information about an funnelstep
 *
 * @param $id int Contact ID
 * @param $key string Column Name
 * @param $value string New Column Value
 *
 * @return false|int funnelstep ID in success, false on failure
 */
function wpfn_update_funnel_step( $id, $key, $value )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );

    if ( ! $id )
        return false;

    return $wpdb->update(
        $wpdb->prefix . WPFN_FUNNELSTEPS,
        array(
            $key => $value
        ),
        array( 'ID' => $id ),
        array(
            '%s'	// value1
        ),
        array( '%d' )
    );
}

/**
 * Adds meta data field to a funnelstep.
 *
 * @param int    $funnelstep_id    Contact ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value.
 * @param bool   $unique     Optional, default is false. Whether the same key should not be added.
 * @return int|false Meta ID on success, false on failure.
 */
function wpfn_add_step_meta($funnelstep_id, $meta_key, $meta_value, $unique = false) {
    return add_metadata('funnelstep', $funnelstep_id, $meta_key, $meta_value, $unique);
}

/**
 * Removes metadata matching criteria from a funnelstep.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @param int    $funnelstep_id    Contact ID
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Optional. Metadata value.
 * @return bool True on success, false on failure.
 */
function wpfn_delete_step_meta($funnelstep_id, $meta_key, $meta_value = '') {
    return delete_metadata('funnelstep', $funnelstep_id, $meta_key, $meta_value);
}

/**
 * Retrieve meta field for a funnelstep.
 *
 * @param int    $funnelstep_id Contact ID.
 * @param string $key     Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool   $single  Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
 */
function wpfn_get_step_meta( $funnelstep_id, $key = '', $single = true ) {
    return get_metadata('funnelstep', $funnelstep_id, $key, $single );
}

/**
 * Update funnelstep meta field based on funnelstep ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and funnelstep ID.
 *
 * If the meta field for the user does not exist, it will be added.
 *
 * @param int    $funnelstep_id   Contact ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value.
 * @param mixed  $prev_value Optional. Previous value to check before removing.
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function wpfn_update_step_meta($funnelstep_id, $meta_key, $meta_value, $prev_value = '') {
    return update_metadata('funnelstep', $funnelstep_id, $meta_key, $meta_value, $prev_value);
}

add_action( 'plugins_loaded', 'wpfn_integrate_funnelsteps_wpdb' );

/**
 * add support for the metadata API so I don't have to code it myself.
 */
function wpfn_integrate_funnelsteps_wpdb()
{
    global $wpdb;

    $wpdb->funnelstepmeta = $wpdb->prefix . 'funnelstepmeta';
    $wpdb->tables[] = 'funnelstepmeta';

    return;
}

define( 'WPFN_FUNNELSTEPS', 'funnelsteps' );
define( 'WPFN_FUNNELSTEPS_DB_VERSION', '0.1' );

/**
 * Create the funnelsteps database table.
 */
function wpfn_create_funnelsteps_db()
{

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . WPFN_FUNNELSTEPS;

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option('wpfn_funnelsteps_db_version'), WPFN_FUNNELSTEPS_DB_VERSION, '==' ) )
        return;

    $sql = "CREATE TABLE $table_name (
      ID bigint(20) NOT NULL AUTO_INCREMENT,
      funnel_id bigint(20) NOT NULL,
      funnelstep_title text NOT NULL,
      funnelstep_status varchar(20) NOT NULL,
      funnelstep_group varchar(20) NOT NULL,
      funnelstep_type varchar(20) NOT NULL,
      funnelstep_order int NOT NULL,
      date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      PRIMARY KEY  (ID)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    update_option( 'wpfn_funnelsteps_db_version', WPFN_FUNNELSTEPS_DB_VERSION );
}

define( 'WPFN_FUNNELSTEP_META', 'funnelstepmeta' );
define( 'WPFN_FUNNELSTEP_META_DB_VERSION', '0.2' );

function wpfn_create_funnelstep_meta_db()
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . WPFN_FUNNELSTEP_META;

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option('wpfn_funnelstep_meta_db_version'), WPFN_FUNNELSTEP_META_DB_VERSION, '==' ) )
        return;

    $max_index_length = 191;

    $install_query = "CREATE TABLE $table_name (
		meta_id bigint(20) unsigned NOT NULL auto_increment,
		funnelstep_id bigint(20) unsigned NOT NULL default '0',
		meta_key varchar(255) default NULL,
		meta_value longtext,
		PRIMARY KEY  (meta_id),
		KEY funnelstep (funnelstep_id),
		KEY meta_key (meta_key($max_index_length))
	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $install_query );

    update_option( 'wpfn_funnelstep_meta_db_version', WPFN_FUNNELSTEP_META_DB_VERSION );

}