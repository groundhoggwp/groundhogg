<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact;
use Groundhogg\Plugin;
use function Groundhogg\convert_to_local_time;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\html;
use function Groundhogg\notices;
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
		], __( 'Migrate notes now!', 'groundhogg' ) );

		$notice = sprintf( __( "Contact notes have been updated and must be migrated, consider backing up your site before migrating.</p><p>%s", 'groundhogg' ), $update_button );

		notices()->add( 'migrate-notes', $notice );
	}

	/**
	 * @return string
	 */
	public function get_action() {
		return 'migrate-notes';
	}

	/**
	 * @param array $items
	 *
	 * @return array
	 */
	public function query( $items ) {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			return $items;
		}

		$query = get_db( 'contactmeta' )->query( [
			'meta_key' => 'notes'
		] );

		return wp_list_pluck( $query, 'contact_id' );
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

		$old_notes = $contact->get_meta( 'notes' );
		preg_match_all( "/===== ([^=]+) =====([^=]+)/m", $old_notes, $matches );

		$dates = $matches[1];
		$notes = $matches[2];

		for ( $i = 0; $i < count( $notes ); $i ++ ) {

			$time = strtotime( $dates[ $i ] );

			$note_to_add = [
				'contact_id'   => $item,
				'context'      => 'system',
				'content'      => sanitize_textarea_field( $notes[ $i ] ),
				'date_created' => date( 'Y-m-d H:i:s', $time ),
				'timestamp'    => $time,
			];

			get_db( 'contactnotes' )->add( $note_to_add );
		}

		$contact->delete_meta( 'notes' );

	}

	protected function post_loop() {
		// TODO: Implement post_loop() method.
	}

	protected function clean_up() {
		delete_option( 'gh_migrate_notes' );
		notices()->remove( 'migrate-notes' );
	}
}