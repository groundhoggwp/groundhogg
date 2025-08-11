<?php

namespace Groundhogg\Admin\Tools;

use Exception;
use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\background\Export_Contacts_Last_Id;
use Groundhogg\background\Import_Contacts;
use Groundhogg\background\Sync_Users_Last_Id;
use Groundhogg\Background_Tasks;
use Groundhogg\Bulk_Jobs\Create_Users;
use Groundhogg\Bulk_Jobs\Export_Contacts;
use Groundhogg\Files;
use Groundhogg\Plugin;
use Groundhogg\Properties;
use Groundhogg\Queue\Event_Queue;
use WP_Error;
use function Groundhogg\action_input;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\code_it;
use function Groundhogg\count_csv_rows;
use function Groundhogg\enqueue_filter_assets;
use function Groundhogg\export_header_pretty_name;
use function Groundhogg\files;
use function Groundhogg\get_db;
use function Groundhogg\get_exportable_fields;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_query;
use function Groundhogg\get_request_uri;
use function Groundhogg\get_request_var;
use function Groundhogg\get_sanitized_FILE;
use function Groundhogg\get_url_var;
use function Groundhogg\gh_cron_installed;
use function Groundhogg\html;
use function Groundhogg\install_gh_cron_file;
use function Groundhogg\is_groundhogg_network_active;
use function Groundhogg\is_option_enabled;
use function Groundhogg\isset_not_empty;
use function Groundhogg\kses;
use function Groundhogg\nonce_url_no_amp;
use function Groundhogg\notices;
use function Groundhogg\safe_user_id_sync;
use function Groundhogg\uninstall_gh_cron_file;
use function Groundhogg\uninstall_groundhogg;
use function Groundhogg\utils;
use function Groundhogg\validate_tags;
use function Groundhogg\verify_admin_ajax_nonce;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-01
 * Time: 3:19 PM
 */
class Tools_Page extends Tabbed_Admin_Page {

	/**
	 * @var \Groundhogg\Bulk_Jobs\Import_Contacts
	 */
	public $importer;

	/**
	 * @var Export_Contacts
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
		return 105;
	}

	// Unused functions.

	public function view() {
	}

	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin-element' );

		if ( $this->get_current_tab() === 'cron' ) {
			wp_enqueue_script( 'groundhogg-admin-cron-jobs' );

			$wp_last_ping = absint( get_option( 'wp_cron_last_ping' ) );
			$gh_last_ping = absint( get_option( 'gh_cron_last_ping' ) );

			$data = [
				'DISABLE_WP_CRON'    => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
				'gh_disable_wp_cron' => is_option_enabled( 'gh_disable_wp_cron' ),
				'gh_cron_installed'  => gh_cron_installed(),
				'wp_last_ping_i18n'  => $wp_last_ping ? sprintf( '%s ago', human_time_diff( $wp_last_ping ) ) : 'Never',
				'wp_last_ping_diff'  => time() - $wp_last_ping,
				'gh_last_ping_i18n'  => $gh_last_ping ? sprintf( '%s ago', human_time_diff( $gh_last_ping ) ) : 'Never',
				'gh_last_ping_diff'  => time() - $gh_last_ping,
				'cron_download_url'  => action_url( 'install_gh_cron_manually' ),
				'promo_dismissed'    => notices()->is_dismissed( 'gh-promo-cron-job' )
			];

			wp_add_inline_script( 'groundhogg-admin-cron-jobs', 'const GroundhoggCron = ' . wp_json_encode( $data ), 'above' );
		}
		if ( $this->get_current_tab() === 'api' ) {
			enqueue_filter_assets();
			wp_enqueue_script( 'groundhogg-admin-api-docs' );
			$routes  = [
				'contacts',
				'tags',
				'notes',
				'tasks',
				'broadcasts',
				'emails',
				'funnels',
				'relationships',
				'activity',
				'reports',
			];
			$dot_min = is_option_enabled( 'gh_script_debug' ) ? '' : '.min';
			foreach ( $routes as $route ) {
				wp_enqueue_script( "groundhogg-admin-api-docs-$route", GROUNDHOGG_ASSETS_URL . "js/admin/api-docs/{$route}{$dot_min}.js", [ 'groundhogg-admin-api-docs' ], GROUNDHOGG_VERSION );
			}
			wp_enqueue_script( 'groundhogg-admin-filter-emails' );
			do_action( 'groundhogg/enqueue_api_docs' );
		}
	}

	public function help() {
	}

	public function add_ajax_actions() {
		add_action( 'wp_ajax_gh_install_cron', [ $this, 'ajax_install_gh_cron' ] );
		add_action( 'wp_ajax_gh_check_cron', [ $this, 'ajax_check_cron' ] );
		add_action( 'wp_ajax_gh_disable_internal_cron', [ $this, 'ajax_disable_internal_wp_cron' ] );
	}

	public function ajax_check_cron() {

		if ( ! verify_admin_ajax_nonce() || ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		$wp_last_ping = absint( get_option( 'wp_cron_last_ping' ) );
		$gh_last_ping = absint( get_option( 'gh_cron_last_ping' ) );

		$data = [
			'wp_last_ping_i18n' => $wp_last_ping ? sprintf( '%s ago', human_time_diff( $wp_last_ping ) ) : 'Never',
			'wp_last_ping_diff' => time() - $wp_last_ping,
			'gh_last_ping_i18n' => $gh_last_ping ? sprintf( '%s ago', human_time_diff( $gh_last_ping ) ) : 'Never',
			'gh_last_ping_diff' => time() - $gh_last_ping,
		];

		wp_send_json_success( $data );
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
		return esc_html__( 'Tools' , 'groundhogg' );
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
				'action' => esc_html__( 'Import New List', 'groundhogg' ),
			];
		}

		if ( $this->get_current_tab() === 'export' ) {
			$actions[] = [
				'link'   => $this->admin_url( [ 'action' => 'choose_columns', 'tab' => 'export' ] ),
				'action' => esc_html__( 'Export All Contacts', 'groundhogg' ),
			];
		}

		return apply_filters( 'groundhogg/admin/tools/title_action', $actions, $this );

	}

	protected function get_tabs() {
		$tabs = [
			[
				'name' => esc_html__( 'Tools', 'groundhogg' ),
				'slug' => 'misc',
				'cap'  => 'manage_options'
			],
			[
				'name' => esc_html__( 'System Info & Debug' , 'groundhogg' ),
				'slug' => 'system',
				'cap'  => 'manage_options'
			],
			[
				'name' => esc_html__( 'Import' , 'groundhogg' ),
				'slug' => 'import',
				'cap'  => 'import_contacts'
			],
			[
				'name' => esc_html__( 'Export' , 'groundhogg' ),
				'slug' => 'export',
				'cap'  => 'export_contacts'
			],
			[
				'name' => esc_html__( 'Cron Setup', 'groundhogg' ),
				'slug' => 'cron',
				'cap'  => 'manage_options'
			],
			[
				'name' => esc_html__( 'Rest API Playground', 'groundhogg' ),
				'slug' => 'api',
				'cap'  => 'manage_options'
			],
		];

		return apply_filters( 'groundhogg/admin/tools/tabs', $tabs );
	}

	public function page() {

		if ( $this->get_current_tab() === 'api' ) {
			$this->api_view();

			return;
		}

		parent::page();
	}

	/**
	 * View API
	 *
	 * @return void
	 */
	public function api_view() {

		?>
        <div id="api-docs"></div>
		<?php
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
                 id="system-info-textarea"><?php echo esc_html( groundhogg_tools_sysinfo_get() ); ?></pre>
			<?php
			return;
		endif; ?>
		<?php if ( get_url_var( 'action' ) === 'view_updates' && ! get_request_var( 'confirm' ) ):
			do_action( 'groundhogg/admin/tools/updates', get_request_var( 'updater' ) );

			return;
		endif; ?>
		<?php if ( get_url_var( 'action' ) === 'view_updates' && get_request_var( 'confirm' ) === 'yes' ): ?>
            <p><?php esc_html_e( '⚠️ Re-performing previous updates can cause unexpected issues and should be done with caution. We recommend you backup your site, or export your contact list before proceeding.', 'groundhogg' ); ?></p>
			<?php

			html( 'a', [
				'class' => 'big-button button-primary',
				'href'  => add_query_arg( [
					'updater'             => sanitize_text_field( get_request_var( 'updater' ) ),
					'manual_update'       => sanitize_text_field( get_request_var( 'manual_update' ) ),
					'manual_update_nonce' => wp_create_nonce( 'gh_manual_update' ),
				], get_request_uri() )
			], sprintf(
				/* translators: 1: version number to update to */
				esc_html__( 'Yes, perform update %s', 'groundhogg' ),
				sanitize_text_field( get_request_var( 'manual_update' ) )
			) );

			return;
		endif; ?>
        <div class="post-box-grid">
			<?php do_action( 'groundhogg/admin/tools/system_status/before' ); ?>
            <div class="gh-panel">
                <div class="gh-panel-header">
                    <h2 class="hndle"><?php esc_html_e( 'Download System Info', 'groundhogg' ); ?></h2>
                </div>
                <div class="inside">
                    <p><?php esc_html_e( 'Download System Info when requesting support.', 'groundhogg' ); ?></p>
                    <a class="gh-button primary"
                       href="<?php echo esc_url( admin_url( '?gh_download_sys_info=1' ) ) ?>"><?php esc_html_e( 'Download System Info', 'groundhogg' ); ?></a>
                    <a class="gh-button secondary"
                       href="<?php echo esc_url( admin_page_url( 'gh_tools', [
						   'tab'           => 'system',
						   'show_sys_info' => 1
					   ] ) ) ?>"><?php esc_html_e( 'View System Info', 'groundhogg' ); ?></a>
                </div>
            </div>
            <div class="gh-panel">
                <div class="gh-panel-header">
                    <h2 class="hndle"><?php esc_html_e( 'Safe Mode', 'groundhogg' ); ?></h2>
                </div>
                <div class="inside">
                    <p><?php
                        /* translators: 1: plugin/brand name */
                        echo esc_html( sprintf( __( 'Safe mode will temporarily disable any non %s related plugins for debugging purposes for your account only. Other users will not be impacted.', 'groundhogg' ), white_labeled_name() ) );
                        ?></p>
					<?php

					maybe_install_safe_mode_plugin();

					if ( ! groundhogg_is_safe_mode_enabled() ):

						html( 'a', [
							'href'  => nonce_url_no_amp( $this->admin_url( [ 'action' => 'enable_safe_mode' ] ), 'enable_safe_mode' ),
							'class' => 'gh-button danger text danger-confirm',
						], esc_html__( 'Enable Safe Mode', 'groundhogg' ) );

					else:

						html( 'a', [
							'href'  => nonce_url_no_amp( $this->admin_url( [ 'action' => 'disable_safe_mode' ] ), 'disable_safe_mode' ),
							'class' => [ 'gh-button primary' ]
						], esc_html__( 'Disable Safe Mode', 'groundhogg' ) );

					endif;

					?>
                </div>
            </div>
            <div class="gh-panel">
                <div class="gh-panel-header">
                    <h2 class="hndle"><?php esc_html_e( 'Install Help', 'groundhogg' ); ?></h2>
                </div>
                <div class="inside">
                    <p><?php esc_html_e( 'In the event there were installation issues you can run the install process from here.', 'groundhogg' ); ?></p>
                    <form method="get">
						<?php html()->hidden_GET_inputs() ?>
						<?php wp_nonce_field( 'gh_manual_install', 'manual_install_nonce' ) ?>
                        <div class="gh-input-group">
							<?php

                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                            echo html()->dropdown( [
								'name'        => 'manual_install',
	                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped downstream
								'options'     => apply_filters( 'groundhogg/admin/tools/install', [] ),
								'required'    => true,
								'option_none' => esc_html__( 'Select plugin to run install', 'groundhogg' )
							] );

							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
							echo html()->submit( [
								'class' => 'gh-button primary',
								'text'  => esc_html__( 'Run installation', 'groundhogg' )
							] )
							?>
                        </div>
                    </form>
                </div>
            </div>
            <div class="gh-panel">
                <div class="gh-panel-header">
                    <h2 class="hndle"><?php esc_html_e( 'Previous Updates', 'groundhogg' ); ?></h2>
                </div>
                <div class="inside">
                    <p><?php esc_html_e( 'Run previous update paths in case of a failed update.', 'groundhogg' ); ?></p>
                    <form method="get">
						<?php html()->hidden_GET_inputs() ?>
						<?php action_input( 'view_updates' ) ?>
                        <div class="gh-input-group">
							<?php

							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                            echo html()->dropdown( [
								'name'        => 'updater',
								'required'    => true,
	                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped downstream
								'options'     => apply_filters( 'groundhogg/admin/tools/updaters', [] ),
								'option_none' => esc_html__( 'Select plugin to view updates', 'groundhogg' )
							] );

							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
							echo html()->submit( [
								'class' => 'gh-button primary',
								'text'  => esc_html__( 'View Updates' , 'groundhogg' )
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
                        <h2 class="hndle"><?php esc_html_e( 'Network Upgrades', 'groundhogg' ); ?></h2>
                    </div>
                    <div class="inside">
                        <p><?php esc_html_e( 'Process database upgrades network wide so they do not have to be done by each subsite owner.', 'groundhogg' ); ?></p>
						<?php

						do_action( 'groundhogg/admin/tools/network_updates' );

						?>
                    </div>
                </div>
			<?php endif; ?>
			<?php if ( is_super_admin() ): ?>
                <div class="gh-panel">
                    <div class="gh-panel-header">
                        <h2 class="hndle"><span>⚠️ <?php esc_html_e( 'Reset', 'groundhogg' ); ?></span></h2>
                    </div>
                    <div class="inside">
                  						<p><?php
                                            /* translators: %s: plugin/brand name */
                                            echo esc_html( sprintf( __( 'Want to start from scratch? You can reset your %s installation to when you first installed it.', 'groundhogg' ), white_labeled_name() ) ); ?></p>
                  						<p><?php
                                            /* translators: %s: the literal confirmation word */
                                            printf( esc_html__('To confirm you want to reset, type %s into the text box below.', 'groundhogg' ),
	                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe HTML
                                                code_it( 'reset' ) );
                                            ?></p>
                        <form method="post" class="danger-permanent">
							<?php wp_nonce_field( 'reset' ) ?>
							<?php action_input( 'reset' ) ?>
                            <div class="gh-input-group">

								<?php
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                                echo html()->input( [
									'class'       => 'input',
									'name'        => 'reset_confirmation',
									'placeholder' => 'Type "reset" to confirm.',
									'required'    => true,
								] );

								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
								echo html()->submit( [
									'class' => 'gh-button danger',
									'text'  => esc_html__( '⚠️ Reset', 'groundhogg' )
								] )
								?>
                            </div>
                        </form>
                        <p><?php esc_html_e( 'This cannot be undone.', 'groundhogg' ); ?></p>
                    </div>
                </div>
			<?php endif; ?>
			<?php do_action( 'groundhogg/admin/tools/system_status/after' ); ?>
        </div>
		<?php
	}

	/**
	 * Enable safe mode
	 */
	public function process_enable_safe_mode() {

		maybe_install_safe_mode_plugin();

		if ( groundhogg_enable_safe_mode() ) {
			$this->add_notice( 'safe_mode_enabled', esc_html__( 'Safe mode has been enabled.' , 'groundhogg' ) );
		} else {
			$this->add_notice( new WP_Error( 'error', 'Could not enable safe mode due to a possible fatal error.' ) );
		}
	}

	public function process_disable_safe_mode() {

		maybe_install_safe_mode_plugin();

		if ( groundhogg_disable_safe_mode() ) {
			$this->add_notice( 'safe_mode_disabled', __( 'Safe mode has been disabled.' , 'groundhogg' ) );
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

		wp_enqueue_script( 'groundhogg-admin-big-file-upload' );
		wp_localize_script( 'groundhogg-admin-big-file-upload', 'BigFileUploader', [
			'location' => 'imports',
			'selector' => '#import_file'
		] );

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

		$file = get_sanitized_FILE( 'import_file' );

		$result = files()->safe_file_upload( $file, [
			'csv' => 'text/csv',
		], 'imports' );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return wp_safe_redirect( $this->admin_url( [
			'action' => 'map',
			'tab'    => 'import',
			'import' => urlencode( basename( $result['file'] ) ),
		] ) );

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

		$tags = [ sprintf( '%1$s - %2$s', __( 'Import' , 'groundhogg' ), date_i18n( 'Y-m-d H:i:s' ) ) ];

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce is handled upstream
		if ( isset_not_empty( $_POST, 'tags' ) ) {
			$tags = array_merge( $tags, get_post_var( 'tags' ) );
		}

		$tags = validate_tags( $tags );

		$result = Background_Tasks::add( new Import_Contacts( $file_name, [
			'is_confirmed'      => (bool) get_post_var( 'email_is_confirmed' ),
			'gdpr_consent'      => (bool) get_post_var( 'data_processing_consent_given' ),
			'marketing_consent' => (bool) get_post_var( 'marketing_consent_given' ),
			'field_map'         => $map,
			'tags'              => $tags,
		] ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $result === false ) {
			return new WP_Error( 'oops', 'Something went wrong.' );
		}

		$rows = count_csv_rows( files()->get_csv_imports_dir( $file_name ) );

		$time = human_time_diff( time(), time() + ( ceil( $rows / 1000 ) * MINUTE_IN_SECONDS ) );

			/* translators: 1: estimated time until import completes */
			$this->add_notice( 'success', sprintf( __( 'Your contacts are being imported in the background! <i>We\'re estimating it will take ~%s.</i> We\'ll let you know when it\'s done!', 'groundhogg' ), $time ) );

		return admin_page_url( 'gh_tools', [ 'tab' => 'import' ] );
	}

	/**
	 * @return int|WP_Error
	 */
	public function process_import_delete() {

		if ( ! current_user_can( 'delete_imports' ) ) {
			$this->wp_die_no_access();
		}

		$files = $this->get_items();

		foreach ( $files as $file_name ) {
			$filepath = files()->get_csv_imports_dir( sanitize_file_name( $file_name ) );

			if ( ! file_exists( $filepath ) || ! wp_delete_file( $filepath ) ) {
				return new WP_Error( 'failed', 'Unable to delete file.' );
			}
		}

		$this->add_notice( 'file_removed', __( 'Imports deleted.', 'groundhogg' ) );

		return false;
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
        <p><?php esc_html_e( "Select which information you want to appear in your CSV file.", 'groundhogg' ); ?></p>

        <form method="post">
			<?php action_input( 'choose_columns', true, true ); ?>

            <h3><?php esc_html_e( 'Name your export', 'groundhogg' ); ?></h3>

			<?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
            echo html()->input( [
				'name'        => 'file_name',
				'placeholder' => 'My export...',
				'required'    => true,
	            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped downstream
				'value'       => sanitize_file_name( sprintf( 'export-%s', current_time( 'Y-m-d' ) ) )
			] ); ?>

            <h3><?php esc_html_e( 'Basic Contact Information', 'groundhogg' ); ?></h3>
			<?php

			html()->export_columns_table( $default_exportable_fields );

			$tabs = Properties::instance()->get_tabs();

			foreach ( $tabs as $tab ):

				?><h2><?php echo esc_html( $tab['name'] ); ?></h2><?php

				$groups = Properties::instance()->get_groups( $tab['id'] );

				foreach ( $groups as $group ):
					?><h4><?php echo esc_html( $group['name'] ); ?></h4><?php

					$columns = [];
					$fields  = Properties::instance()->get_fields( $group['id'] );

					foreach ( $fields as $field ) {
						$columns[ $field['id'] ] = $field['label'];
					}

					html()->export_columns_table( $columns );
				endforeach;

			endforeach;

			do_action( 'groundhogg/admin/tools/export' );

			?>

			<?php if ( ! empty( $meta_keys ) ): ?>

                <h3><?php esc_html_e( 'Custom Meta Information', 'groundhogg' ); ?></h3>
				<?php

				html()->export_columns_table( array_combine( $meta_keys, array_map( '\Groundhogg\key_to_words', $meta_keys ) ) );

			endif;
			?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th><?php esc_html_e( 'Select the kind of column headers you want.', 'groundhogg' ); ?></th>
                    <td>
						<?php

						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
						echo html()->dropdown( [
							'name'        => 'header_type',
							'options'     => [
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped downstream
								'basic'  => __( 'Field IDs' , 'groundhogg' ),
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped downstream
								'pretty' => __( 'Pretty Names' , 'groundhogg' ),
							],
							'option_none' => false
						] );

						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses used
						echo html()->description( kses( __( "Choose <b>Fields IDs</b> for <code>first_name</code> and <b>Pretty Names</b> for <code>First Name</code>.", 'groundhogg' ), 'simple' ) )

						?>
                    </td>
                </tr>
                </tbody>
            </table>
			<?php
			/* translators: 1: number of contacts to export */
			submit_button( sprintf( _nx( 'Export %s contact', 'Export %s contacts', $count, 'action', 'groundhogg' ), number_format_i18n( $count ) ) );
			?>
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

		$columns = array_keys( get_post_var( 'headers', [] ) );
		$query   = get_request_var( 'query', [] );

		if ( empty( $columns ) ) {
			return new WP_Error( 'error', 'Please choose columns to export.' );
		}

		$header_type = sanitize_text_field( get_post_var( 'header_type', 'basic' ) );

		$headers = array_map( function ( $col ) use ( $header_type ) {
			return export_header_pretty_name( $col, $header_type );
		}, $columns );

		$file_name = sanitize_file_name( get_post_var( 'file_name' ) . '.csv' );
		$file_name = wp_unique_filename( files()->get_csv_exports_dir(), $file_name );
		$file_path = files()->get_csv_exports_dir( $file_name, true );

		// Add headers to the file first
		$pointer = fopen( $file_path, 'w' );
		fputcsv( $pointer, array_values( $headers ) );
		fclose( $pointer );

		Background_Tasks::add( new Export_Contacts_Last_Id( $query, $file_name, $columns ) );

		notices()->add_user_notice( __( 'We\'re exporting your contacts in the background. We\'ll let you know when it\'s ready for download.', 'groundhogg' ) );

		return admin_page_url( 'gh_contacts' );
	}

	/**
	 * @return int|WP_Error
	 */
	public function process_export_delete() {

		if ( ! current_user_can( 'delete_exports' ) ) {
			$this->wp_die_no_access();
		}

		$files = $this->get_items();

		foreach ( $files as $file_name ) {
			$filepath = files()->get_csv_exports_dir( sanitize_file_name( $file_name ) );
			if ( ! file_exists( $filepath ) || ! wp_delete_file( $filepath ) ) {
				return new WP_Error( 'failed', 'Unable to delete file.' );
			}
		}

		$this->add_notice( 'file_removed', __( 'Exports deleted.', 'groundhogg' ) );

		return false;
	}

	####### UPDATES TAB FUNCTIONS #########

	/**
	 * Reset Groundhogg to when first installed.
	 */
	public function process_system_reset() {

		if ( ! is_super_admin() ) {
			$this->wp_die_no_access();
		} else if ( get_post_var( 'reset_confirmation' ) !== 'reset' ) {
			return new WP_Error( 'error', __( 'You must confirm the reset by typing <code>reset</code> into the text field.', 'groundhogg' ) );
		}

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		uninstall_groundhogg();

		// Flush the object cache
		wp_cache_flush();

		do_action( 'groundhogg/reset' );

		return admin_page_url( 'gh_guided_setup' );
	}

	########### ADVANCED SETUP ###########

	public function cron_view() {
		?>
        <div id="cron-wrap"></div><?php
	}

	public function ajax_disable_internal_wp_cron() {
		if ( ! verify_admin_ajax_nonce() || ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		// disabled in wp-config.
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			wp_send_json_success();
		}

		// already disabled
		if ( is_option_enabled( 'gh_disable_wp_cron' ) ) {
			wp_send_json_success();
		}

		if ( ! update_option( 'gh_disable_wp_cron', true ) ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	public function ajax_install_gh_cron() {
		if ( ! verify_admin_ajax_nonce() || ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		if ( ! install_gh_cron_file() ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * Install the gh-cron.php file
	 *
	 * @return bool|WP_Error
	 */
	public function process_cron_install_gh_cron() {

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		if ( ! install_gh_cron_file() ) {
			return new WP_Error( 'error', __( 'Unable to install gh-cron.php file. Please install is manually.', 'groundhogg' ) );
		} else {
			$this->add_notice( 'success', __( 'Installed gh-cron.php successfully!', 'groundhogg' ) );
		}

		return false;
	}

	/**
	 * Uninstall the gh-cron.php file
	 *
	 * @return bool|WP_Error
	 */
	public function process_cron_uninstall_gh_cron() {

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		if ( ! uninstall_gh_cron_file() ) {
			return new WP_Error( 'error', __( 'Unable to uninstall gh-cron.php file. Please delete it manually via FTP.', 'groundhogg' ) );
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

		$gh_cron_php = files()->filesystem()->get_contents( GROUNDHOGG_PATH . 'gh-cron.txt' );

		nocache_headers();

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="gh-cron.txt"' );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file download
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
			return new WP_Error( 'error', __( 'Something went wrong.', 'groundhogg' ) );
		} else {
			$this->add_notice( 'success', __( 'Unhooked Groundhogg from WP Cron.', 'groundhogg' ) );
		}

		return false;
	}

	public function process_disable_wp_cron() {
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->wp_die_no_access();
		}

		update_option( 'gh_disable_wp_cron', true );

		$this->add_notice( 'success', __( 'WP Cron has been disabled!', 'groundhogg' ) );

		return false;
	}

	/**
	 * Download a file from the admin
	 *
	 * @return void
	 */
	public function process_download_file() {

		$short_path      = get_url_var( 'file_path' );
		$groundhogg_path = utils()->files->get_base_uploads_dir();
		$file_path       = wp_normalize_path( $groundhogg_path . DIRECTORY_SEPARATOR . $short_path );

		// guard against ../../ traversal attack
		if ( ! $file_path || ! file_exists( $file_path ) || ! is_file( $file_path ) || ! Files::is_file_within_directory( $file_path, $groundhogg_path ) ) {
			wp_die( 'The requested file was not found.', 'File not found.', [ 'status' => 404 ] );
		}

		$request = get_request_query();

		if ( ! current_user_can( 'download_file', $short_path, $request, $file_path ) ) {
			wp_die( 'You do not have permission to view this file.', 'Access denied.', [ 'status' => 403 ] );
		}

		$mime = wp_check_filetype( $file_path );
		$mime = $mime['type'];

		if ( ! $mime ) {
			wp_die( 'The request file type is unrecognized and has been blocked for your protection.', 'Access denied.', [ 'status' => 403 ] );
		}

		$content_type = sprintf( "Content-Type: %s", $mime );
		$content_size = sprintf( "Content-Length: %s", filesize( $file_path ) );

		header( $content_type );
		header( $content_size );

		if ( get_request_var( 'download' ) ) {
			$content_disposition = sprintf( "Content-disposition: attachment; filename=%s", basename( $file_path ) );
		} else {
			$content_disposition = sprintf( "Content-disposition: inline; filename=%s", basename( $file_path ) );
		}

		header( $content_disposition );

		status_header( 200 );
		nocache_headers();

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file contents
        echo files()->filesystem()->get_contents( $file_path );
		exit;
	}

	/**
	 * Misc view
	 */
	public function misc_view() {
		include __DIR__ . '/misc.php';
	}

	/**
	 * Re-sync user IDs
	 *
	 * @throws Exception
	 * @return bool
	 */
	public function process_re_sync_user_ids() {

		if ( ! current_user_can( 'edit_users' ) ) {
			$this->wp_die_no_access();
		}

		$updated = safe_user_id_sync();

		if ( $updated ) {
			$this->add_notice( 'success', 'User IDs have been synced.' );
		} else {
			$this->add_notice( 'failed', 'Re-sync failed.', 'error' );
		}

		return true;
	}

	public function process_sync_users() {
		if ( ! current_user_can( 'edit_users' ) ) {
			$this->wp_die_no_access();
		}

		$added = Background_Tasks::add( new Sync_Users_Last_Id() );

		if ( $added ) {
			notices()->add_user_notice( "Users are being synced in the background. It might take a few minutes!" );

			return admin_page_url( 'gh_tools', [ 'tab' => 'misc' ] );
		}

		return false;
	}

}
