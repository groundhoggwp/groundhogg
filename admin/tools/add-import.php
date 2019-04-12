<?php
/**
 * New Import
 *
 * upload page to add a new import
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

do_action( 'groundhogg/import/form/before' );

?>
<div class="show-upload-view">
    <div class="upload-plugin-wrap">
        <div class="upload-plugin">
            <p class="install-help"><?php _e( 'If you have a .CSV file you can upload it here!', 'groundhogg' ); ?></p>
            <form method="post" enctype="multipart/form-data" class="wp-upload-form">
                <?php wp_nonce_field(); ?>
                <input type="file" name="import_file" id="import_file" accept=".csv">
                <button class="button-primary" name="import_file_button" value="import"><?php _ex('Import Contacts', 'action', 'groundhogg'); ?></button>
            </form>
            <p class="description" style="text-align: center"><a href="<?php echo admin_url( 'admin.php?page=gh_tools&tab=import' ); ?>">&larr;&nbsp;<?php _e( 'Import from existing file.' ); ?></a></p>
        </div>
    </div>
</div>
<?php do_action('groundhogg/import/form/after' ); ?>
<?php


