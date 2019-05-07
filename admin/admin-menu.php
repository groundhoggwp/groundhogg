<?php
namespace Groundhogg\Admin;

use Groundhogg\Admin\Emails\Emails_Page;
use Groundhogg\Admin\SMS\SMS_Page;
use Groundhogg\Admin\Tags\Tags_Page;
use Groundhogg\Admin\Welcome\Welcome_Page;
use function Groundhogg\isset_not_empty;

/**
 * Admin Manager to manage databases in Groundhogg
 *
 * Class Manager
 * @package Groundhogg\Admin
 */
class Admin_Menu
{

    /**
     * @var Admin_Page[]
     */
    protected $pages = [];

    /**
     * Manager constructor.
     */
    public function __construct()
    {
        $this->init_admin();
    }

    /**
     * Setup the base DBs for the plugin
     */
    protected function init_admin(){

        $this->welcome  = new Welcome_Page();
        $this->tags     = new Tags_Page();
        $this->sms      = new SMS_Page();

        do_action( 'groundhogg/admin/init', $this );
    }

    /**
     * Set the data to the given value
     *
     * @param $key string
     * @return Admin_Page
     */
    public function get_page( $key ){
        return $this->$key;
    }

    /**
     * Magic get method
     *
     * @param $key string
     * @return bool|Admin_Page
     */
    public function __get( $key )
    {
        if ( isset_not_empty( $this->pages, $key ) ){
            return $this->pages[ $key ];
        }

        return false;
    }

    /**
     * Set the data to the given value
     *
     * @param $key string
     * @param $value Admin_Page
     */
    public function __set( $key, $value )
    {
        $this->pages[ $key ] = $value;
    }

}
