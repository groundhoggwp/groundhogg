<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 10/29/2018
 * Time: 10:58 AM
 */


class WPGH_API_V1
{

    /**
     * This recognizes that their is an API call being made.
     *
     * WPGH_API_V1 constructor.
     */
    public function __construct()
    {

        //todo
        if ( strpos( $_SERVER[ 'REQUEST_URI' ], 'api/v1' ) ){

            add_action( 'init', array( $this, 'process' ) );

        }

    }


    /**
     * This is where you decide what //todo
     */
    public function process()
    {

        $response = array(

            'success' => true,

        );

        echo json_encode( $response );
        die();
    }

    public function add_contact()
    {

        WPGH()->contacts->add(

            array( 'info' )

        );

    }

}