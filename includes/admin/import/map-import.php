<?php
/**
 * Map Import
 *
 * map the fields to contact record fields.
 *
 * @package     Admin
 * @subpackage  Admin/Imports
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       1.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$file_name = urldecode( $_GET[ 'file_name' ] );
$file_name = wp_normalize_path( wpgh_get_csv_imports_dir( $file_name ) );

if ( ! file_exists( $file_name ) ){
    wp_die( 'The given file does not exist.' );
}

$items = wpgh_get_items_from_csv( $file_name );

$sample_item = array_shift( $items );

?>
<h2><?php _e( 'Map Contact Fields', 'groundhogg' ); ?></h2>
<table class="form-table">
    <tbody>
    <?php

    foreach ( $sample_item as $key => $value ):

    endforeach;

    ?>
    </tbody>
</table>