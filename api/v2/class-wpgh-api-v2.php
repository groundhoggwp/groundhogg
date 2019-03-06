<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 12/12/2018
 * Time: 4:18 PM
 */

class WPGH_API_V2
{
    /**
     * @var WPGH_API_V2_CONTACTS
     */
    public $contacts;

    /**
     * @var WPGH_API_V2_TAGS
     */
    public $tags;

    /**
     * @var WPGH_API_V2_EMAILS
     */
    public $emails;

    /**
     * @var WPGH_API_V2_SMS
     */
    public $sms;

    /**
     * @var WPGH_API_V2_BASE[]
     */
    public $extension_apis = array();


    public function __construct()
    {

        $this->includes();

        $this->contacts = new WPGH_API_V2_CONTACTS();
        $this->tags     = new WPGH_API_V2_TAGS();
        $this->emails   = new WPGH_API_V2_EMAILS();
        $this->sms      = new WPGH_API_V2_SMS();

//        $this->load_extension_apis();

    }

    /**
     * Get API class
     *
     * @param $name
     * @return mixed | WPGH_API_V2_BASE
     */
    public function __get($name)
    {
        if ( property_exists( $this, $name ) ){

            return $this->$name;

        } else if ( isset( $this->extension_apis[ $name ] ) ){

            return $this->extension_apis[ $name ];

        } else {
            return false;
        }
    }

    /**
     * Filter for extensions to add their implementations of the API class
     */
    private function load_extension_apis()
    {
        $this->extension_apis = apply_filters( 'wpgh_api_add_extension', $this->extension_apis );
    }

    /**
     * Include base files.
     */
    private function includes()
    {

        include_once dirname( __FILE__ ) . '/class-wpgh-api-v2-base.php';
        include_once dirname( __FILE__ ) . '/class-wpgh-api-v2-contacts.php';
        include_once dirname( __FILE__ ) . '/class-wpgh-api-v2-tags.php';
        include_once dirname( __FILE__ ) . '/class-wpgh-api-v2-emails.php';
        include_once dirname( __FILE__ ) . '/class-wpgh-api-v2-sms.php';

        do_action( 'wpgh_api_include_extensions', $this );

    }

}