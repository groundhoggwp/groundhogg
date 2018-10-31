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
     * This is where you decide what //todo
     */
    public function process()
    {


       // $parts = explode( '/', $request );

        /*
         * array(
         *
         * 'api', 'v1', 'contacts', 'add'
         * 'api', 'v1', 'contacts', 'delete', '1234'
         *
         * )
         *
         *
         * */

        /* get json */

        /* extract token */

        /* get the user */


        /* perform request... */

        // $this->user = get_user_by( 'ID', '' );

//        echo $_SERVER[ 'HTTP_REFERER' ];
//        die();

       // $domain = parse_url( $_SERVER[ 'HTTP_REFERER' ], PHP_URL_HOST );

         if ($_SERVER['REQUEST_METHOD']==='POST')
         {

             $json = file_get_contents('php://input');
             //echo $json;

             $params = json_decode($json);

             //check users has a token
             if (isset($params->token) && isset($params->domain) )
             {
                    // $user = WPGH()->tokens->get_tokens(array('token' => $params->token ,'domain' => $params->domain));
                     if ($this->is_valid_token($params->token,$params->domain))
                     {// valid token
                         //authorise user to perform operation

                         // return user
                         //$user = WPGH()->tokens->get_tokens(array('token' => $params->token , 'domain' => $params->domain));


                         $module = $params->module;

                         if ( file_exists( dirname( __FILE__ ) . '/class-wpgh-api-v1-' . $module . '.php' ) ){

                             require_once  dirname( __FILE__ ) . '/class-wpgh-api-v1-' . $module . '.php';

                             $class = 'wpgh_api_v1_' . $module;

                             $api = new $class();

                             if ( method_exists( $api, $params->method ) ){

                                $result = call_user_func( array( $api,$params->method), $params->data );
//                                 $result = $api->$params->method( $params->data );


                                 $response = array(
                                     'success' => true,
                                     'data' =>$result
                                 );

                                 echo json_encode( $response );
                                 die();

                             }

                         }

                         /* END */

                        // echo $user[0]->user_id; // code to get userid



                     }
                     else
                     {// invalid token
                         $response = array(
                             'success' => true,
                             'data' => 'Your Token is Invalid '
                         );
                         echo json_encode($response);
                     }
             }
             else
             {
                 $response = array(
                     'success' => false,
                     'data' => "please Eneter token"
                 );

                 echo json_encode($response);
             }
         }

        elseif ($_SERVER['REQUEST_METHOD']==='GET'||$_SERVER['REQUEST_METHOD']==='PUT'||$_SERVER['REQUEST_METHOD']==='DELETE')
        {

                $response = array(
                    'success' => false,
                    'message' => "please use POST method to use API."
                );

                echo json_encode($response);
                // get list of contats from user_id

                // $user = WPGH()->tokens->get_tokens(array('token' => $params->token , 'domain' => $params->domain));

                //$contacts  = WPGH()->contacts->get_contacts();

            }
            die();

    }


    public function is_valid_token($token,$domain)
    {

        if (WPGH()->tokens->count(array('token'=> $token , 'domain' =>$domain))>0)
        {// valid token

            return true;
        }
        else
        {
            $response = array(
                'success' => false,
                'data' => 'In Valid token'  ,

            );

            echo json_encode($response);
            die();
        }

    }

}