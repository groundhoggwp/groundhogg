<?php

namespace Groundhogg\Admin\Tools;

use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use Groundhogg\Plugin;
use function Groundhogg\get_items_from_csv;
use function Groundhogg\get_key_from_column_label;
use function Groundhogg\get_mappable_fields;

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

$file_name = sanitize_file_name( urldecode( $_GET[ 'import' ] ) );
$file_path = Plugin::$instance->utils->files->get_csv_imports_dir( $file_name );

if ( ! file_exists( $file_path ) ){
    wp_die( 'The given file does not exist.' );
}

$items = get_items_from_csv( $file_path );

$selected = absint( get_url_var(  'preview_item' ) );

$total_items = count( $items );

$sample_item = $items[ $selected ]

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
    <div class="tablenav" style="max-width: 900px;">
        <div class="alignright">
            <?php

            $base_admin_url = add_query_arg( [
                'page' => 'gh_tools',
                'tab' => 'import',
                'action' => 'map',
                'import' => $file_name,
            ], admin_url( 'admin.php' ) );

            if ( $selected > 0 ){
                echo html()->e( 'a', [ 'href' => add_query_arg( 'preview_item', $selected - 1, $base_admin_url ), 'class' => 'button' ], __( '&larr; Prev' ) );
                echo '&nbsp;';
            }

            if ( $selected < $total_items - 1 ){
                echo html()->e( 'a', [ 'href' => add_query_arg( 'preview_item', $selected + 1, $base_admin_url ), 'class' => 'button' ], __( 'Next &rarr;' ) );
            }


            ?>
        </div>
    </div>
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
                'value' => $value,
                'readonly' => true,
//                'disabled' => true,
            ] );

            echo Plugin::$instance->utils->html->dropdown( [
                'name' => sprintf( 'map[%s]', $key ),
                'id'   => sprintf( 'map_%s', $key ),
                'selected' => get_key_from_column_label( $key ),
                'options' => get_mappable_fields(),
                'option_none' => '* Do Not Map *'
            ] );
            ?>
        </td>
    </tr>
    <?php

    endforeach;

    ?>
    <tr>
        <th><?php _e( 'Add additional tags to this import', 'groundhogg' ) ?></th>
        <td><div style="max-width: 500px"><?php echo Plugin::$instance->utils->html->tag_picker( [] ); ?></div></td>
    </tr>
    <tr>
        <th><?php _e( 'I have previously confirmed these email addresses.', 'groundhogg' ) ?></th>
        <td><?php echo html()->checkbox( [
                'label'         => __( 'Yes' ),
                'name'          => 'is_confirmed',
                'id'            => 'is_confirmed',
                'class'         => '',
                'value'         => '1',
                'checked'       => false,
                'title'         => 'I have confirmed.',
            ] ); ?></td>
    </tr>

    </tbody>
</table>
    <?php submit_button( __( 'Import Contacts', 'groundhogg' ) )?>
</form>
