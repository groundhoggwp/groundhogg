<?php


/**
 * Module Manager
 *
 * This class is a helper class for the settigns page. it essentially provides an api with Groundhogg.io for managing premium extension licenses.
 *
 * @package     Admin
 * @subpackage  Admin/Settings
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists('GH_EDD_SL_Plugin_Updater') ){
    require_once dirname(__FILE__) . '/updater/GH_EDD_SL_Plugin_Updater.php';
}

class WPGH_Extension_Manager
{
    static $extensions = array(); // array( item_id => array( license, status ) )
    static $storeUrl = "https://www.groundhogg.io";

	/**
     * Add an extension to the extensions options.
     *
	 * @param $item_id int
	 * @param $license string
	 * @param $status string
	 * @param $expiry string
	 *
	 * @return bool
	 */
    public static function add_extension( $item_id, $license, $status, $expiry )
    {
        if ( empty( static::$extensions ) )

            static::$extensions = wpgh_get_option( "gh_extensions", array() );

        static::$extensions[ $item_id ] = array(
            'license' => $license,
            'status' => $status,
	        'expiry' => $expiry
        );

        return update_option( "gh_extensions", static::$extensions );
    }

	/**
     * Remove an extension
	 * @param $item_id int
	 *
	 * @return bool
	 */
    public static function delete_extension( $item_id )
    {
	    if ( empty( static::$extensions ) ){
		    static::$extensions = wpgh_get_option( "gh_extensions", array() );
		    if ( empty( static::$extensions ) ){
		        return false;
            }
	    }

	    unset( static::$extensions[ $item_id ] );

	    return update_option( "gh_extensions", static::$extensions );
    }

    public static function maybe_check_for_updates()
    {
        $current = get_site_transient( 'update_plugins' );
        if ( isset( $current->last_checked ) && 12 * HOUR_IN_SECONDS > ( time() - $current->last_checked ) ) {
            return;
        }

        self::check_for_updates();
    }

    public static function check_for_updates()
    {
        $extensions = apply_filters( 'get_gh_extensions', array() );
        foreach ( $extensions as $extension_args ){
            if ( self::has_license( $extension_args['item_id'] ) && self::get_license_status( $extension_args['item_id']  ) !== 'invalid' ){
                $updater = new GH_EDD_SL_Plugin_Updater( WPGH_Extension_Manager::$storeUrl, $extension_args['file'], array(
                        'version' 	=> $extension_args['version'], 		// current version number
                        'license' 	=> trim( self::get_license( $extension_args['item_id'] ) ), 	// license key (used wpgh_get_option above to retrieve from DB)
                        'item_id'   => $extension_args['item_id'], 	// id of this product in EDD
                        'author' 	=> $extension_args['author'],  // author of this plugin
                        'url'       => home_url()
                    )
                );
            }
        }
    }

    public static function has_extensions()
    {
        if ( empty( static::$extensions ) )
            static::$extensions = wpgh_get_option( "gh_extensions", array() );

        return ! empty( static::$extensions );
    }

    public static function has_license( $item_id )
    {
	    if ( empty( static::$extensions ) )
		    static::$extensions = wpgh_get_option( "gh_extensions", array() );

        return isset( static::$extensions[$item_id]['license'] );
    }

    public static function get_license( $item_id )
    {
    	if ( empty( static::$extensions ) )
    		static::$extensions = wpgh_get_option( "gh_extensions", array() );

        return static::$extensions[$item_id]['license'];
    }

    public static function get_license_status( $item_id )
    {
	    if ( empty( static::$extensions ) )
		    static::$extensions = wpgh_get_option( "gh_extensions", array() );

	    if ( isset( static::$extensions[ $item_id ] ) ){
            return static::$extensions[ $item_id ][ 'status' ];
        }

        return false;

    }

    public static function update_license_status( $item_id, $status )
    {
        static::$extensions[ $item_id ][ 'status' ] = $status;
        return update_option( "gh_extensions", static::$extensions );
    }

	/**
	 * Activate a license
	 */
    public static function perform_activation()
    {
        if ( isset( $_POST['gh_activate_license'] ) ){

//            wp_die();

    		if ( ! current_user_can('manage_options' ) )
    			wp_die( "Cannot access this functionality" );

    		$licenses = $_POST[ 'licenses' ];

    		if ( ! is_array( $licenses ) ){
    		    wp_die( _x( 'Invalid license format', 'notice', 'groundhogg' ) );
            }

            foreach ( $licenses as $item_id => $license ){
                $license = trim( $license );
                $item_id = intval( trim( $item_id ) );

                if ( ! empty( $license ) && ! self::get_license_status( $license ) ){
                    self::activate_license( $license, $item_id );
                }

            }


	    }
    }

	/**
     * Deactivate a license
     *
	 * @return bool
	 */
    public static function perform_deactivation()
    {
	    if ( isset( $_REQUEST['action'] ) && $_REQUEST[ 'action' ] === 'deactivate' && isset( $_REQUEST[ 'item' ] )  ){

		    if ( ! current_user_can('manage_options' ) )
			    wp_die( "Cannot access this functionality" );

		    $item = intval( $_REQUEST[ 'item' ] );

		    return self::deactivate_license( $item );
	    }

	    return false;
    }

    public static function activate_license( $license, $item_id )
    {
        $api_params = array(
            'edd_action' => 'activate_license',
            'license'    => $license,
            'item_id'    => $item_id,// The ID of the item in EDD,
           // 'item_name'  => $item_name,
            'url'        => home_url(),
	        'beta'      => false
		);
		// Call the custom api.
		$response = wp_remote_post( static::$storeUrl, array( 'timeout' => 15, 'sslverify' => true, 'body' => $api_params ) );
		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            $message =  ( is_wp_error( $response ) && $response->get_error_message() ) ? $response->get_error_message() : __( 'An error occurred, please try again.' );
        } else {
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );
            if ( false === $license_data->success ) {
                switch( $license_data->error ) {
                    case 'expired' :
                        $message = sprintf(
                            _x( 'Your license key expired on %s.', 'notice', 'groundhogg' ),
                            date_i18n( wpgh_get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
                        );
                        break;
                    case 'revoked' :
                        $message = _x( 'Your license key has been disabled.', 'notice', 'groundhogg' );
                        break;
                    case 'missing' :
                        $message = _x( 'Invalid license.', 'notice', 'groundhogg' );
                        break;
                    case 'invalid' :
                    case 'site_inactive' :
                        $message = _x( 'Your license is not active for this URL.', 'notice', 'groundhogg' );
                        break;
                    case 'item_name_mismatch' :
                        $message = sprintf( _x( 'This appears to be an invalid license key', 'notice', 'groundhogg' ) );
                        break;
                    case 'no_activations_left':
                        $message = _x( 'Your license key has reached its activation limit.' , 'notice', 'groundhogg' );
                        break;
                    default :
                        $message = _x( 'An error occurred, please try again.', 'notice', 'groundhogg' );
                        break;
                }
            }
        }

		// Check if anything passed on a message constituting a failure
		if ( ! empty( $message ) ) {
            $status = 'invalid';
            $expiry = "unknown";

            WPGH()->notices->add( esc_attr( 'license_failed' ), __( $message ), 'error' );

        } else {
			$status = 'valid';
			$expiry = $license_data->expires;

            WPGH()->notices->add( esc_attr( 'license_activated' ), _x( 'License activated', 'notice', 'groundhogg' ), 'success' );

        }

//		wp_die( print_r( $license_data, true ) );

        self::add_extension( $item_id, $license, $status, $expiry );

		return $license_data->success;
    }

	/**
     * Deactivate a license
     *
	 * @param $license string
     * @return bool
	 */
    public static function deactivate_license( $item_id=0 )
    {

        $license = self::get_license( $item_id );

	    $api_params = array(
		    'edd_action' => 'deactivate_license',
		    'item_id'    => $item_id,
		    'license'    => $license,
		    'url'        => home_url(),
	    );
// Send the remote request
	    $response = wp_remote_post( self::$storeUrl, array( 'body' => $api_params, 'timeout' => 15, 'sslverify' => false ) );

	    if ( is_wp_error( $response ) ){
	        $success = false;
	        $message = _x( 'Something went wrong.', 'notice', 'groundhogg' );
        } else {

	        $response = json_decode( wp_remote_retrieve_body( $response ) );

	        if ( $response->success === false ){
		        $success = false;
		        $message = _x( 'Something went wrong.', 'notice', 'groundhogg' );
	        } else {
		        $success = true;
		        $message = _x( 'License deactivated.', 'notice', 'groundhogg' );
		        self::delete_extension( $item_id );
	        }

        }


        $type = $success ? 'success' : 'error';
	    WPGH()->notices->add( 'license_outcome', $message, $type );

	    return $success;
    }

    public static function verify_license( $item_id, $item_name, $license )
    {
        $api_params = array(
            'edd_action' => 'check_license',
            'license' => $license,
            'item_id' => $item_id,
            'url' => home_url()
        );

        $response = wp_remote_post( static::$storeUrl, array( 'body' => $api_params, 'timeout' => 15, 'sslverify' => true ) );

        if ( is_wp_error( $response ) ) {
            // return true in the event of an error. Check again later...
            return true;
        }

        $license_data = json_decode( wp_remote_retrieve_body( $response ) );

        if( isset( $license_data->license ) && $license_data->license == 'invalid' ) {
            self::update_license_status( $item_id, 'invalid' );
            return false;
        }

        return true;
    }

	public static function extension_page()
	{
        wp_enqueue_style( 'groundhogg-admin-extension' );

        //use a filter instead of the member variable so that it goes away when plugin is deactivated.
		$extensions = apply_filters( 'get_gh_extensions', array() );

		?>
            <div id="poststuff">
                <p><?php _e( 'Enter your extension license keys here to receive updates for purchased extensions. If your license key has expired, <a href="https://groundhogg.io/account/">please renew your license.</a>', 'groundhogg' ); ?></p>
                <?php
                if ( empty( $extensions ) ){
                    ?>
                    <p><?php _e( 'You have no extensions installed. Want some?', 'groundhogg' ); ?> <a href="https://groundhogg.io"><?php _e( 'Get your first extension!', 'groundhogg' ) ?></a></p>
                    <?php
                } else {
                    foreach ( $extensions as $extensionId => $args ){
                        echo new WPGH_Extension_Box( $extensionId, $args );
                    }
                }
                ?>
            </div>
        <?php
	}
}

add_action( 'gh_tab_extensions',    [ 'WPGH_Extension_Manager', 'extension_page' ] );
//add_action( 'load-plugins.php',     [ 'WPGH_Extension_Manager', 'check_for_updates' ] );
//add_action( 'load-update.php',      [ 'WPGH_Extension_Manager', 'check_for_updates' ] );
//add_action( 'load-update-core.php', [ 'WPGH_Extension_Manager', 'check_for_updates' ] );
//add_action( 'wp_update_plugins',    [ 'WPGH_Extension_Manager', 'check_for_updates' ] );
//add_action( 'admin_init',           [ 'WPGH_Extension_Manager', 'maybe_check_for_updates' ] );
add_action( 'admin_init',           [ 'WPGH_Extension_Manager', 'check_for_updates' ] );

class WPGH_Extension_Box
{
    var $item_id;
    var $item_name;
    var $img_source;
    var $description;

    function __construct( $item_id, $args )
    {
        $this->item_id = $item_id;
        $this->item_name = $args[ 'item_name' ];
        $this->img_source = $args[ 'img_source' ];
        $this->description = $args[ 'description' ];
    }

    public function license_exists()
    {
        return isset( WPGH_Extension_Manager::$extensions[ $this->item_id ] );
    }

    public function get_license()
    {
        return WPGH_Extension_Manager::$extensions[ $this->item_id ]['license'];
    }

    public function get_license_status()
    {
        return WPGH_Extension_Manager::$extensions[ $this->item_id ]['status'];
    }

    public function get_expiry()
    {
    	return isset( WPGH_Extension_Manager::$extensions[ $this->item_id ]['expiry'] )? WPGH_Extension_Manager::$extensions[ $this->item_id ]['expiry'] : _x( 'No data. See your account at groundhogg.io', 'notice', 'groundhogg' );
    }

    public function __toString()
    {
        //head container
        $content = "<div style='width: 400px;margin:10px;display: inline-block;vertical-align: top' class=\"postbox\">";
        //image
//        $content.= "<div class=\"gh-image-container\">";
//        $content.= "<img width='380' class=\"gh-extension-image\" src=\"{$this->img_source}\">";
//        $content.= "</div>";
        //description
        $content.= "<h2 class='hndle'>{$this->item_name}</h2>";
        $content.= "<div class=\"inside\">";

//        $content.= "<p>{$this->description}</p>";

        if ( $this->license_exists() ){
            $content.= "<input class='regular-text' type='text' style='margin-right: 10px;' placeholder='License' name='licenses[{$this->item_id}]' value='{$this->get_license()}'>";
	        $content.= "<p class='submit'><a class='button button-secondary' href='" . admin_url( 'admin.php?page=gh_settings&tab=extensions&action=deactivate&item=' . $this->item_id ) . "'>" . _x( "Deactivate Extension", 'action', 'groundhogg' ) . "</a></p>";
        } else {
	        $content.= "<input class='regular-text' type='text' style='margin-right: 10px;' placeholder='License' name='licenses[{$this->item_id}]'>";
	        $content.= "<p class='submit'><input type='submit' class='button button-primary' name='gh_activate_license' value='" . _x( "Activate Extension", 'action', 'groundhogg' ) . "'></p>";
        }

	    if ( $this->license_exists() ){
            $content .= "<p>";
            $content .= sprintf( __( "Your license expires on %s", 'groundhogg' ), $this->get_expiry() );
            $content .= "</p>";
        }

        $content.= "</div>";
        $content.= "</div>";
        return $content;
    }
}