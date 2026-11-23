<?php

namespace Groundhogg\Admin\Tools;

/**
 * New Import
 *
 * upload page to add a new import
 *
 * @var $this Tools_Page
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
    <div class="gh-importer">
		<?php $this->import_steps( 1 ); ?>
        <form method="post" enctype="multipart/form-data" class="gh-panel gh-import-upload">
			<?php wp_nonce_field(); ?>
            <h2><?php esc_html_e( 'Upload a list of contacts', 'groundhogg' ); ?></h2>
            <p class="gh-import-lead"><?php esc_html_e( 'Choose a .csv file. You\'ll match its columns to contact fields next.', 'groundhogg' ); ?></p>

            <div class="gh-import-dropzone">
                <input type="file" name="import_file" id="import_file" accept=".csv,text/csv" required>
                <span class="dashicons dashicons-upload" aria-hidden="true"></span>
                <span class="gh-import-dropzone-title"><?php esc_html_e( 'Drop your CSV here', 'groundhogg' ); ?></span>
                <span class="gh-import-dropzone-hint"><?php esc_html_e( 'or click to browse', 'groundhogg' ); ?></span>
                <span class="gh-import-file-name" aria-live="polite"></span>
            </div>

            <ul class="gh-import-tips">
                <li><?php esc_html_e( 'The first row should be column headings, like Email, First Name, Last Name.', 'groundhogg' ); ?></li>
                <li><?php esc_html_e( 'Include an email address for each contact. Existing contacts are updated, not duplicated.', 'groundhogg' ); ?></li>
                <li><?php esc_html_e( 'Exporting from Excel or Google Sheets? Choose "CSV" or "CSV UTF-8".', 'groundhogg' ); ?></li>
            </ul>

            <div class="gh-import-actions">
				<?php if ( current_user_can( 'view_previous_imports' ) ): ?>
                    <a class="gh-button secondary text"
                       href="<?php echo esc_url( admin_url( 'admin.php?page=gh_tools&tab=import' ) ); ?>"><?php esc_html_e( 'Use a previous upload', 'groundhogg' ); ?></a>
				<?php endif; ?>
                <button class="gh-button primary" name="import_file_button" value="import"><?php echo esc_html_x( 'Upload and continue', 'action', 'groundhogg' ); ?>
                    &rarr;
                </button>
            </div>
        </form>
    </div>
<?php do_action( 'groundhogg/import/form/after' );
