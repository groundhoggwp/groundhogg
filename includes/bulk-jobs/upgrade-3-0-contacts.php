<?php

namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Contact_Query;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_contactdata;
use function Groundhogg\is_a_contact;
use function Groundhogg\sanitize_phone_number;
use function Groundhogg\Ymd;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Move primary_phone, primary_phone_extension, mobile_phone, gdpr_content, terms_consent, marketing_consent to the primary contacts table
 */
class Upgrade_3_0_Contacts extends Bulk_Job {

	/**
	 * Get the action reference.
	 *
	 * @return string
	 */
	function get_action() {
		return 'gh_3_0_contacts';
	}

	/**
	 * Get an array of items someway somehow
	 *
	 * @param $items array
	 *
	 * @return array
	 */
	public function query( $items ) {
		if ( ! current_user_can( 'edit_contacts' ) ) {
			return $items;
		}

		$args = [];

		$query    = new Contact_Query();
		$contacts = $query->query( $args );

		return wp_list_pluck( $contacts, 'ID' );
	}

	/**
	 * Process an item
	 *
	 * @param $item mixed
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function process_item( $item ) {

		$contact = get_contactdata( $item );

		if ( ! is_a_contact( $contact ) ) {
			return;
		}

		$args = [
			'primary_phone'                => sanitize_phone_number( $contact->get_meta( 'primary_phone' ) ),
			'primary_phone_extension'      => sanitize_phone_number( $contact->get_meta( 'primary_phone_extension' ) ),
			'mobile_phone'                 => sanitize_phone_number( $contact->get_meta( 'mobile_phone' ) ),
			'terms_agreement_date'         => $contact->get_meta( 'terms_agreement_date' ),
			'data_processing_consent_date' => $contact->get_meta( 'gdpr_consent_date' ),
			'marketing_consent_date'       => $contact->get_meta( 'marketing_consent_date' ),
		];

		$contact->update( $args );

		$contact->delete_meta( [
			'primary_phone',
			'mobile_phone',
			'primary_phone_extension',
			'terms_agreement_date',
			'terms_agreement',
			'gdpr_consent_date',
			'gdpr_consent',
			'marketing_consent_date',
			'marketing_consent'
		] );
	}

	/**
	 * Do stuff before the loop
	 *
	 * @return void
	 */
	protected function pre_loop() {
	}

	/**
	 * do stuff after the loop
	 *
	 * @return void
	 */
	protected function post_loop() {
	}

	/**
	 * Cleanup any options/transients/notices after the bulk job has been processed.
	 *
	 * @return void
	 */
	protected function clean_up() {
	}

	/**
	 * Get the return URL
	 *
	 * @return string
	 */
	protected function get_return_url() {
		return admin_page_url( 'gh_contacts', [] );
	}
}