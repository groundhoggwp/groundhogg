<?php

namespace Groundhogg\Admin\Tools;

/**
 * New Import
 *
 * upload page to add a new import
 *
 * @since       1.3
 * @subpackage  Admin/Imports
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'groundhogg/import/form/before' );

?>
    <div class="gh-tools-wrap">
        <p class="tools-help"><?php _e( 'If you have a .CSV file you can upload it here!', 'groundhogg' ); ?></p>
        <form method="post" enctype="multipart/form-data" class="gh-tools-box gh-panel">
			<?php wp_nonce_field(); ?>
            <div class="display-flex align-center flex-wrap">
                <input type="file" name="import_file" id="import_file" accept=".csv" style="width: 200px; margin-right: auto">
                <button class="gh-button primary" name="import_file_button"
                        value="import"><?php _ex( 'Import Contacts', 'action', 'groundhogg' ); ?></button>
            </div>
        </form>

		<?php if ( current_user_can( 'view_previous_imports' ) ): ?>
            <p class="description" style="text-align: center"><a
                        href="<?php echo admin_url( 'admin.php?page=gh_tools&tab=import' ); ?>">&larr;&nbsp;<?php _e( 'Import from existing file.' ); ?></a>
            </p>
		<?php endif; ?>
    </div>
	<?php do_action( 'groundhogg/import/form/after' ); ?>
<?php


