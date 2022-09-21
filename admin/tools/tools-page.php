<?php

namespace Groundhogg\Admin\Tools;

use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Bulk_Jobs\Create_Users;
use Groundhogg\Bulk_Jobs\Delete_Contacts;
use Groundhogg\Properties;
use Groundhogg\Extension_Upgrader;
use Groundhogg\License_Manager;
use Groundhogg\Queue\Event_Queue;
use function Groundhogg\action_input;
use function Groundhogg\admin_page_url;
use function Groundhogg\export_header_pretty_name;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_exportable_fields;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\html;
use Groundhogg\Plugin;
use \WP_Error;
use function Groundhogg\install_gh_cron_file;
use function Groundhogg\is_groundhogg_network_active;
use function Groundhogg\is_option_enabled;
use function Groundhogg\isset_not_empty;
use function Groundhogg\key_to_words;
use function Groundhogg\nonce_url_no_amp;
use function Groundhogg\notices;
use function Groundhogg\uninstall_gh_cron_file;
use function Groundhogg\uninstall_groundhogg;
use function Groundhogg\validate_tags;
use function Groundhogg\white_labeled_name;
use function set_transient;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-01
 * Time: 3:19 PM
 */
class Tools_Page extends Tabbed_Admin_Page {

	protected $uploads_path = [];

	/**
	 * @var \Groundhogg\Bulk_Jobs\Import_Contacts
	 */
	public $importer;

	/**
	 * @var \Groundhogg\Bulk_Jobs\Export_Contacts
	 */
	public $exporter;

	/**
	 * @var Create_Users;
	 */
	public $create_users;

	/**
	 * Get the menu order between 1 - 99
	 *
	 * @return int
	 */
	public function get_priority() {
		return 98;
	}

	// Unused functions.

	public function view() {
	}

	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );
	}

	public function help() {
	}

	public function add_ajax_actions() {
	}

	protected function add_additional_actions() {
		$this->init_bulk_jobs();
	}

	public function init_bulk_jobs() {
		$this->importer     = Plugin::$instance->bulk_jobs->import_contacts;
		$this->exporter     = Plugin::$instance->bulk_jobs->export_contacts;
		$this->create_users = Plugin::$instance->bulk_jobs->create_users;
	}

	public function get_order() {
		return 98;
	}

	public function screen_options() {
	}

	protected function get_parent_slug() {
		return 'groundhogg';
	}

	public function get_slug() {
		return 'gh_tools';
	}

	public function get_name() {
		return __( 'Tools' );
	}

	public function get_cap() {
		return 'view_contacts';
	}

	public function get_item_type() {

		switch ( $this->get_current_tab() ) {
			default:
			case 'system':
			case 'delete':
				$type = 'tool';
				break;
			case 'import':
				$type = 'import';
				break;
			case 'export':
				$type = 'export';
				break;
		}

		return $type;
	}

	protected function get_title_actions() {

		$actions = [];

		if ( $this->get_current_tab() === 'import' ) {
			$actions[] = [
				'link'   => $this->admin_url( [ 'action' => 'add', 'tab' => 'import' ] ),
				'action' => __( 'Import New List', 'groundhogg' ),
			];
		}

		if ( $this->get_current_tab() === 'export' ) {
			$actions[] = [
				'link'   => $this->admin_url( [ 'action' => 'choose_columns', 'tab' => 'export' ] ),
				'action' => __( 'Export All Contacts', 'groundhogg' ),
			];
		}

		return apply_filters( 'groundhogg/admin/tools/title_action', $actions, $this );

	}

	protected function get_tabs() {
		$tabs = [
			[
				'name' => __( 'System Info & Debug' ),
				'slug' => 'system',
				'cap'  => 'manage_options'
			],
			[
				'name' => __( 'Import' ),
				'slug' => 'import',
				'cap'  => 'import_contacts'
			],
			[
				'name' => __( 'Export' ),
				'slug' => 'export',
				'cap'  => 'export_contacts'
			],
			[
				'name' => __( 'Cron Setup', 'groundhogg' ),
				'slug' => 'cron',
				'cap'  => 'manage_options'
			],
			[
				'name' => __( 'Misc', 'groundhogg' ),
				'slug' => 'misc',
				'cap'  => 'manage_options'
			],
		];

		// If old customer updating to new version.
//		if ( get_option( 'gh_updating_to_2_1' ) ) {
//			$tabs[] = [
//				'name' => __( 'Re-install Features' ),
//				'slug' => 'remote_install'
//			];
//		}

		return apply_filters( 'groundhogg/admin/tools/tabs', $tabs );
	}

	####### SYSTEM TAB FUNCTIONS #########

	/**
	 * Regular system view.
	 */
	public function system_view() {

		?>
        <p></p>
		<?php if ( get_url_var( 'show_sys_info' ) ): ?>
            <pre class="code" style="width: 100%;height:max-content;"
                 id="system-info-textarea"><?php echo groundhogg_tools_sysinfo_get(); ?></pre>
			<?php
			return;
		endif; ?>
		<?php if ( get_url_var( 'action' ) === 'view_updates' && ! get_request_var( 'confirm' ) ):
			do_action( 'groundhogg/admin/tools/updates', get_request_var( 'updater' ) );

			return;
		endif; ?>
		<?php if ( get_url_var( 'action' ) === 'view_updates' && get_request_var( 'confirm' ) === 'yes' ):

			?>
            <p><?php _e( '<b>WARNING:</b> Re-performing previous updates can cause unexpected issues and should be done with caution. We recommend you backup your site, or export your contact list before proceeding.', 'groundhogg' ); ?></p>
			<?php

			echo html()->e( 'a', [
				'class' => 'big-button button-primary',
				'href'  => add_query_arg( [
					'updater'             => sanitize_text_field( get_request_var( 'updater' ) ),
					'manual_update'       => sanitize_text_field( get_request_var( 'manual_update' ) ),
					'manual_update_nonce' => wp_create_nonce( 'gh_manual_update' ),
				], $_SERVER['REQUEST_URI'] )
			], sprintf( __( 'Yes, perform update %s', 'groundhogg' ), sanitize_text_field( get_request_var( 'manual_update' ) ) ) );

			return;
		endif; ?>
        <div class="post-box-grid">
			<?php do_action( 'groundhogg/admin/tools/system_status/before' ); ?>
            <div class="gh-panel">
                <div class="gh-panel-header">
                    <h2 class="hndle"><?php _e( 'Download System Info', 'groundhogg' ); ?></h2>
                </div>
                <div class="inside">
                    <p><?php _e( 'Download System Info when requesting support.', 'groundhogg' ); ?></p>
                    <a class="gh-button primary"
                       href="<?php echo admin_url( '?gh_download_sys_info=1' ) ?>"><?php _e( 'Download System Info', 'groundhogg' ); ?></a>
                    <a class="gh-button secondary"
                       href="<?php echo admin_page_url( 'gh_tools', [
						   'tab'           => 'system',
						   'show_sys_info' => 1
					   ] ) ?>"><?php _e( 'View System Info', 'groundhogg' ); ?></a>
                </div>
            </div>
            <div class="gh-panel">
                <div class="gh-panel-header">
                    <h2 class="hndle"><?php _e( 'Safe Mode', 'groundhogg' ); ?></h2>
                </div>
                <div class="inside">
                    <p><?php printf( __( 'Safe mode will disable any non %s related plugins for debugging purposes.', 'groundhogg' ), white_labeled_name() ); ?></p>
					<?php

					if ( ! is_option_enabled( 'gh_safe_mode_enabled' ) ):

						echo html()->e( 'a', [
							'href'  => nonce_url_no_amp( $this->admin_url( [ 'action' => 'enable_safe_mode' ] ), 'enable_safe_mode' ),
							'class' => [ 'gh-button secondary' ]
						], __( 'Enable Safe Mode' ) );

					else:

						echo html()->e( 'a', [
							'href'  => nonce_url_no_amp( $this->admin_url( [ 'action' => 'disable_safe_mode' ] ), 'disable_safe_mode' ),
							'class' => [ 'gh-button primary' ]
						], __( 'Disable Safe Mode' ) );

					endif;

					?>
                </div>
            </div>
            <div class="gh-panel">
                <div class="gh-panel-header">
                    <h2 class="hndle"><?php _e( 'Install Help', 'groundhogg' ); ?></h2>
                </div>
                <div class="inside">
                    <p><?php _e( 'In the event there were installation issues you can run the install process from here.', 'groundhogg' ); ?></p>
                    <form method="get">
						<?php html()->hidden_GET_inputs() ?>
						<?php wp_nonce_field( 'gh_manual_install', 'manual_install_nonce' ) ?>
                        <div class="gh-input-group">
							<?php echo html()->dropdown( [
								'name'        => 'manual_install',
								'options'     => apply_filters( 'groundhogg/admin/tools/install', [] ),
								'required'    => true,
								'option_none' => __( 'Select plugin to run install', 'groundhogg' )
							] );

							echo html()->submit( [
								'class' => 'gh-button primary',
								'text'  => __( 'Run installation', 'groundhogg' )
							] )
							?>
                        </div>
                    </form>
                </div>
            </div>
            <div class="gh-panel">
                <div class="gh-panel-header">
                    <h2 class="hndle"><?php _e( 'Previous Updates', 'groundhogg' ); ?></h2>
                </div>
                <div class="inside">
                    <p><?php _e( 'Run previous update paths in case of a failed update.', 'groundhogg' ); ?></p>
                    <form method="get">
						<?php html()->hidden_GET_inputs() ?>
						<?php action_input( 'view_updates' ) ?>
                        <div class="gh-input-group">
							<?php echo html()->dropdown( [
								'name'        => 'updater',
								'required'    => true,
								'options'     => apply_filters( 'groundhogg/admin/tools/updaters', [] ),
								'option_none' => __( 'Select plugin to view updates', 'groundhogg' )
							] );

							echo html()->submit( [
								'class' => 'gh-button primary',
								'text'  => __( 'View Updates' )
							] )

							?>
                        </div>
                    </form>
                </div>
            </div>
			<?php
			if ( is_multisite() && is_main_site() && is_groundhogg_network_active() ) : ?>
                <div class="gh-panel">
                    <div class="gh-panel-header">
                        <h2 class="hndle"><?php _e( 'Network Upgrades', 'groundhogg' ); ?></h2>
                    </div>
                    <div class="inside">
                        <p><?php _e( 'Process database upgrades network wide so they do not have to be done by each subsite owner.' ); ?></p>
						<?php

						do_action( 'groundhogg/admin/tools/network_updates' );

						?>
                    </div>
                </div>
			<?php endif; ?>
            <div class="gh-panel">
                <div class="gh-panel-header">
                    <h2 class="hndle"><span>⚠️ <?php _e( 'Reset', 'groundhogg' ); ?></span></h2>
                </div>
                <div class="inside">
                    <p><?php printf( __( 'Want to start from scratch? You can reset your %s installation to when you first installed it.', 'groundhogg' ), white_labeled_name() ); ?></p>
                    <p><?php _e( 'To confirm you want to reset, type <code>reset</code> into the text box below.', 'groundhogg' ); ?></p>
                    <form method="post">
						<?php wp_nonce_field( 'reset' ) ?>
						<?php action_input( 'reset' ) ?>
                        <div class="gh-input-group">
							<?php echo html()->input( [
								'class'       => 'input',
								'name'        => 'reset_confirmation',
								'placeholder' => 'reset',
								'required'    => true,
							] );


							echo html()->submit( [
								'class' => 'gh-button primary',
								'text'  => __( '⚠️ Reset', 'groundhogg' )
							] )
							?>
                        </div>
                    </form>
                    <p><?php _e( 'This cannot be undone.', 'groundhogg' ); ?></p>
                </div>
            </div>
			<?php do_action( 'groundhogg/admin/tools/system_status/after' ); ?>
        </div>
		<?php
	}

	/**
	 * Enable safe mode
	 */
	public function process_enable_safe_mode() {
		if ( groundhogg_enable_safe_mode() ) {
			$this->add_notice( 'safe_mode_enabled', __( 'Safe mode has been enabled.' ) );
		}
	}

	public function process_disable_safe_mode() {
		if ( groundhogg_disable_safe_mode() ) {
			$this->add_notice( 'safe_mode_disabled', __( 'Safe mode has been disabled.' ) );
		}
	}

	####### IMPORT TAB FUNCTIONS #########

	/**
	 * Imports tab view
	 */
	public function import_view() {

		if ( ! current_user_can( 'view_previous_imports' ) ) {
			$this->import_add();

			return;
		}

		if ( ! class_exists( 'WPGH_Imports_Table' ) ) {
			require_once __DIR__ . '/imports-table.php';
		}

		$table = new Imports_Table(); ?>
        <form method="post" class="search-form wp-clearfix">
			<?php $table->prepare_items(); ?>
			<?php $table->display(); ?>
        </form>
		<?php
	}

	/**
	 * Add new import view
	 */
	public function import_add() {
		include __DIR__ . '/add-import.php';
	}

	/**
	 * Map import view
	 */
	public function import_map() {
		include __DIR__ . '/map-import.php';
	}

	/**
	 * Process the import addition
	 *
	 * @return int|WP_Error
	 */
	public function process_import_add() {

		if ( ! current_user_can( 'import_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$file = get_array_var( $_FILES, 'import_file' );

		$validate = wp_check_filetype( $file['name'], [ 'csv' => 'text/csv' ] );

		if ( $validate['ext'] !== 'csv' || $validate['type'] !== 'text/csv' ) {
			return new WP_Error( 'invalid_csv', sprintf( 'Please upload a valid CSV. Expected mime type of <i>text/csv</i> but got <i>%s</i>', esc_html( $file['type'] ) ) );
		}

		$file_name = str_replace( '.csv', '', $file['name'] );
		$file_name .= '-' . current_time( 'mysql' ) . '.csv';

		$file['name'] = sanitize_file_name( $file_name );

		$result = $this->handle_file_upload( $file );

		if ( is_wp_error( $result ) ) {

			if ( is_multisite() ) {
				return new WP_Error( 'multisite_add_csv', 'Could not import because CSV is not an allowed file type on this subsite. Please add CSV to the list of allowed file types in the network settings.' );
			}

			return $result;
		}

		return wp_redirect( $this->admin_url( [
			'action' => 'map',
			'tab'    => 'import',
			'import' => urlencode( basename( $result['file'] ) ),
		] ) );

	}

	/**
	 * Upload a file to the Groundhogg file directory
	 *
	 * @param $file array
	 * @param $config
	 *
	 * @return array|bool|WP_Error
	 */
	private function handle_file_upload( $file ) {
		$upload_overrides = array( 'test_form' => false );

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/file.php' );
		}

		$this->set_uploads_path();

		add_filter( 'upload_dir', array( $this, 'files_upload_dir' ) );
		$mfile = wp_handle_upload( $file, $upload_overrides );
		remove_filter( 'upload_dir', array( $this, 'files_upload_dir' ) );

		if ( isset( $mfile['error'] ) ) {

			if ( empty( $mfile['error'] ) ) {
				$mfile['error'] = _x( 'Could not upload file.', 'error', 'groundhogg' );
			}

			return new WP_Error( 'BAD_UPLOAD', $mfile['error'] );
		}

		return $mfile;
	}

	/**
	 * Change the default upload directory
	 *
	 * @param $param
	 *
	 * @return mixed
	 */
	public function files_upload_dir( $param ) {
		$param['path']   = $this->uploads_path['path'];
		$param['url']    = $this->uploads_path['url'];
		$param['subdir'] = $this->uploads_path['subdir'];

		return $param;
	}

	/**
	 * Initialize the base upload path
	 */
	private function set_uploads_path() {
		$this->uploads_path['subdir'] = Plugin::$instance->utils->files->get_base_uploads_dir();
		$this->uploads_path['path']   = Plugin::$instance->utils->files->get_csv_imports_dir();
		$this->uploads_path['url']    = Plugin::$instance->utils->files->get_csv_imports_url();
	}

	/**
	 * map the import
	 */
	public function process_import_map() {
		if ( ! current_user_can( 'import_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$map = map_deep( get_post_var( 'map' ), 'sanitize_text_field' );

		if ( ! is_array( $map ) ) {
			wp_die( 'Invalid map provided.' );
		}

		$file_name = sanitize_file_name( get_post_var( 'import' ) );

		$tags = [ sprintf( '%s - %s', __( 'Import' ), date_i18n( 'Y-m-d H:i:s' ) ) ];

		if ( isset_not_empty( $_POST, 'tags' ) ) {
			$tags = array_merge( $tags, get_post_var( 'tags' ) );
		}

		$tags = validate_tags( $tags );

		set_transient( 'gh_import_tags', $tags, DAY_IN_SECONDS );
		set_transient( 'gh_import_map', $map, DAY_IN_SECONDS );
		set_transient( 'gh_import_compliance', [
			'is_confirmed'      => (bool) get_post_var( 'email_is_confirmed' ),
			'gdpr_consent'      => (bool) get_post_var( 'data_processing_consent_given' ),
			'marketing_consent' => (bool) get_post_var( 'marketing_consent_given' ),
		], DAY_IN_SECONDS );

		$this->importer->start( [ 'import' => $file_name ] );
	}

	/**
	 * @return int delete the files
	 */
	public function process_import_delete() {

		if ( ! current_user_can( 'delete_imports' ) ) {
			$this->wp_die_no_access();
		}

		$files = $this->get_items();

		foreach ( $files as $file_name ) {
			$filepath = Plugin::$instance->utils->files->get_csv_imports_dir( $file_name );
			if ( file_exists( $filepath ) ) {
				unlink( $filepath );
			}
		}

		$this->add_notice( 'file_removed', __( 'Imports deleted.', 'groundhogg' ) );

		return admin_url( 'admin.php?page=gh_tools&action=add&tab=import' );
	}

	####### EXPORT TAB FUNCTIONS #########

	/**
	 * Exports tab view
	 */
	public function export_view() {

		if ( ! current_user_can( 'view_previous_exports' ) ) {
			$this->export_choose_columns();

			return;
		}

		if ( ! class_exists( 'Exports_Table' ) ) {
			require_once __DIR__ . '/exports-table.php';
		}

		$table = new Exports_Table(); ?>
        <form method="post" class="wp-clearfix">
			<?php $table->prepare_items(); ?>
			<?php $table->display(); ?>
        </form>
		<?php
	}

	public function export_add() {
		// todo
	}

	/**
	 * Show the choose columns page to export.
	 */
	public function export_choose_columns() {

		if ( ! current_user_can( 'export_contacts' ) ) {
			$this->wp_die_no_access();
		}

		$query_args = get_request_var( 'query' );

		$count = get_db( 'contacts' )->count( $query_args );

		$default_exportable_fields = get_exportable_fields();
		$custom_properties         = array_map( function ( $f ) {
			return $f['name'];
		}, Properties::instance()->get_fields() );
		$meta_keys                 = array_diff( array_values( get_db( 'contactmeta' )->get_keys() ), array_keys( $default_exportable_fields ), $custom_properties );

		?>
        <p><?php _e( "Select which information you want to appear in your CSV file.", 'groundhogg' ); ?></p>

        <form method="post">
			<?php action_input( 'choose_columns', true, true ); ?>
            <h3><?php _e( 'Basic Contact Information', 'groundhogg' ) ?></h3>
			<?php

			html()->list_table( [
				'class' => 'export-table'
			], [
				[
					'class' => 'check-column',
					'name'  => "<input type='checkbox' value='1' checked class='select-all'>",
					'tag'   => 'td'
				],
				__( 'Pretty Name', 'groundhogg' ),
				__( 'Field ID', 'groundhogg' ),
			], map_deep( array_keys( $default_exportable_fields ), function ( $header ) use ( $default_exportable_fields ) {
				return [
					html()->checkbox( [
						'label'   => '',
						'type'    => 'checkbox',
						'name'    => 'headers[' . $header . ']',
						'id'      => 'header_' . $header,
						'class'   => 'basic header',
						'value'   => '1',
						'checked' => true,
					] ),
					$default_exportable_fields[ $header ],
					'<code>' . esc_html( $header ) . '</code>'
				];
			} ) );

			$tabs = Properties::instance()->get_tabs();

			foreach ( $tabs as $tab ):

				?><h2><?php esc_html_e( $tab['name'] ); ?></h2><?php

				$groups = Properties::instance()->get_groups( $tab['id'] );

				foreach ( $groups as $group ):
					?><h4><?php esc_html_e( $group['name'] ); ?></h4><?php

					html()->list_table( [
						'class' => 'export-table'
					], [
						[
							'class' => 'check-column',
							'name'  => "<input type='checkbox' value='1' class='select-all'>",
							'tag'   => 'td'
						],
						__( 'Pretty Name', 'groundhogg' ),
						__( 'Field ID', 'groundhogg' ),
					], array_map( function ( $field ) {
						return [
							html()->checkbox( [
								'label'   => '',
								'type'    => 'checkbox',
								'name'    => 'headers[' . $field['id'] . ']',
								'id'      => 'header_' . $field['name'],
								'class'   => 'meta header',
								'value'   => '1',
								'checked' => false,
							] ),
							esc_html( $field['label'] ),
							'<code>' . esc_html( $field['name'] ) . '</code>'
						];
					}, Properties::instance()->get_fields( $group['id'] ) ) );
				endforeach;

			endforeach;

			do_action( 'groundhogg/admin/tools/export' );

			?>

			<?php if ( ! empty( $meta_keys ) ): ?>

                <h3><?php _e( 'Custom Meta Information', 'groundhogg' ) ?></h3>
				<?php

				html()->list_table( [
					'class' => 'export-table'
				], [
					[
						'class' => 'check-column',
						'name'  => "<input type='checkbox' value='1' class='select-all'>",
						'tag'   => 'td'
					],
					__( 'Pretty Name', 'groundhogg' ),
					__( 'Field ID', 'groundhogg' ),
				], map_deep( $meta_keys, function ( $header ) {
					return [
						html()->checkbox( [
							'label'   => '',
							'type'    => 'checkbox',
							'name'    => 'headers[' . $header . ']',
							'id'      => 'header_' . $header,
							'class'   => 'meta header',
							'value'   => '1',
							'checked' => false,
						] ),
						key_to_words( $header ),
						'<code>' . esc_html( $header ) . '</code>'
					];
				} ) );

			endif;
			?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th><?php _e( 'Select the kind of column headers you want.', 'groundhogg' ) ?></th>
                    <td>
						<?php

						echo html()->dropdown( [
							'name'        => 'header_type',
							'options'     => [
								'basic'  => __( 'Field IDs' ),
								'pretty' => __( 'Pretty Names' ),
							],
							'option_none' => false
						] );

						echo html()->description( __( "Choose <b>Fields IDs</b> for <code>first_name</code> and <b>Pretty Names</b> for <code>First Name</code>.", 'groundhogg' ) )

						?>
                    </td>
                </tr>
                </tbody>
            </table>
			<?php submit_button( sprintf( _nx( 'Export %s contact', 'Export %s contacts', $count, 'action', 'groundhogg' ), number_format_i18n( $count ) ) ); ?>
        </form>
        <script>
          ( function ($) {

            $('.select-all').on('change', function (e) {

              let $checks = $(e.target).closest('table').find('input')

              if ($(this).is(':checked')) {
                $checks.prop('checked', true)
              }
              else {
                $checks.prop('checked', false)
              }
            })

          } )(jQuery)
        </script>
		<?php
	}

	/**
	 * When columns are chosen start the export process.
	 *
	 * @return WP_Error|bool|string
	 */
	public function process_choose_columns() {

		$headers = array_keys( get_post_var( 'headers' ) );
		$query   = get_request_var( 'query' );

		if ( empty( $headers ) ) {
			return new WP_Error( 'error', 'Please choose columns to export.' );
		}

		$header_type = sanitize_text_field( get_post_var( 'header_type', 'basic' ) );

		set_transient( 'gh_export_headers', $headers, DAY_IN_SECONDS );
		set_transient( 'gh_export_header_type', $header_type, DAY_IN_SECONDS );

		Plugin::$instance->bulk_jobs->export_contacts->start( [
			'query' => $query,
		] );

		return false;
	}

	/**
	 * @return int delete the files
	 */
	public function process_export_delete() {

		if ( ! current_user_can( 'delete_exports' ) ) {
			$this->wp_die_no_access();
		}

		$files = $this->get_items();

		foreach ( $files as $file_name ) {
			$filepath = Plugin::$instance->utils->files->get_csv_exports_dir( $file_name );
			if ( file_exists( $filepath ) ) {
				unlink( $filepath );
			}
		}

		$this->add_notice( 'file_removed', __( 'Exports deleted.', 'groundhogg' ) );

		return admin_url( 'admin.php?page=gh_tools&action=add&tab=export' );
	}

	####### UPDATES TAB FUNCTIONS #########

	/**
	 * Reset Groundhogg to when first installed.
	 */
	public function process_system_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		} else if ( get_post_var( 'reset_confirmation' ) !== 'reset' ) {
			return new WP_Error( 'error', __( 'You must confirm the reset by typing <code>reset</code> into the text field.', 'groundhogg' ) );
		}

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		uninstall_groundhogg();

		do_action( 'groundhogg/reset' );

		return admin_page_url( 'gh_guided_setup' );
	}

	########### ADVANCED SETUP ###########

	public function cron_view() {
		include __DIR__ . '/cron-setup.php';
	}

	/**
	 * Install the gh-cron.php file
	 *
	 * @return bool|\WP_Error
	 */
	public function process_cron_install_gh_cron() {

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		if ( ! install_gh_cron_file() ) {
			return new \WP_Error( 'error', __( 'Unable to install gh-cron.php file. Please install is manually.', 'groundhogg' ) );
		} else {
			$this->add_notice( 'success', __( 'Installed gh-cron.php successfully!', 'groundhogg' ) );
		}

		return false;
	}

	/**
	 * Uninstall the gh-cron.php file
	 *
	 * @return bool|\WP_Error
	 */
	public function process_cron_uninstall_gh_cron() {

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		if ( ! uninstall_gh_cron_file() ) {
			return new \WP_Error( 'error', __( 'Unable to uninstall gh-cron.php file. Please delete it manually via FTP.', 'groundhogg' ) );
		} else {
			$this->add_notice( 'success', __( 'Uninstalled gh-cron.php successfully!', 'groundhogg' ) );
		}

		return false;
	}

	/**
	 * Download the gh-cron.txt file.
	 *
	 * @return void
	 */
	public function process_cron_install_gh_cron_manually() {

		$gh_cron_php = file_get_contents( GROUNDHOGG_PATH . 'gh-cron.txt' );

		nocache_headers();

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="gh-cron.txt"' );

		echo $gh_cron_php;
		die();
	}

	/**
	 * Unschedule Groundhogg from WP Cron.
	 *
	 * @return bool|WP_Error
	 */
	public function process_cron_unschedule_gh_cron() {
		if ( wp_unschedule_hook( Event_Queue::WP_CRON_HOOK ) === false ) {
			return new \WP_Error( 'error', __( 'Something went wrong.', 'groundhogg' ) );
		} else {
			$this->add_notice( 'success', __( 'Unhooked Groundhogg from WP Cron.', 'groundhogg' ) );
		}

		return false;
	}

	########### OTHER ###########

	public function cron_setup_view() {

		?>
        <h2><?php _e( 'WP Cron Setup', 'groundhogg' ); ?></h2>
        <p><?php _e( 'Follow this guide to properly configure WP Cron.', 'groundhogg' ); ?></p>
        <h3><?php _e( '1. Disable built-in WP Cron', 'groundhogg' ); ?></h3>
        <p><?php _e( 'For the best performance you need to disable the built-in WP Cron. This will improve your overall WP performance while ensuring Groundhogg works properly.', 'groundhogg' ); ?></p>
        <form method="post">
			<?php
			action_input( 'disable_wp_cron' );
			wp_nonce_field( 'disable_wp_cron' );

			if ( ! defined( 'DISABLE_WP_CRON' ) ) {
				submit_button( __( 'Disable WP Cron', 'groundhogg' ) );
			} else {
				?>
                <p><b><?php _e( 'Built-in WP Cron is already disabled.', 'groundhogg' ); ?></b></p>
				<?php
			}

			?>
        </form>
        <h3><?php _e( '2. Create an external Cron Job.', 'groundhogg' ); ?></h3>
        <p><?php _e( 'You need to replace the built-in WP Cron with an external cron-job. Choose one of the following methods.', 'groundhogg' ); ?></p>
        <h4><?php _e( 'a) Use Cron-Job.org', 'groundhogg' ); ?></h4>
        <p><?php _e( 'Cron-Job.org is a free site you can use to quickly setup an external cron-job.', 'groundhogg' ); ?></p>
        <p><?php _e( 'Use the below URL as the request URL.', 'groundhogg' ); ?></p>
        <p><?php _e( '<b>Cron URL: </b>' );
			echo html()->input( [
				'readonly' => true,
				'onfocus'  => "this.select()",
				'value'    => site_url( 'wp-cron.php' )
			] ); ?></p>
        <a class="button button-secondary" target="_blank"
           href="https://help.groundhogg.io/article/49-add-an-external-cron-job-cron-job-org"><?php _e( 'Use Cron-Job.org' ); ?></a>
        <h4><?php _e( 'a) Use CPanel', 'groundhogg' ); ?></h4>
        <p><?php _e( 'If you have access to CPanel you may be able to use CPanels cron system to setup the cron-job.', 'groundhogg' ); ?></p>
        <p><?php _e( 'Use the below command.', 'groundhogg' ); ?></p>
        <p><?php _e( '<b>Command: </b>' );
			echo html()->input( [
				'readonly' => true,
				'onfocus'  => "this.select()",
				'value'    => sprintf( "/usr/bin/wget -q -O - %s >/dev/null 2>&1", site_url( 'wp-cron.php?doing_wp_cron=1' ) )
			] ); ?></p>
        <a class="button button-secondary" target="_blank"
           href="https://help.groundhogg.io/article/51-add-an-external-cron-job-cpanel"><?php _e( 'Use CPanel' ); ?></a>

		<?php

	}

	public function process_disable_wp_cron() {
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		update_option( 'gh_disable_wp_cron', true );

		$this->add_notice( 'success', __( 'WP Cron has been disabled!', 'groundhogg' ) );

		return false;
	}

	public function remote_install_view() {

		?>
        <form method="post">
        <h3><?php _e( 'Re-install features.' ); ?></h3>
        <p><?php _e( 'The following features have been removed from the Groundhogg core plugin in version 2.1 and have instead been added to separate premium extensions.' ); ?></p>
        <ol>
            <li><?php _e( 'Elementor integration' ); ?></li>
            <li><?php _e( 'SMS functionality' ); ?></li>
            <li><?php _e( 'Advanced email editor' ); ?></li>
            <li><?php _e( 'Advanced funnel steps' ); ?></li>
            <li><?php _e( 'Superlinks' ); ?></li>
        </ol>
        <p><?php _e( 'You can learn more about this change <i>(officially announced Oct 17th)</i> <a href="https://www.groundhogg.io/press/new-pricing-and-updates-planned-for-november-1st/" target="_blank">on our blog.</a>' ); ?></p>
        <p><?php _e( 'If you have an All Access Pass, <a href="https://www.groundhogg.io/pricing/" target="_blank">Premium Plan</a> or a <a href="https://www.groundhogg.io/grandfather-program/" target="_blank">Grandfather license</a> get you can enter it below to automatically install and activate the removed features.' ); ?></p>
		<?php

		action_input( 'remote_install_plugins' );
		wp_nonce_field( 'remote_install_plugins' );

		html()->start_form_table();

		html()->start_row();

		html()->th( __( 'License Key:' ) );

		html()->td( [
			html()->input( [ 'name' => 'license_key', 'value' => License_Manager::get_license() ] ),
			html()->description( implode( '', [
					html()->e( 'a', [
						'href'   => 'https://www.groundhogg.io/account/',
						'target' => '_blank'
					], __( 'Find my license key', 'groundhogg' ) ),
					' | ',
					html()->e( 'a', [
						'href'   => 'https://www.groundhogg.io/pricing/',
						'target' => '_blank'
					], __( 'Get a license key', 'groundhogg' ) ),
				] )
			)
		] );

		html()->end_row();

		html()->end_form_table();

		submit_button( __( 'Install extensions!' ) );

	}

	public function process_remote_install_plugins() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			$this->wp_die_no_access();
		}

		$downloads = [
			23538, // SMS
			22198, // Elementor
			22397  // Pro features
		];

		foreach ( $downloads as $download ) {
			$installed = Extension_Upgrader::remote_install( $download, sanitize_text_field( get_request_var( 'license_key' ) ) );

			if ( is_wp_error( $installed ) ) {
				return $installed;
			}

			if ( ! $installed ) {
				return new WP_Error( 'error', 'Could not remotely install plugin...' );
			}
		}

		$this->add_notice( 'installed', 'Installed extension successfully!' );

		notices()->dismiss_notice( 'features-removed-notice' );

		delete_option( 'gh_updating_to_2_1' );

		return false;
	}

	/**
	 * Misc view
	 */
	public function misc_view() {
		include __DIR__ . '/misc.php';
	}

}
