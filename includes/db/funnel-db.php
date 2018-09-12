<?php
/**
 * Email database functions
 *
 * Functions to manipulate and retrieve data from the database.
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

/**
 * Insert a new funnel into the database
 *
 * @param $title string The new funnel Title
 * @param $status string The status of the funnel. 'active' or 'inactive'
 *
 * @return bool|int the ID of the new funnel or the
 */
function wpgh_insert_new_funnel( $title, $status )
{
    global $wpdb;

    if ( ! $title || ! is_string( $title ) )
        return false;

    $success = $wpdb->insert(
        $wpdb->prefix . WPGH_FUNNELS,
        array(
            'funnel_title'  => $title,
            'funnel_status' => $status,
            'date_created'  => current_time( 'mysql' ),
            'last_updated'  => current_time( 'mysql' )
        )
    );

    if ( $success ){
        return $wpdb->insert_id;
    } else {
        return false;
    }
}

/**
 * Get an funnel row via the ID of the funnel
 *
 * @param int $id Email Id
 *
 * @return object|false the Email, false on failure.
 */
function wpgh_get_funnel_by_id( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    $table_name = $wpdb->prefix . WPGH_FUNNELS;

    $sql_prep1 = $wpdb->prepare("SELECT * FROM $table_name WHERE ID = %d", $id);
    $funnel = $wpdb->get_row( $sql_prep1 );

    return $funnel;
}

/**
 * Update information about an funnel
 *
 * @param $id int Contact ID
 * @param $key string Column Name
 * @param $value string New Column Value
 *
 * @return false|int funnel ID in success, false on failure
 */
function wpgh_update_funnel( $id, $key, $value )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    return $wpdb->update(
        $wpdb->prefix . WPGH_FUNNELS,
        array(
            $key => $value,
            'last_updated' => current_time( 'mysql' )
        ),
        array( 'ID' => $id ),
        array(
            '%s',	// value1
            '%s'
        ),
        array( '%d' )
    );
}

/**
 * Deletes a funnel, all related funnel meta, and all steps and their meta as well.
 *
 * @param $id int the ID of the funnel to delete
 * @return bool whether the funnel was delete successfully.
 */
function wpgh_delete_funnel( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    /* delete funnel meta */

    $a = $wpdb->delete(
      $wpdb->funnelmeta,
      array( 'funnel_id' => $id ),
      array( '%d' )
    );

    /* delete steps */

    $steps = wpgh_get_funnel_steps( $id );

    foreach ( $steps as $args ){
        $b = wpgh_delete_funnel_step( $args[ 'ID' ] );
    }

    $c = $wpdb->delete(
        $wpdb->prefix . WPGH_FUNNELS,
        array( 'ID' => $id ),
        array( '%d' )
    );

    /* delete funnel */
    return $a && $b && $c;
}

/**
 * Get the count of funnels for a specific column value
 *
 * @param $where string the column
 * @param $clause string the value
 * @return int the count of items
 */
function wpgh_count_funnel_items( $where='', $clause='' )
{
    global $wpdb;

    $table_name = $wpdb->prefix . WPGH_FUNNELS;

    if ( $where && $clause ){
        return intval( $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE $where LIKE %s", $clause ) ) );
    } else {
        return intval( $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE funnel_status LIKE %s OR funnel_status LIKE %s OR funnel_status LIKE %s", 'active', 'inactive', '' ) ) );
    }
}

/**
 * Adds meta data field to a funnel.
 *
 * @param int    $funnel_id    Contact ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value.
 * @param bool   $unique     Optional, default is false. Whether the same key should not be added.
 * @return int|false Meta ID on success, false on failure.
 */
function wpgh_add_funnel_meta($funnel_id, $meta_key, $meta_value, $unique = false) {
    return add_metadata('funnel', $funnel_id, $meta_key, $meta_value, $unique);
}

/**
 * Removes metadata matching criteria from a funnel.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @param int    $funnel_id    Contact ID
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Optional. Metadata value.
 * @return bool True on success, false on failure.
 */
function wpgh_delete_funnel_meta($funnel_id, $meta_key, $meta_value = '') {
    return delete_metadata('funnel', $funnel_id, $meta_key, $meta_value);
}

/**
 * Retrieve meta field for a funnel.
 *
 * @param int    $funnel_id Contact ID.
 * @param string $key     Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool   $single  Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
 */
function wpgh_get_funnel_meta( $funnel_id, $key = '', $single = true ) {
    return get_metadata('funnel', $funnel_id, $key, $single );
}

/**
 * Update funnel meta field based on funnel ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and funnel ID.
 *
 * If the meta field for the user does not exist, it will be added.
 *
 * @param int    $funnel_id   Contact ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value.
 * @param mixed  $prev_value Optional. Previous value to check before removing.
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function wpgh_update_funnel_meta($funnel_id, $meta_key, $meta_value, $prev_value = '') {
    return update_metadata('funnel', $funnel_id, $meta_key, $meta_value, $prev_value);
}

add_action( 'plugins_loaded', 'wpgh_integrate_funnels_wpdb' );

/**
 * add support for the metadata API so I don't have to code it myself.
 */
function wpgh_integrate_funnels_wpdb()
{
    global $wpdb;

    $wpdb->funnelmeta = $wpdb->prefix . 'funnelmeta';
    $wpdb->tables[] = 'funnelmeta';

    return;
}

define( 'WPGH_FUNNELS', 'funnels' );
define( 'WPGH_FUNNELS_DB_VERSION', '0.3' );

/**
 * Create the funnels database table.
 */
function wpgh_create_funnels_db()
{

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . WPGH_FUNNELS;

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option('wpgh_funnels_db_version'), WPGH_FUNNELS_DB_VERSION, '==' ) )
        return;

    $sql = "CREATE TABLE $table_name (
      ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      funnel_title text NOT NULL,
      funnel_status varchar(20) NOT NULL,
      last_updated datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      PRIMARY KEY  (ID)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );

    $wpdb->query( "ALTER TABLE $table_name AUTO_INCREMENT = 2" );

    update_option( 'wpgh_funnels_db_version', WPGH_FUNNELS_DB_VERSION );
}

define( 'WPGH_FUNNEL_META', 'funnelmeta' );
define( 'WPGH_FUNNEL_META_DB_VERSION', '0.2' );

function wpgh_create_funnel_meta_db()
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . WPGH_FUNNEL_META;

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option('wpgh_funnel_meta_db_version'), WPGH_FUNNEL_META_DB_VERSION, '==' ) )
        return;

    $max_index_length = 191;

    $install_query = "CREATE TABLE $table_name (
		meta_id bigint(20) unsigned NOT NULL auto_increment,
		funnel_id bigint(20) unsigned NOT NULL default '0',
		meta_key varchar(255) default NULL,
		meta_value longtext,
		PRIMARY KEY  (meta_id),
		KEY funnel (funnel_id),
		KEY meta_key (meta_key($max_index_length))
	) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $install_query );

    update_option( 'wpgh_funnel_meta_db_version', WPGH_FUNNEL_META_DB_VERSION );

}