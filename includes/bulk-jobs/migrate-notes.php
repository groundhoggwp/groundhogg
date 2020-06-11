<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact;
use Groundhogg\Plugin;
use function Groundhogg\convert_to_local_time;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Migrate_Notes extends Bulk_Job {


	public function __construct() {
		add_action( 'admin_init', [ $this, 'show_upgrade_prompt' ] );
		parent::__construct();
	}


	public function show_upgrade_prompt() {

		if ( ! get_option( 'gh_migrate_notes' ) || ! current_user_can( 'perform_bulk_actions' ) ) {
			return;
		}


		$update_button = html()->e( 'a', [
			'href'  => $this->get_start_url(),
			'class' => 'button button-secondary'
		], __( 'Migrate now!', 'groundhogg' ) );

		$notice = sprintf( __( "%s requires a database migration. Consider backing up your site before migrating. </p><p>%s", 'groundhogg' ), white_labeled_name(), $update_button );

		Plugin::$instance->notices->add( 'db-update', $notice );
	}


	public function get_action() {
		return 'migrate-notes';
	}

	public function query( $items ) {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			return $items;
		}

		$query = get_db( 'contactmeta' )->query(
			[ 'meta_key' => 'notes' ] );
		$ids   = wp_list_pluck( $query, 'contact_id' );

		return $ids;

	}

	public function max_items( $max, $items ) {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			return $max;
		}

		return min( 100, intval( ini_get( 'max_input_vars' ) ) );
	}

	protected function pre_loop() {
		// TODO: Implement pre_loop() method.
	}

	protected function process_item( $item ) {

		$contact = new Contact( $item );
		if ( ! $contact->exists() ) {
			return;
		}

		$note = $contact->get_meta( 'notes' );


		$your_array = explode( "\n", $note );

		$final_array = [];

		$note = "";
		$date = "";

		foreach ( $your_array as $line ) {
			if ( strpos( $line, "=====" ) !== false ) {


				if ( $note && $date ) {
					$final_array[] = [
						'contact_id'   => $contact->get_id(), //todo
						'context'      => 'system',
						'content'      => $note,
						'date_created' => date( 'Y-m-d H:i:s', Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $date ) ) ),
						'timestamp'    => Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $date ) ),

					];
				}


				$date = substr( $line, 5, strpos( $line, "=====", 5 ) - 5 );

				$note = "";

			} else {

				$note = $note . $line . "\n";
			}
		}

		$final_array[] = [
			'contact_id'   => $contact->get_id(), //todo
			'context'      => 'system',
			'content'      => $note,
			'date_created' => date( 'Y-m-d H:i:s', Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $date ) ) ),
			'timestamp'    => Plugin::$instance->utils->date_time->convert_to_utc_0( strtotime( $date ) ),
		];

		foreach ( array_reverse( $final_array ) as $item ) {
			get_db( 'contactnotes' )->add( $item );
		}

		$contact->delete_meta( 'notes' );

	}

	protected function post_loop() {
		// TODO: Implement post_loop() method.
	}

	protected function clean_up() {
		delete_option( 'gh_migrate_notes' );
	}
}