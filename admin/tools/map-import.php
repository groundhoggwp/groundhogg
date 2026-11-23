<?php

namespace Groundhogg\Admin\Tools;

use Groundhogg\Plugin;
use function Groundhogg\count_csv_rows;
use function Groundhogg\files;
use function Groundhogg\get_items_from_csv;
use function Groundhogg\get_key_from_column_label;
use function Groundhogg\get_mappable_fields;
use function Groundhogg\get_post_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;

/**
 * Map Import
 *
 * map the fields to contact record fields.
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

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- handled upstream
$file_name = basename( sanitize_file_name( wp_unslash( $_GET['import'] ?? '' ) ) );

if (
	! $file_name ||
	strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) ) !== 'csv'
) {
	wp_die( esc_html__( 'Invalid import file.', 'groundhogg' ) );
}

$file_path = files()->get_csv_imports_dir( $file_name );

if ( ! file_exists( $file_path ) ) {
	wp_die( esc_html__( 'The given file does not exist.', 'groundhogg' ) );
}

$total_items = count_csv_rows( $file_path );
$selected    = min( absint( get_url_var( 'preview_item' ) ), max( 0, $total_items - 1 ) );
$items       = get_items_from_csv( $file_path, 1, $selected );
$sample_item = $items[0] ?? [];

if ( empty( $sample_item ) ) {
	wp_die( esc_html__( 'This file has no rows to import. Check that it has a header row followed by one row per contact.', 'groundhogg' ) );
}

// When the map was sent back with an error, keep what was chosen
$posted_map = get_post_var( 'map' );
$posted_map = is_array( $posted_map ) ? map_deep( $posted_map, 'sanitize_text_field' ) : [];

function guess_column_map_to( $column ) {

	$column = get_key_from_column_label( $column );

	$guesses = [
		'address'        => 'street_address_1',
		'address1'       => 'street_address_1',
		'address_1'      => 'street_address_1',
		'address_line_1' => 'street_address_1',
		'street_address' => 'street_address_1',
		'address2'       => 'street_address_2',
		'address_2'      => 'street_address_2',
		'address_line_2' => 'street_address_2',
		'province'       => 'region',
		'state'          => 'region',
		'postal_code'    => 'postal_zip',
		'postal'         => 'postal_zip',
		'zip'            => 'postal_zip',
		'zip_code'       => 'postal_zip',
		'first'          => 'first_name',
		'firstname'      => 'first_name',
		'last'           => 'last_name',
		'lastname'       => 'last_name',
		'surname'        => 'last_name',
		'name'           => 'full_name',
		'email_address'  => 'email',
		'e_mail'         => 'email',
		'phone'          => 'primary_phone',
		'phone_number'   => 'primary_phone',
		'mobile'         => 'mobile_phone',
		'cell'           => 'mobile_phone',
		'company'        => 'company_name',
		'tag'            => 'tags',
	];

	if ( key_exists( $column, $guesses ) ) {
		return $guesses[ $column ];
	}

	static $fields;

	if ( ! isset( $fields ) ) {
		$fields = array_merge( ...array_values( get_mappable_fields() ) );
	}

	if ( key_exists( $column, $fields ) ) {
		return $column;
	}

	// match a field's label, e.g. "Mobile Phone Number"
	foreach ( $fields as $key => $label ) {
		if ( get_key_from_column_label( $label ) === $column ) {
			return $key;
		}
	}

	return '';
}

$base_admin_url = add_query_arg( [
	'page'   => 'gh_tools',
	'tab'    => 'import',
	'action' => 'map',
	'import' => rawurlencode( $file_name ),
], admin_url( 'admin.php' ) );

/**
 * One option row: a title and description on the left, the control on the right
 *
 * @param string $id          the control's id, so the title labels it
 * @param string $title
 * @param string $description
 * @param string $control     HTML
 */
$option_row = function ( $id, $title, $description, $control ) {
	?>
    <div class="gh-import-option">
        <div class="gh-import-option-text">
            <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></label>
            <p><?php echo esc_html( $description ); ?></p>
        </div>
        <div class="gh-import-option-control"><?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
			echo $control; ?></div>
    </div>
	<?php
};

?>
<div class="gh-importer gh-importer-wide">
	<?php $this->import_steps( 2 ); ?>
    <form method="post" class="gh-import-map">
		<?php wp_nonce_field(); ?>
        <input type="hidden" name="import" value="<?php echo esc_attr( $file_name ); ?>">

        <div class="gh-panel">
            <div class="gh-import-panel-head">
                <div>
                    <h2><?php esc_html_e( 'Match your columns', 'groundhogg' ); ?></h2>
                    <p class="gh-import-lead"><?php
						/* translators: %s: the file name */
						printf( esc_html__( 'Pick the contact field each column of %s goes into. Columns left on "Don\'t import" are skipped.', 'groundhogg' ), '<code>' . esc_html( $file_name ) . '</code>' ); ?></p>
                </div>
                <span class="gh-import-count"><?php
					/* translators: %s: the number of rows in the CSV */
					echo esc_html( sprintf( _n( '%s contact', '%s contacts', $total_items, 'groundhogg' ), number_format_i18n( $total_items ) ) ); ?></span>
            </div>

            <table class="gh-import-map-table">
                <thead>
                <tr>
                    <th class="col-column"><?php esc_html_e( 'Column', 'groundhogg' ); ?></th>
                    <th class="col-sample">
						<?php esc_html_e( 'Sample', 'groundhogg' ); ?>
                        <span class="gh-import-preview-nav">
                            <span><?php
	                            /* translators: 1: the row being previewed, 2: the number of rows */
	                            echo esc_html( sprintf( __( 'Row %1$s of %2$s', 'groundhogg' ), number_format_i18n( $selected + 1 ), number_format_i18n( $total_items ) ) ); ?></span>
							<?php if ( $selected > 0 ): ?>
                                <a href="<?php echo esc_url( add_query_arg( 'preview_item', $selected - 1, $base_admin_url ) ); ?>"
                                   aria-label="<?php esc_attr_e( 'Previous row', 'groundhogg' ); ?>"><span class="dashicons dashicons-arrow-left-alt2"></span></a>
							<?php endif; ?>
							<?php if ( $selected < $total_items - 1 ): ?>
                                <a href="<?php echo esc_url( add_query_arg( 'preview_item', $selected + 1, $base_admin_url ) ); ?>"
                                   aria-label="<?php esc_attr_e( 'Next row', 'groundhogg' ); ?>"><span class="dashicons dashicons-arrow-right-alt2"></span></a>
							<?php endif; ?>
                        </span>
                    </th>
                    <th class="col-field"><?php esc_html_e( 'Import as', 'groundhogg' ); ?></th>
                </tr>
                </thead>
                <tbody>
				<?php foreach ( $sample_item as $column => $value ):
					$value = trim( (string) $value );
					?>
                    <tr>
                        <td class="col-column" data-label="<?php esc_attr_e( 'Column', 'groundhogg' ); ?>">
                            <label for="<?php echo esc_attr( 'map_' . md5( $column ) ); ?>"><?php echo esc_html( $column ); ?></label>
                        </td>
                        <td class="col-sample" data-label="<?php esc_attr_e( 'Sample', 'groundhogg' ); ?>">
							<?php if ( $value === '' ): ?>
                                <span class="gh-import-empty"><?php esc_html_e( 'empty', 'groundhogg' ); ?></span>
							<?php else: ?>
                                <span class="gh-import-sample" title="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $value ); ?></span>
							<?php endif; ?>
                        </td>
                        <td class="col-field" data-label="<?php esc_attr_e( 'Import as', 'groundhogg' ); ?>"><?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
							echo html()->dropdown( [
								'name'        => sprintf( 'map[%s]', $column ),
								'id'          => 'map_' . md5( $column ),
								'selected'    => $posted_map[ $column ] ?? guess_column_map_to( $column ),
								'options'     => get_mappable_fields(),
								'option_none' => __( 'Don\'t import', 'groundhogg' ),
							] ); ?></td>
                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="gh-panel gh-import-options">
            <div class="gh-import-panel-head">
                <h2><?php esc_html_e( 'Options', 'groundhogg' ); ?></h2>
            </div>
			<?php

			$option_row(
				'tags',
				__( 'Add tags', 'groundhogg' ),
				__( 'Every imported contact also gets an "Import" tag with today\'s date, so you can find them later.', 'groundhogg' ),
				html()->tag_picker( [] )
			);

			$option_row(
				'email_is_confirmed',
				__( 'These contacts confirmed their email address', 'groundhogg' ),
				__( 'Turn this on if they double opted-in somewhere else. Leave it off if your CSV has an opt-in status column.', 'groundhogg' ),
				html()->toggleYesNo( [
					'name'    => 'email_is_confirmed',
					'id'      => 'email_is_confirmed',
					'checked' => (bool) get_post_var( 'email_is_confirmed' ),
				] )
			);

			if ( Plugin::$instance->preferences->is_gdpr_enabled() ) {

				$option_row(
					'data_processing_consent_given',
					__( 'These contacts gave data processing consent', 'groundhogg' ),
					__( 'Leave it off if your CSV has a data processing consent column.', 'groundhogg' ),
					html()->toggleYesNo( [
						'name'    => 'data_processing_consent_given',
						'id'      => 'data_processing_consent_given',
						'value'   => 'yes',
						'checked' => (bool) get_post_var( 'data_processing_consent_given' ),
					] )
				);

				$option_row(
					'marketing_consent_given',
					__( 'These contacts gave marketing consent', 'groundhogg' ),
					__( 'Leave it off if your CSV has a marketing consent column.', 'groundhogg' ),
					html()->toggleYesNo( [
						'name'    => 'marketing_consent_given',
						'id'      => 'marketing_consent_given',
						'value'   => 'yes',
						'checked' => (bool) get_post_var( 'marketing_consent_given' ),
					] )
				);
			}

			?>
        </div>

        <div class="gh-import-actions">
            <a class="gh-button secondary text"
               href="<?php echo esc_url( admin_url( 'admin.php?page=gh_tools&tab=import&action=add' ) ); ?>">&larr; <?php esc_html_e( 'Upload a different file', 'groundhogg' ); ?></a>
            <button type="submit" class="gh-button primary"><?php
				/* translators: %s: the number of contacts */
				echo esc_html( sprintf( _n( 'Import %s contact', 'Import %s contacts', $total_items, 'groundhogg' ), number_format_i18n( $total_items ) ) ); ?></button>
        </div>
    </form>
</div>
