<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 10/29/2018
 * Time: 10:58 AM
 */
if ( ! defined( 'ABSPATH' ) ) exit;


class WPGH_API_V1
{

    /**
     * The user during an auth request.
     *
     * @var WP_User
     */
    protected $user;

    /**
     * This recognizes that their is an API call being made.
     *
     * WPGH_API_V1 constructor.
     */
    public function __construct()
    {

        /* REQUEST PROCESS */
        if ( strpos( $_SERVER[ 'REQUEST_URI' ], 'api/v1' ) !== false ){

            add_action( 'init', array( $this, 'process' ) );

        }

        /* LOGIN PROCESS */
        if ( isset( $_GET[ 'return_url' ] ) ){

            add_action( 'login_form', array( $this, 'login' ) );

        }

        if ( isset( $_POST[ 'return_url' ] ) ){

            add_action( 'wp_login', array( $this, 'generate_token' ), 10, 2 );

        }

    }

    public function login()
    {
        if ( isset( $_GET[ 'return_url' ] ) ){

            ?>

            <input type="hidden" name="return_url" value="<?php echo esc_url_raw( urldecode( $_GET[ 'return_url' ] ) ); ?>">

            <?php

        }
    }

    /**
     * @param $user_login string
     * @param $user WP_User
     */
    public function generate_token( $user_login, $user )
    {

        $token = wp_generate_password( 16, true, true );

        $user_id = $user->ID;

        $domain = parse_url( esc_url_raw( $_POST[ 'return_url' ] ), PHP_URL_HOST );

        if ( WPGH()->tokens->token_exists( array( 'user_id' => $user_id, 'domain' => $domain ) ) ){

            $tokens = WPGH()->tokens->get_tokens( array( 'user_id' => $user_id, 'domain' => $domain ) );

            $token = array_pop( $tokens );

            WPGH()->tokens->update( $token->ID, array( 'token' => $token ) );

        } else {

            $tokenId = WPGH()->tokens->add(
                array(
                    'user_id' => $user_id,
                    'token'   => $token,
                    'domain'  => $domain
                )
            );

        }

        $return_url = add_query_arg( 'token', base64_encode( $token ), esc_url_raw( $_POST[ 'return_url' ] ) );

        wp_redirect( $return_url );
        die();
    }


    /**
     * This is where you decide what todo
     */
    public function process()
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $json = file_get_contents('php://input');
            //echo $json;

            $params = json_decode($json);

            if (isset($params->token) && isset($params->domain)) {

                if ( $this->user = $this->is_valid_token($params->token, $params->domain ) ) {

                    $module = $params->module;

                    if (file_exists(dirname(__FILE__) . '/class-wpgh-api-v1-' . $module . '.php')) {

                        require_once dirname(__FILE__) . '/class-wpgh-api-v1-' . $module . '.php';

                        $class = 'wpgh_api_v1_' . $module;

                        $api = new $class();
                        if (method_exists($api, $params->method)) {

                            $result = call_user_func(array($api, $params->method), $params->data);

                            if ( is_wp_error( $result ) ){

                                $response = array(
                                    'success' => false,
                                    'error' => $result->get_error_message()
                                );

                            } else {

                                $response = array(
                                    'success' => true,
                                    'data' => $result
                                );

                            }

                        } else {

                            $response = array(
                                'success' => false,
                                'error' => __('The requested method does not exist for module: ' . $module . '.')
                            );

                        }

                    } else {

                        $response = array(
                            'success' => false,
                            'error' => __('The requested module does not exist.')
                        );
                    }

                } else {// invalid token
                    $response = array(
                        'success' => true,
                        'error' => __('Your token is invalid.')
                    );
                }

            } else {
                $response = array(
                    'success' => false,
                    'error' => __("Please provide a token.")
                );
            }

        } else if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {

            $response = array(
                'success' => false,
                'error' => __("Please use POST method to use API. ")
            );

        }

        if (!isset($response)) {
            $response = array(
                'success' => false,
                'error' => __("Something went wrong, but we do not know what.")
            );
        }

        echo json_encode($response);
        die();

    }

    /**
     * Verify a given token is valid.
     *
     * @param $token string the given token.
     * @param $domain string the given domain which the token should be valid for.
     * @return false|WP_User The user if the token is valid, false otherwise
     */
    public function is_valid_token( $token, $domain )
    {

        if ( WPGH()->tokens->count( array( 'token'=> $token , 'domain' =>$domain ) ) > 0 ) {// valid token

            $token = WPGH()->tokens->get_tokens(array('token' => $token, 'domain' => $domain));
            $token = array_pop($token);

            $user = get_userdata(intval($token->user_id));

            if ($user && ! is_wp_error($user)) {
                return $user;
            }

        }

        return false;

    }

}