<?php
namespace Groundhogg\Admin;

use Groundhogg\Admin\Broadcasts\Broadcasts_Page;
use Groundhogg\Admin\Bulk_Jobs\Bulk_Job_Page;
use Groundhogg\Admin\Contacts\Contacts_Page;
use Groundhogg\Admin\Dashboard\Dashboard_Widgets;
use Groundhogg\Admin\Emails\Emails_Page;
use Groundhogg\Admin\Events\Events_Page;
use Groundhogg\Admin\Funnels\Funnels_Page;
use Groundhogg\Admin\Guided_Setup\Guided_Setup;
use Groundhogg\Admin\Settings\Settings_Page;
use Groundhogg\Admin\SMS\SMS_Page;
use Groundhogg\Admin\Superlinks\Superlinks_Page;
use Groundhogg\Admin\Tags\Tags_Page;
use Groundhogg\Admin\Tools\Tools_Page;
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
        add_action( 'init', [ $this, 'init_admin' ] );
    }

    /**
     * Setup the base DBs for the plugin
     */
    public function init_admin(){

        $this->welcome  = new Welcome_Page();

        $this->contacts = new Contacts_Page();
        $this->tags     = new Tags_Page();
        $this->emails   = new Emails_Page();
        $this->sms      = new SMS_Page();
        $this->broadcasts = new Broadcasts_Page();
        $this->funnels  = new Funnels_Page();
        $this->superlinks = new Superlinks_Page();
        $this->events   = new Events_Page();
        $this->tools    = new Tools_Page();
        $this->settings = new Settings_Page();
        $this->bulk_jobs = new Bulk_Job_Page();

        $this->dashboard = new Dashboard_Widgets();

        $this->guided_setup = new Guided_Setup();

        do_action( 'groundhogg/admin/init', $this );
    }

    /**
     * Set the data to the given value
     *
     * @param $key string
     * @return Admin_Page|Funnels_Page
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
