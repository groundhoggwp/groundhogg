<?php

namespace Groundhogg\Admin\Tools;

use Groundhogg\Preferences;
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

$file_name = sanitize_file_name( urldecode( $_GET['import'] ) );
$file_path = Plugin::$instance->utils->files->get_csv_imports_dir( $file_name );

if ( ! file_exists( $file_path ) ) {
	wp_die( 'The given file does not exist.' );
}

$items = get_items_from_csv( $file_path );

$selected = absint( get_url_var( 'preview_item' ) );

$total_items = count( $items );

$sample_item = $items[ $selected ];

?>
<form method="post">
	<?php wp_nonce_field(); ?>
	<?php echo html()->input( [
		'type'  => 'hidden',
		'name'  => 'import',
		'value' => $file_name
	] ); ?>
    <h2><?php _e( 'Map Contact Fields', 'groundhogg' ); ?></h2>
    <p class="description"><?php _e( 'Map your CSV columns to the contact records fields below.', 'groundhogg' ); ?></p>
    <style>
        select {
            vertical-align: top !important;
        }
    </style>
    <div class="import-table tablenav top">
        <div class="actions bulkactions alignleft">
			<?php

			$base_admin_url = add_query_arg( [
				'page'   => 'gh_tools',
				'tab'    => 'import',
				'action' => 'map',
				'import' => $file_name,
			], admin_url( 'admin.php' ) );

			if ( $selected > 0 ) {
				echo html()->e( 'a', [
					'href'  => add_query_arg( 'preview_item', $selected - 1, $base_admin_url ),
					'class' => 'button'
				], __( '&larr; Prev' ) );
				echo '&nbsp;';
			}

			if ( $selected < $total_items - 1 ) {
				echo html()->e( 'a', [
					'href'  => add_query_arg( 'preview_item', $selected + 1, $base_admin_url ),
					'class' => 'button'
				], __( 'Next &rarr;' ) );
			}

			?>
        </div>
        <div class="tablenav-pages one-page">
            <span class="displaying-num"><?php printf( _n( "%s contact", "%s contacts", $total_items, 'groundhogg' ), $total_items ); ?></span>
        </div>
    </div>
	<?php

	html()->list_table( [
		'id'    => 'import-contacts',
		'class' => 'import-table',
	], [
		__( 'Column Label', 'groundhogg' ),
		__( 'CSV Data', 'groundhogg' ),
		__( 'Map To Contact Field', 'groundhogg' )
	], array_map( function ( $key ) use ( $sample_item ) {
		return [
			"<b>" . $key . "</b>",
			html()->input( [
				'name'     => 'no_submit',
				'value'    => $sample_item[ $key ],
				'readonly' => true,
			] ),
			html()->dropdown( [
				'name'        => sprintf( 'map[%s]', $key ),
				'id'          => sprintf( 'map_%s', $key ),
				'selected'    => get_key_from_column_label( $key ),
				'options'     => get_mappable_fields(),
				'option_none' => '* Do Not Map *'
			] )
		];
	}, array_keys( $sample_item ) ) );

	?>
    <table class="form-table">
        <tbody>
        <tr>
            <th><?php _e( 'Add additional tags to this import', 'groundhogg' ) ?></th>
            <td>
                <div style="max-width: 500px"><?php echo html()->tag_picker( [] ); ?></div>
            </td>
        </tr>
        <tr>
            <th><?php _e( 'These contacts have previously confirmed their email address.', 'groundhogg' ) ?></th>
            <td><?php echo html()->checkbox( [
					'label'   => __( 'Yes, these contacts have confirmed their email address.', 'groundhogg' ),
					'name'    => 'email_is_confirmed',
					'id'      => 'email_is_confirmed',
					'class'   => '',
					'value'   => Preferences::CONFIRMED,
					'checked' => false,
					'title'   => 'I have confirmed.',
				] );

	            echo html()->description( __( "If you are importing <b>the optin status</b> per contact in your CSV leave this unchecked.", 'groundhogg' ) )

	            ?></td>
        </tr>
		<?php if ( Plugin::$instance->preferences->is_gdpr_enabled() ): ?>
            <tr>
                <th><?php _e( 'These contacts have previously given data processing consent.', 'groundhogg' ) ?></th>
                <td><?php echo html()->checkbox( [
						'label'   => __( 'Yes, these contacts have previously given consent.', 'groundhogg' ),
						'name'    => 'data_processing_consent_given',
						'id'      => 'data_processing_consent_given',
						'class'   => '',
						'value'   => 'yes',
						'checked' => false,
						'title'   => 'Consent Given.',
					] );

					echo html()->description( __( "If you are importing <b>data processing consent</b> per contact in your CSV leave this unchecked.", 'groundhogg' ) )

					?></td>
            </tr>
            <tr>
                <th><?php _e( 'These contacts have previously given marketing consent.', 'groundhogg' ) ?></th>
                <td><?php echo html()->checkbox( [
						'label'   => __( 'Yes, these contacts have previously given consent.', 'groundhogg' ),
						'name'    => 'marketing_consent_given',
						'id'      => 'marketing_consent_given',
						'class'   => '',
						'value'   => 'yes',
						'checked' => false,
						'title'   => 'Consent Given.',
					] );

					echo html()->description( __( "If you are importing <b>marketing consent</b> per contact in your CSV leave this unchecked.", 'groundhogg' ) )

					?></td>
            </tr>
		<?php endif; ?>
        </tbody>
    </table>
	<?php submit_button( __( 'Import Contacts', 'groundhogg' ) ) ?>
</form>
