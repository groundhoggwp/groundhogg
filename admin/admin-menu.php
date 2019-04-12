<?php
namespace Groundhogg\Admin;

use Groundhogg\Admin\Welcome\Welcome_Page;

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

        $this->welcome = new Welcome_Page();

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
        if ( gisset_not_empty( $this->pages, $key ) ){
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
