<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-27
 * Time: 11:01 AM
 */

class GH_Account
{

    /**
     * @var $access_token string this token will be a "password" to access Groundhogg's email API service.
     */
    var $access_token;

    /**
     * @var $user_name string this is going to be their Groundhogg user name which will allocate the email funds appropriately.
     */
    var $user_name;

    /**
     * @var $instance GH_Account this is a singleton class and thus the instance is set to itself.
     */
    static $instance;

    const SERVER = 'https://groundhogg.io/gh-api/';

    function __construct()
    {
        $this->access_token = get_option( 'gh_access_token', false );
        $this->user_name = get_option( 'gh_account_user_login', false );

        if ( isset( $_GET['page'] ) && $_GET['page'] === 'groundhogg' ){
            add_action( 'init', array( $this, 'connect' ) );
        }
    }

    function send( $url, $body )
    {
        if ( ! is_array( $body ) ){
            return new WP_Error( 'INVALID_BODY', __( 'The body of the HTTP POST must be an array', 'groundhogg' ) );
        }

        if ( ! $this->user_name || ! $this->access_token ){
            return new WP_Error( 'NO_ACCESS_TOKEN', __( 'You must first to connect to your Groundhogg account' , 'groundhogg' ) );
        }

        $body[ 'user' ] = $this->user_name;
        $body[ 'token' ] = $this->access_token;

        $response = wp_safe_remote_post( $url , array(
            'body' => $body
        ) );

        if ( is_wp_error( $response ) )
            return $response;

        $response = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $response['error_code'] ) ) {
            return new WP_Error( $response['error_code'], $response['error_msg'] );
        }

        return $response;
    }

    function post( $resource, $body )
    {
        return $this->send( self::SERVER . $resource, $body );
    }

    function put( $resource, $id, $body )
    {
        return $this->send( self::SERVER . $resource . '/' . $id, $body );
    }

    function get( $resource, $id, $body )
    {
        return $this->send( self::SERVER . $resource . '/' . $id, $body );
    }

    function delete( $resource, $id )
    {
        return $this->send( self::SERVER . $resource . '/' . $id, array() );
    }

    function connect()
    {
        if ( ! isset( $_REQUEST[ 'token' ] ) || ! isset( $_REQUEST['user_login'] ) )
            return;

        $token = urldecode( $_REQUEST[ 'token' ] );
        $user_login = urldecode( $_REQUEST['user_login'] );

        update_option( 'gh_access_token', $token );
        update_option( 'gh_account_user_login', $user_login );

        $this->access_token = $token;
        $this->user_name = $user_login;
    }

    function connect_button()
    {
        $callback = add_query_arg( array(
            'page' => 'groundhogg',
            'tab' => 'emails'
        ), admin_url( 'admin.php' ) );

        $destination = add_query_arg( array(
            'redirect_to' => urlencode( $callback ),
            'doing_oauth' => 1
        ), 'https://www.groundhogg.io/wp-login.php' );

        ?>
        <h2>
            <?php _e( 'Connect Your Account', 'groundhogg' ); ?>
        </h2>
        <table class="form-table">
            <tr>
                <th>
                    <?php _e( 'Connect to Groundhogg.io', 'groundhogg' ) ?>
                </th>
                <td>
                    <p>
                        <a target="_blank" class="button button-primary" href="<?php echo $destination; ?>"><?php _e( 'Connect Your Account', 'groundhogg' ); ?></a>
                        <?php if ( $this->access_token ):  ?>
                            <?php _e( ' You are now connected.' , 'groundhogg' ); ?>
                        <?php endif; ?>
                    </p>
                    <p class="description"><?php _e( 'Connecting to your Groundhogg account allows you to easily install your extensions, send email, and manage your licenses all from your own site!', 'groundhogg'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
}

GH_Account::$instance = new GH_Account();

/**
 *
 *
 * @return GH_Account
 */
function wpgh_account()
{
    return GH_Account::$instance;
}