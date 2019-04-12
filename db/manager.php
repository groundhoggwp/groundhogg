<?php
namespace Groundhogg\DB;

/**
 * DB Manager to manage databases in Groundhogg
 *
 * Class Manager
 * @package Groundhogg\DB
 */
class Manager
{

    /**
     * @var DB[]
     */
    protected $dbs = [];

    /**
     * Manager constructor.
     */
    public function __construct()
    {
        $this->init_dbs();
    }

    /**
     * Setup the base DBs for the plugin
     */
    protected function init_dbs(){
        $this->activity     = new Activity();
        $this->broadcasts   = new Broadcasts();
        $this->contactmeta  = new Contact_Meta();
        $this->contacts     = new Contacts();
        $this->emailmeta    = new Email_Meta();
        $this->emails       = new Emails();
        $this->events       = new Events();
        $this->funnels      = new Funnels();
        $this->sms          = new SMS();
        $this->stepmeta     = new Step_Meta();
        $this->steps        = new Steps();
        $this->superlinks   = new Superlinks();
        $this->tags         = new Tags();
        $this->tag_relationships = new Tag_Relationships();

        do_action( 'groundhogg/db/manager/init', $this );
    }

    /**
     * Set the data to the given value
     *
     * @param $key string
     * @return DB|Meta_DB
     */
    public function get_db( $key ){
        return $this->$key;
    }

    /**
     * Magic get method
     *
     * @param $key string
     * @return bool|DB|Meta_DB
     */
    public function __get( $key )
    {
        if ( gisset_not_empty( $this->dbs, $key ) ){
            return $this->dbs[ $key ];
        }

        return false;
    }


    /**
     * Set the data to the given value
     *
     * @param $key string
     * @param $value DB|Meta_DB
     */
    public function __set( $key, $value )
    {
        $this->dbs[ $key ] = $value;
    }

}
