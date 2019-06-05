<?php
namespace Groundhogg\Api\V3;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 12/12/2018
 * Time: 4:18 PM
 */

class API_V3
{

    /**
     * @var BASE[]
     */
    public $apis = [];


    public function __construct()
    {
        /**
         * Use this action to declare extension endpoints...
         */
        do_action( 'groundhogg/api/v3/pre_init', $this );

        $this->declare_base_endpoints();

        do_action( 'groundhogg/api/v3/init', $this );

    }

    /**
     * Declare the initial endpoints.
     */
    public function declare_base_endpoints()
    {
        $this->contacts = new Contacts_Api();
        $this->authentication = new Authentication_Api();
        $this->tags     = new Tags_Api();
        $this->emails   = new Email_Api();
        $this->sms      = new Sms_Api();
        $this->tracking = new Tracking_Api();
        $this->data     = new Data_Api();
        $this->reports  = new Reports_Api();
    }

    /**
     * Get API class
     *
     * @param $name
     * @return mixed | BASE
     */
    public function __get($name)
    {
        if ( property_exists( $this, $name ) ){

            return $this->$name;

        } else if ( isset( $this->apis[ $name ] ) ){

            return $this->apis[ $name ];

        } else {
            return false;
        }
    }

    /**
     * Set extension apis
     *
     * @param $name
     * @param $value
     */
    public function __set( $name, $value )
    {
        $this->apis[ $name ] = $value;
    }

}