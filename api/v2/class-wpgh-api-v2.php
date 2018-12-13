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

    public function __construct()
    {

        $this->includes();

        $this->contacts = new WPGH_API_V2_CONTACTS();
        $this->tags     = new WPGH_API_V2_TAGS();
    }

    private function includes()
    {

        include_once dirname( __FILE__ ) . '/class-wpgh-api-v2-base.php';
        include_once dirname( __FILE__ ) . '/class-wpgh-api-v2-contacts.php';
        include_once dirname( __FILE__ ) . '/class-wpgh-api-v2-tags.php';

    }

}