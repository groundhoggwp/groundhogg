<?php

namespace Groundhogg\Admin;

use Groundhogg\Plugin;
use Groundhogg\Settings;
use function Groundhogg\get_mappable_fields;

class React_App {

	public static function context_filters() {

		add_filter( 'groundhogg/admin/react_init_obj', [ __CLASS__, 'register_nonces' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ __CLASS__, 'register_ajax_url' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ __CLASS__, 'register_rest_base' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ __CLASS__, 'register_assets' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ __CLASS__, 'register_userdata' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ __CLASS__, 'register_settings' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ __CLASS__, 'register_basename' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ __CLASS__, 'register_fields' ] );
		add_filter( 'groundhogg/admin/react_init_obj', [ __CLASS__, 'register_url_params' ] );
//		add_filter( 'groundhogg/admin/react_init_obj', [ __CLASS__, 'register_page_compat' ] );
//		add_filter( 'groundhogg/admin/react_init_obj', [ $this, 'register_replacement_codes' ] );
	}


	/**
	 * Adds field_map argument in the Groundhogg Object also dumped some extra settings
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public static function register_fields( $obj ) {

		$arr = get_mappable_fields();
		// removed the custom field as it returns array and brakes the map field
		$arr['Custom Fields']       = '';
		$obj['field_map']           = $arr;
		$obj['export_default_keys'] = [
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
		$obj['export_meta_keys']    = array_values( Plugin::$instance->dbs->get_db( 'contactmeta' )->get_keys() );
		$obj['user_roles']          = Plugin::$instance->roles->get_roles_for_react_select();

		return $obj;
	}

	/**
	 * This will determine which component should be used.
	 *
	 * @return mixed
	 */
	public static function register_url_params( $obj ) {

		$obj['params'] = urldecode_deep( $_GET );

		return $obj;
	}


	/**
	 * Register the nonces that will be required for accessing this
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public static function register_nonces( $obj ) {

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
	public static function register_rest_base( $obj ) {

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
	public static function register_ajax_url( $obj ) {

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
	public static function register_assets( $obj ) {

		$obj['assets'] = [
			'bigG'      => GROUNDHOGG_ASSETS_URL . 'images/big-g.png',
			'logoBlack' => GROUNDHOGG_ASSETS_URL . 'images/logo-black-1000x182.png',
			'logoWhite' => GROUNDHOGG_ASSETS_URL . 'images/logo-white-1000x182.png',
			'welcome'   => [
				'import'    => GROUNDHOGG_ASSETS_URL . 'images/welcome/import-your-contact-list-with-groundhogg.png',
				'funnel'    => GROUNDHOGG_ASSETS_URL . 'images/welcome/create-your-first-funnel-with-groundhogg.png',
				'broadcast' => GROUNDHOGG_ASSETS_URL . 'images/welcome/send-your-first-broadcast-with-groundhogg.png',
				'cron'      => GROUNDHOGG_ASSETS_URL . 'images/welcome/correctly-configure-wp-cron-for-groundhogg.png',
				'course'    => GROUNDHOGG_ASSETS_URL . 'images/welcome/official-quickstart-course-for-groundhogg.png',
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
	public static function register_basename( $obj ) {
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
	public static function register_userdata( $obj ) {

		$obj['user'] = wp_get_current_user();

		return $obj;
	}

	/**
	 * Add the replacement codes to the global object
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public static function register_replacement_codes( $obj ) {

		$obj['replacements'] = Plugin::instance()->replacements->get_replacements();

		return $obj;
	}

	/**
	 * Make the settings accessible
	 *
	 * @param $obj
	 *
	 * @return mixed
	 */
	public static function register_settings( $obj ) {
		$settings = new Settings();

		$settings->init_defaults();
		$settings->register_settings();

		$settings = wp_json_encode( $settings );
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

	/**
     * Add custom context to the React APP init object
     *
	 * @param $context
	 */
	public static function add_context( $context ) {

	    if ( did_action( 'enqueue_groundhogg_react_assets_scripts' ) ){
	        _doing_it_wrong( __CLASS__ . '::' . __METHOD__, 'This needs to be called before enqueuing the React APpp scripts.', GROUNDHOGG_VERSION );
        }

		add_filter( 'groundhogg/admin/react_init_obj', function ( $obj ) use ( $context ) {
			return array_merge( $obj, $context );
		} );
	}

	/**
	 * Adds additional actions.
	 */
	public static function required_actions() {
		add_action( 'screen_options_show_screen', '__return_false' );
		add_action( 'admin_enqueue_scripts', function () {
			wp_styles()->remove( [ 'forms', 'wp-admin' ] );
			wp_styles()->add( 'wp-admin', false, array(
				'dashicons',
				'common',
				'admin-menu',
				'dashboard',
				'list-tables',
				'edit',
				'revisions',
				'media',
				'themes',
				'about',
				'nav-menus',
				'widgets',
				'site-icon',
				'l10n'
			) );

		}, 999 );
		add_action( 'admin_head', function () {
			?>
            <style>
                .wrap {
                    margin: 0;
                    min-height: 300vh;
                }
                #wpcontent {
                    padding-left: 0 !important;
                }
            </style>
			<?php
		} );
	}

	/**
	 * Enqueue any scripts
	 */
	public static function scripts() {

	    self::context_filters();
//
		wp_enqueue_style( 'wp-edit-post' );
		wp_enqueue_style( 'groundhogg-react-styles', GROUNDHOGG_URL . 'build/index.css' );
//
		wp_enqueue_script( 'groundhogg-react' );
		wp_localize_script( 'groundhogg-react', 'Groundhogg', apply_filters( 'groundhogg/admin/react_init_obj', [] ) );

//		wp_enqueue_style( 'fa-icons', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css' );
		wp_dequeue_script( 'groundhogg-admin' );

		do_action( 'enqueue_groundhogg_react_assets_scripts' );
	}

	public static function app() {

		?>
        <div class="wrap">
            <div class="" id="gh-react-app-root"></div>
            <div class="wp-clearfix"></div>
        </div>
		<?php
	}
}