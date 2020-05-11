<?php

use Groundhogg\Plugin;

class GH_UnitTest_Factory extends WP_UnitTest_Factory {
	/**
	 * @var GH_UnitTest_Factory_For_Contact
	 */
	public $contacts;

	/**
	 * @var GH_UnitTest_Factory_For_Funnel
	 */
	public $funnels;

	/**
	 * @var GH_UnitTest_Factory_For_Step
	 */
	public $steps;

	/**
	 * @var GH_UnitTest_Factory_For_Event
	 */
	public $events;

	/**
	 * @var GH_UnitTest_Factory_For_Event_Queue
	 */
	public $event_queue;

	/**
	 * @var GH_UnitTest_Factory_For_Activity
	 */
	public $activity;

	/**
	 * GH_UnitTest_Factory constructor.
	 */
	public function __construct() {

		parent::__construct();

		$this->contacts    = new GH_UnitTest_Factory_For_Contact( $this );
		$this->funnels     = new GH_UnitTest_Factory_For_Funnel( $this );
		$this->steps       = new GH_UnitTest_Factory_For_Step( $this );
		$this->events      = new GH_UnitTest_Factory_For_Event( $this );
		$this->event_queue = new GH_UnitTest_Factory_For_Event_Queue( $this );
		$this->activity    = new GH_UnitTest_Factory_For_Activity( $this );
	}

	/**
	 * empty all the dbs.
	 */
	public function truncate() {
		Plugin::$instance->dbs->truncate_dbs();
	}
}
