<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Plugin;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Migrate_Waiting_Events extends Bulk_Job {


	public function __construct() {
		add_action( 'admin_init', [ $this, 'show_upgrade_prompt' ] );
		parent::__construct();
	}


	public function show_upgrade_prompt() {
		if ( get_option( 'gh_migrate_form_impressions' ) && current_user_can( 'edit_contacts' ) ) {
			Plugin::$instance->notices->add( 'db-update', "<a href='{$this->get_start_url()}'>" . __( 'Thank you for updating to 2.0! Please click here to update your database.', 'groundhogg' ) . "</a>" );
		}
	}


	public function get_action() {
		return 'migrate-notes';;
	}

	public function query( $items ) {
		// TODO: Implement query() method.

		//get all the meta

	}

	public function max_items( $max, $items ) {
		if ( ! current_user_can( 'edit_funnels' ) ) {
			return $max;
		}

		return min( 100, intval( ini_get( 'max_input_vars' ) ) );
	}

	protected function pre_loop() {
		// TODO: Implement pre_loop() method.
	}

	protected function process_item( $item ) {
		// TODO: Implement process_item() method.


		$contact = get_contactdata($item);

		$note = $contact->get_meta( 'notes' );


		$your_array = explode( "\n", $note );

		$final_array = [];

		$note = "";
		$date = "";

		foreach ( $your_array as $line ) {
			if ( strpos( $line, "=====" ) !== false ) {


				if ( $note && $date ) {
					$final_array[] = [
						'contact_id' => $contact->get_id(), //todo
						'context'    => 'system',
						'content'      => $note,
						'date_created' => $date,
						'timestamp'    => strtotime( $date ),
					];
				}


				$date = substr( $line, 5, strpos( $line, "=====", 5 ) - 5 );

				$note = "";

			} else {

				$note = $note . $line . "\n";
			}
		}

		$final_array[] = [
			'contact_id' => $contact->get_id(), //todo
			'context'    => 'system',
			'content'      => $note,
			'date_created' => $date,
			'timestamp'    => strtotime( $date ),
		];

		foreach ( array_reverse($final_array ) as $item)
		{
//			get_db('contactnotes')->add($item);
		}


		// code to

	}

	protected function post_loop() {
		// TODO: Implement post_loop() method.
	}

	protected function clean_up() {
		// TODO: Implement clean_up() method.
	}
}