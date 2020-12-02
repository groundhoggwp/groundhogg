<?php

namespace Groundhogg\Admin;

use Groundhogg\Plugin;
use Groundhogg\Settings;
use function Groundhogg\get_mappable_fields;
use function Groundhogg\get_url_var;
use function Groundhogg\groundhogg_logo;
use function Groundhogg\key_to_words;
use function Groundhogg\white_labeled_name;

class React_App {

	/**
	 * @var Settings
	 */
	protected $settings;

	public function __construct() {

	    add_action( 'admin_menu', [ $this, 'register_menu_item' ] );

		add_action( 'rest_api_init', [ $this, 'register_rest_settings' ] );

		add_action( 'admin_init', [ $this, 'maybe_redirect' ], 8 );
		add_action( 'init', [ $this, 'maybe_render' ] );

		add_filter( 'groundhogg/admin/react_init_obj', [ $this, 'register_nonces' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ $this, 'register_ajax_url' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ $this, 'register_rest_base' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ $this, 'register_assets' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ $this, 'register_userdata' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ $this, 'register_settings' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ $this, 'register_basename' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ $this, 'register_fields' ] );
	}


	/**
	 * Adds field_map argument in the Groundhogg Object also dumped some extra settings
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public function register_fields( $obj ) {

		$arr = get_mappable_fields();
		// removed the custom field as it returns array and brakes the map field
		$arr [ 'Custom Fields' ] = '';
		$obj[ 'field_map' ]      = $arr;
		$obj ['export_default_keys'] =   [
			'ID',
			'email',
			'first_name',
			'last_name',
			'user_id',
			'owner_id',
			'optin_status',
			'date_created',
			'tags'
		];
		$obj[ 'export_meta_keys'] =  array_values( Plugin::$instance->dbs->get_db( 'contactmeta' )->get_keys() );

		$obj[ 'user_roles' ] = Plugin::$instance->roles->get_roles_for_react_select();

		return $obj;
	}


	public function register_menu_item () {
		add_menu_page(
			white_labeled_name(),
			white_labeled_name(),
			'view_contacts',
			'groundhogg',
			function () {
				echo 'Whoops!';
			},
			'dashicons-email-alt',
			2
		);
	}

	public function register_rest_settings() {
		$this->settings = new Settings();
		$this->settings->init_defaults();
		$this->settings->register_settings();
	}

	public function maybe_redirect() {
		if ( get_url_var( 'page' ) === 'groundhogg' ) {
			wp_safe_redirect( admin_url( '/groundhogg' ) );
			die;
		}
	}

	public function enqueue_block_editor_assets() {
		wp_enqueue_style( 'wp-edit-post' );
		wp_enqueue_style( 'groundhogg-react-styles', GROUNDHOGG_URL . 'build/index.css' );
		do_action( 'groundhogg/scripts/after_enqueue_block_editor_assets' );
	}

	public function maybe_render() {

		if ( false === strpos( $_SERVER['REQUEST_URI'], 'wp-admin/groundhogg' ) ) {
			return;
		}

		if ( ! is_user_logged_in() ){
		    wp_redirect( wp_login_url( admin_url( 'groundhogg/' ) ) );
		    die;
        } if ( ! current_user_can( 'view_contacts' ) ) {
			wp_die( __( 'You do not have access to this platform.', 'groundhogg' ) );
		}

		$this->enqueue_block_editor_assets();

		Plugin::instance()->scripts->register_admin_scripts();
		Plugin::instance()->scripts->register_admin_styles();

		wp_localize_script( 'groundhogg-react', 'Groundhogg', apply_filters( 'groundhogg/admin/react_init_obj', [] ) );

		wp_enqueue_style( 'fa-icons', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css' );
		wp_enqueue_style( 'groundhogg-admin-react' );

		do_action( 'enqueue_groundhogg_assets_header' );

		ob_start();

		$this->edit_header();
		$this->edit_content();
		$this->edit_footer();
		exit;

	}

	/**
	 * Groundhogg header!
	 */
	protected function edit_header() {
		?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta name="viewport" content="width=device-width"/>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title><?php esc_html_e( 'Groundhogg', 'groundhogg' ); ?></title>
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'groundhogg_head' ); ?>
        </head>
        <body class="groundhogg groundhogg-funnel">
		<?php
	}

	/**
	 * Where react starts
	 */
	protected function edit_content() {
		?>
        <div id="root"></div>
		<?php
	}

	/**
	 * The Groundhogg footer
	 */
	protected function edit_footer() {
		$handles = apply_filters( 'groundhogg/admin/scripts', [] );
		$handles[] = 'groundhogg-react';
	?>
        </body>
		<?php do_action( 'groundhogg_footer' ); ?>
		<?php wp_print_scripts( $handles ) ?>
        </html>
		<?php
	}

	/**
	 * Register the nonces that will be required for accessing this
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public function register_nonces( $obj ) {

		$obj['nonces'] = [
			'_wpnonce'            => wp_create_nonce(),
			'_wprest'             => wp_create_nonce( 'wp_rest' ),
			'_adminajax'          => wp_create_nonce( 'admin_ajax' ),
			'_ajax_linking_nonce' => wp_create_nonce( 'internal-linking' ),
		];

		return $obj;
	}

	/**
	 * Make the rest API base accessible
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public function register_rest_base( $obj ) {

		$obj['rest_base'] = rest_url( 'gh/v4' );

		return $obj;
	}

	/**
	 * Make the rest API base accessible
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public function register_ajax_url( $obj ) {

		$obj['ajax_url'] = admin_url( 'admin-ajax.php' );

		return $obj;
	}

	/**
	 * Make the rest API base accessible
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public function register_assets( $obj ) {

		$obj['assets'] = [
			'bigG'      => GROUNDHOGG_ASSETS_URL . 'images/big-g.png',
			'logoBlack' => GROUNDHOGG_ASSETS_URL . 'images/logo-black-1000x182.png',
			'logoWhite' => GROUNDHOGG_ASSETS_URL . 'images/logo-white-1000x182.png',
			'welcome'   => [
				'import'    => GROUNDHOGG_ASSETS_URL . 'images/welcome/import-your-contact-list-with-groundhogg.png',
				'funnel'    => GROUNDHOGG_ASSETS_URL . 'images/welcome/create-your-first-funnel-with-groundhogg.png',
				'broadcast' => GROUNDHOGG_ASSETS_URL . 'images/welcome/send-your-first-broadcast-with-groundhogg.png',
				'cron'      => GROUNDHOGG_ASSETS_URL . 'images/welcome/correctly-configure-wp-cron-for-groundhogg.png',
				'course'      => GROUNDHOGG_ASSETS_URL . 'images/welcome/official-quickstart-course-for-groundhogg.png',
			],
		];

		return $obj;
	}

	/**
	 * Make the rest API base accessible
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public function register_basename( $obj ) {
		$settings = $obj['preloadSettings'];

		$settings['basename'] = path_join( wp_parse_url( admin_url(), PHP_URL_PATH ), 'groundhogg' );

		$obj['preloadSettings'] = $settings;

		return $obj;
	}

	/**
	 * Make the rest API base accessible
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public function register_userdata( $obj ) {

		$obj['user'] = wp_get_current_user();

		return $obj;
	}

	/**
	 * Make the settings accessible
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public function register_settings( $obj ) {
		$this->settings = new Settings();

		$this->settings->init_defaults();
		$this->settings->register_settings();

		$settings = wp_json_encode( $this->settings );
		$settings = json_decode( $settings, true );

		foreach ( $settings['settings'] as $name => $setting ) {
			$settings['settings'][ $name ]['defaultValue'] = (string) Plugin::instance()->settings->get_option( $setting['id'] );
		}

		$settings['allowedBlockTypes'] = apply_filters(
			'groundhogg/email_editor/allowed_block_types',
			[
				'groundhogg/paragraph',
				'groundhogg/spacer',
				'groundhogg/divider',
				'groundhogg/html',
				'groundhogg/button',
				'groundhogg/image',
				'groundhogg/heading',
			]
		);

		$obj['preloadSettings'] = $settings;

		return $obj;
	}

}