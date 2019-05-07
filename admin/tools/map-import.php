<?php

namespace Groundhogg\Admin\Tools;

use Groundhogg\Plugin;

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

$file_name = urldecode( $_GET[ 'import' ] );
$file_path = Plugin::$instance->utils->files->get_csv_imports_dir( $file_name );

if ( ! file_exists( $file_path ) ){
    wp_die( 'The given file does not exist.' );
}

$items = wpgh_get_items_from_csv( $file_path ); //todo

$sample_item = array_shift( $items );

?>
<form method="post">
    <?php wp_nonce_field(); ?>
    <?php echo  Plugin::$instance->utils->html->input( [
        'type' => 'hidden',
        'name' => 'import',
        'value' => $file_name
    ] ); ?>
<h2><?php _e( 'Map Contact Fields', 'groundhogg' ); ?></h2>
    <p class="description"><?php _e( 'Map your CSV columns to the contact records fields below.', 'groundhogg'); ?></p>
<style>
    select {vertical-align: top !important;}
</style>
<table class="form-table">
    <thead>
    <tr>
        <th><?php _e( 'Column Label', 'groundhogg' ) ?></th>
        <td><b><?php _e( 'Example Data / Contact Record Field', 'groundhogg' ) ?></b></td>
    </tr>
    </thead>
    <tbody>
    <?php

    foreach ( $sample_item as $key => $value ):
        ?>
    <tr>
        <th><?php echo $key; ?></th>
        <td>
            <?php echo Plugin::$instance->utils->html->input( [
                'name' => 'no_submit',
                'id'   => 'no_submit',
                'value' => $value,
                'attributes' => 'readonly'
            ] );

            echo Plugin::$instance->utils->html->dropdown( [
                'name' => sprintf( 'map[%s]', $key ),
                'id'   => sprintf( 'map_%s', $key ),
                'selected' => get_key_from_column_label( $key ), //todo
                'options' => wpgh_get_mappable_fields(), //todo
                'option_none' => '* Do Not Map *'
            ] );
            ?>
        </td>
    </tr>
    <?php

    endforeach;

    ?>
    <tr>
        <th><?php _e( 'Add additional tags to this import' ) ?></th>
        <td><div style="max-width: 500px"><?php echo Plugin::$instance->utils->html->tag_picker( [] ); ?></div></td>
    </tr>
    </tbody>
</table>
    <?php submit_button( __( 'Import Contacts', 'groundhogg' ) )?>
</form>
