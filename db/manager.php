<?php
namespace Groundhogg\DB;

use function Groundhogg\isset_not_empty;

/**
 * DB Manager to manage databases in Groundhogg
 *
 * Class Manager
 * @package Groundhogg\DB
 */
class Manager
{

    /**
     * List of DBs
     *
     * @var DB[]|Meta_DB[]
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
        $this->submissions = new Submissions();
        $this->submissionmeta = new Submission_Meta();

        /**
         * Runs when the DB Manager is setup and all the standard DBs have been initialized.
         */
        do_action( 'groundhogg/db/manager/init', $this );
    }

    /**
     * Install all DBS.
     */
    public function install_dbs()
    {
        foreach ( $this->dbs as $db ){
            $db->create_table();
        }
    }

    /**
     * Drop all the DBs
     */
    public function drop_dbs()
    {
        foreach ( $this->dbs as $db ){
            $db->drop();
        }
    }

    /**
     * Get all the table names.
     *
     * @return string[]
     */
    public function get_table_names()
    {
        $table_names = [];

        foreach ( $this->dbs as $db ){

            $table_names[] = $db->get_table_name();

        }

        return $table_names;
    }

    /**
     * Set the data to the given value
     *
     * @param $key string
     * @return DB|Meta_DB|Tags
     */
    public function get_db( $key ){
        return $this->$key;
    }

    /**
     * @return DB[]|Meta_DB[]
     */
    public function get_dbs(){
        return $this->dbs;
    }

    /**
     * Magic get method
     *
     * @param $key string
     * @return bool|DB|Meta_DB
     */
    public function __get( $key )
    {
        if ( isset_not_empty( $this->dbs, $key ) ){
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
