<?php

namespace Groundhogg\Bulk_Jobs;

use function Groundhogg\get_array_var;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-15
 * Time: 3:24 PM
 */
class Manager {

	protected $jobs = [];

	/**
	 * Manager constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init_jobs' ] );
	}

	public function init_jobs() {
		$this->broadcast_scheduler      = new Broadcast_Scheduler();
		$this->delete_contacts          = new Delete_Contacts();
		$this->export_contacts          = new Export_Contacts();
		$this->import_contacts          = new Import_Contacts();
		$this->sync_contacts            = new Sync_Users();
		$this->add_contacts_to_funnel   = new Add_Contacts_To_Funnel();
		$this->create_users             = new Create_Users();
		$this->process_events           = new Process_Events();
		$this->update_subsites          = new Update_subsites();
		$this->check_licenses           = new Check_Licenses();
		$this->update_marketing_consent = new Update_Marketing_Consent();
		$this->bulk_edit_contacts       = new Edit_Contacts();
		$this->fix_birthdays            = new Fix_Birthdays();
		$this->process_bg_task          = new Process_Bg_Task();

		do_action( 'groundhogg/bulk_jobs/init', $this );
	}

	/**
	 * Magic get method
	 *
	 * @param $key string
	 *
	 * @return bool|Bulk_Job
	 */
	public function __get( $key ) {
		return get_array_var( $this->jobs, $key, false );
	}

	/**
	 * Set the data to the given value
	 *
	 * @param $key   string
	 * @param $value Bulk_Job
	 */
	public function __set( $key, $value ) {
		$this->jobs[ $key ] = $value;
	}


}
