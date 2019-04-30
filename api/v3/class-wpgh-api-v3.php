<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 12/12/2018
 * Time: 4:18 PM
 */

class WPGH_API_V3
{

    /**
     * @var WPGH_API_V3_BASE[]
     */
    public $apis = [];


    public function __construct()
    {

        $this->includes();
        do_action( 'groundhogg/api/v3/pre_init', $this );

        $this->declare_base_endpoints();

        do_action( 'groundhogg/api/v3/register_endpoints', $this );
        do_action( 'groundhogg/api/v3/init', $this );

    }

    /**
     * Declare the initial endpoints.
     */
    public function declare_base_endpoints()
    {
        $this->contacts = new WPGH_API_V3_CONTACTS();
        $this->tags     = new WPGH_API_V3_TAGS();
        $this->emails   = new WPGH_API_V3_EMAILS();
        $this->sms      = new WPGH_API_V3_SMS();
        $this->elements = new WPGH_API_V3_ELEMENTS();
    }

    /**
     * Get API class
     *
     * @param $name
     * @return mixed | WPGH_API_V3_BASE
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

    /**
     * Include base files.
     */
    private function includes()
    {

        include_once dirname(__FILE__) . '/class-wpgh-api-v3-base.php';
        include_once dirname(__FILE__) . '/class-wpgh-api-v3-contacts.php';
        include_once dirname(__FILE__) . '/class-wpgh-api-v3-tags.php';
        include_once dirname(__FILE__) . '/class-wpgh-api-v3-emails.php';
        include_once dirname(__FILE__) . '/class-wpgh-api-v3-sms.php';
        include_once dirname(__FILE__) . '/class-wpgh-api-v3-elements.php';

        do_action( 'groundhogg/api/v3/includes', $this );

    }

}