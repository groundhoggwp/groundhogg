<?php

namespace Groundhogg\Api\V4;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 12/12/2018
 * Time: 4:18 PM
 */
class API_V4_HANDLER {

	/**
	 * @var Base_Api[]
	 */
	public $apis = [];


	public function __construct() {
		/**
		 * Use this action to declare extension endpoints...
		 */
		do_action( 'groundhogg/api/v4/pre_init', $this );

		$this->declare_base_endpoints();

		do_action( 'groundhogg/api/v4/init', $this );
	}

	/**
	 * Declare the initial endpoints.
	 */
	public function declare_base_endpoints() {

		$this->contacts        = new Contacts_Api();
		$this->notes_api       = new Notes_Api();
		$this->tags            = new Tags_Api();
		$this->fields          = new Fields_Api();
		$this->properties      = new Properties_Api();
		$this->emails          = new Emails_Api();
		$this->broadcasts      = new Broadcasts_Api();
		$this->funnels_api     = new Funnels_Api();
		$this->steps_api       = new Steps_Api();
		$this->activity_api    = new Activity_Api();
		$this->events_api      = new Events_Api();
		$this->event_queue_api = new Event_Queue_Api();
		$this->submissions_api = new Submissions_Api();
		$this->files_api       = new Files_Api();
		$this->searches_api    = new Searches_Api();
		$this->reports         = new Reports_Api();
		$this->email_log       = new Email_Log_Api();
		$this->unsubscribe_api = new Unsubscribe_Api();
		$this->campaings_api   = new Campaigns_Api();
		$this->tracking_api    = new Tracking_Api();
		$this->forms           = new Forms_Api();
		$this->options         = new Options_Api();
		$this->page_visits     = new Page_Visits_Api();
	}

	/**
	 * Get API class
	 *
	 * @param $name
	 *
	 * @return mixed | Base_Api
	 */
	public function __get( $name ) {
		if ( property_exists( $this, $name ) ) {

			return $this->$name;

		} else if ( isset( $this->apis[ $name ] ) ) {

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
	public function __set( $name, $value ) {
		$this->apis[ $name ] = $value;
	}

}
