<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-21
 * Time: 11:34 AM
 */

/**
 * Get a superlink by it's id.
 *
 * @param $id int the superlink Id
 *
 * @return object|false the Email, false on failure.
 */
function wpgh_get_superlink_by_id( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    $table_name = $wpdb->prefix . WPGH_SUPER_LINKS;

    $sql_prep1 = $wpdb->prepare("SELECT * FROM $table_name WHERE ID = %d", $id);
    $link = $wpdb->get_row( $sql_prep1, ARRAY_A );

    /* Unserialize the array of tags... */
    $link['tags'] = maybe_unserialize( $link['tags'] );

    return $link;
}

/**
 * Insert a new superlink into the DB.
 *
 * @param $target string a link target for the super link
 * @param $tags array am array of tags to apply to a superlink
 *
 * @return false|int the superlink ID on success, false on failure.
 */
function wpgh_insert_new_superlink( $name, $target, $tags )
{
    global $wpdb;

    if ( ! $target || ! is_string( $target ) )
        return false;

    $target = wp_unslash( strtolower( $target ) );
    if ( ! $target )
        return false;

    $target = esc_url_raw( $target );

    $success = $wpdb->insert(
        $wpdb->prefix . WPGH_SUPER_LINKS,
        array(
            'name' => $name,
            'target' => $target,
            'tags' => maybe_serialize( $tags )
        )
    );

    if ( $success ){
        return $wpdb->insert_id;
    } else {
        return false;
    }
}

/**
 * Update information about a superlink
 *
 * @param $id int Superlink ID
 * @param $key string Column Name
 * @param $value string New Column Value
 *
 * @return false|int superlink ID in success, false on failure
 */
function wpgh_update_superlink( $id, $key, $value )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    do_action( 'wpgh_update_superlink_before', $id );

    return $wpdb->update(
        $wpdb->prefix . WPGH_SUPER_LINKS,
        array(
            $key => maybe_serialize( $value )
        ),
        array( 'ID' => $id ),
        array(
            '%s'	// value1
        ),
        array( '%d' )
    );
}

/**
 * Deletes the superlink from the db.
 *
 * @param $id int ID of the superlink
 * @return true
 */
function wpgh_delete_superlink( $id )
{
    global $wpdb;

    if ( ! $id || ! is_numeric( $id ) )
        return false;

    $id = absint( $id );
    if ( ! $id )
        return false;

    do_action( 'wpgh_delete_superlink_before', $id );

    //delete superlink from superlinks table
    $wpdb->delete(
        $wpdb->prefix . WPGH_SUPER_LINKS,
        array( 'ID' => $id ),
        array( '%d' )
    );

    do_action( 'wpgh_delete_superlink_after' );

    return true;

}

define( 'WPGH_SUPER_LINKS', 'superlinks' );
define( 'WPGH_SUPER_LINKS_DB_VERSION', '0.1' );

function wpgh_create_superlinks_db()
{

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . WPGH_SUPER_LINKS;

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name && version_compare( get_option('wpgh_superlinks_db_version'), WPGH_SUPER_LINKS_DB_VERSION, '==' ) )
        return;

    $sql = "CREATE TABLE $table_name (
      ID bigint(20) NOT NULL AUTO_INCREMENT,
      name VARCHAR(100) NOT NULL,
      target VARCHAR(100) NOT NULL,
      tags longtext NOT NULL,
      clicks bigint(20) NOT NULL,
      PRIMARY KEY  (ID)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta( $sql );

    update_option( 'wpgh_superlinks_db_version', WPGH_SUPER_LINKS_DB_VERSION );

}
