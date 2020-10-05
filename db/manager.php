<?php

namespace Groundhogg\DB;

use function Groundhogg\isset_not_empty;

/**
 * DB Manager to manage databases in Groundhogg
 *
 * Class Manager
 * @package Groundhogg\DB
 */
class Manager {

	/**
	 * List of DBs
	 *
	 * @var DB[]|Meta_DB[]
	 */
	protected $dbs = [];

	protected $initialized = false;

	/**
	 * Manager constructor.
	 */
	public function __construct() {
		// GET THOSE DBS UP AND RUNNING ASAP
		add_action( 'plugins_loaded', [ $this, 'init_dbs' ], 1 );

		// Re-init the DBS if a new plugin is activated, like an extension.
		add_action( 'activate_plugin', [ $this, 'listen_for_addons' ], 1 );
	}

	public function listen_for_addons() {
		do_action( 'groundhogg/db/manager/init', $this );
	}

	/**
	 * @return bool
	 */
	public function is_initialized() {
		return $this->initialized;
	}

	/**
	 * Setup the base DBs for the plugin
	 */
	public function init_dbs() {
		$this->activity          = new Activity();
		$this->activitymeta      = new Activity_Meta();
		$this->broadcasts        = new Broadcasts();
		$this->broadcastmeta     = new Broadcast_Meta();
		$this->contactmeta       = new Contact_Meta();
		$this->contacts          = new Contacts();
		$this->emailmeta         = new Email_Meta();
		$this->emails            = new Emails();
		$this->events            = new Events();
		$this->funnels           = new Funnels();
		$this->funnelmeta        = new Funnel_Meta();
		$this->stepmeta          = new Step_Meta();
		$this->steps             = new Steps();
		$this->tags              = new Tags();
		$this->tag_relationships = new Tag_Relationships();
		$this->submissions       = new Submissions();
		$this->submissionmeta    = new Submission_Meta();
		$this->form_impressions  = new Form_Impressions();
		$this->notes             = new Notes();
		$this->permissions_keys  = new Permissions_Keys();

		//new queue table
		$this->event_queue = new Event_Queue();

		/**
		 * Runs when the DB Manager is setup and all the standard DBs have been initialized.
		 */
		$this->listen_for_addons();

		$this->initialized = true;
	}

	/**
	 * Install all DBS.
	 */
	public function install_dbs() {
		if ( empty( $this->dbs ) ) {
			$this->init_dbs();
		}

		foreach ( $this->dbs as $db ) {
			$db->create_table();
		}
	}

	/**
	 * Empty all of the dbs.
	 */
	public function truncate_dbs() {
		if ( empty( $this->dbs ) ) {
			$this->init_dbs();
		}

		foreach ( $this->dbs as $db ) {
			$db->truncate();
		}
	}

	/**
	 * Drop all the DBs
	 */
	public function drop_dbs() {
		if ( empty( $this->dbs ) ) {
			$this->init_dbs();
		}

		foreach ( $this->dbs as $db ) {
			$db->drop();
		}
	}

	/**
	 * Get all the table names.
	 *
	 * @return string[]
	 */
	public function get_table_names() {
		$table_names = [];

		foreach ( $this->dbs as $db ) {

			$table_names[] = $db->get_table_name();

		}

		return $table_names;
	}

	/**
	 * Set the data to the given value
	 *
	 * @param $key string
	 *
	 * @return DB|Meta_DB|Tags
	 */
	public function get_db( $key ) {
		return $this->$key;
	}

	/**
	 * @return DB[]|Meta_DB[]
	 */
	public function get_dbs() {
		return $this->dbs;
	}

	/**
	 * Magic get method
	 *
	 * @param $key string
	 *
	 * @return bool|DB|Meta_DB
	 */
	public function __get( $key ) {
		if ( isset_not_empty( $this->dbs, $key ) ) {
			return $this->dbs[ $key ];
		}

		return false;
	}


	/**
	 * Set the data to the given value
	 *
	 * @param $key   string
	 * @param $value DB|Meta_DB
	 */
	public function __set( $key, $value ) {
		$this->dbs[ $key ] = $value;
	}

}
