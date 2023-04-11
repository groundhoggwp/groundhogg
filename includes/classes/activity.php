<?php

namespace Groundhogg\Classes;

use Groundhogg\Base_Object;
use Groundhogg\Base_Object_With_Meta;
use Groundhogg\Contact;
use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;
use Groundhogg\Email;
use Groundhogg\Utils\DateTimeHelper;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;

class Activity extends Base_Object_With_Meta {
	const EMAIL_OPENED = 'email_opened';
	const EMAIL_CLICKED = 'email_link_click';
	const SMS_CLICKED = 'sms_link_click';
	const FORM_IMPRESSION = 'form_impression';
	const FORM_SUBMISSION = 'form_submission';
	const UNSUBSCRIBED = 'unsubscribed';
	const PAGE_VIEW = 'page_view';
	const LOGIN = 'wp_login';

	public $contact;

	/**
	 * Retrieve associated the contact
	 *
	 * @return false|\Groundhogg\Contact
	 */
	public function get_contact() {
		if ( $this->contact ){
			return $this->contact;
		}

		$this->contact = new Contact( $this->contact_id );
		return $this->contact;
	}

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {
		$this->type = $this->activity_type;
	}

	public function get_timestamp() {
		return absint( $this->timestamp );
	}

	public function get_time() {
		return $this->get_timestamp();
	}

	public function get_step_id() {
		return absint( $this->step_id );
	}

	public function get_funnel_id() {
		return absint( $this->funnel_id );
	}

	/**
	 * Return the DB instance that is associated with items of this type.
	 *
	 * @return DB
	 */
	protected function get_db() {
		return get_db( 'activity' );
	}

	/**
	 * Add helper stuff to activity
	 *
	 * @return array
	 */
	public function get_as_array() {
		$array = parent::get_as_array();

		$date = new DateTimeHelper(  $this->get_timestamp() );

		$array['locale'] = [
			'diff_time' => $date->i18n()
		];

		switch ( $this->activity_type ) {
			case 'composed_email_sent':

				$sent_by = get_userdata( $this->get_meta( 'sent_by' ) );

				if ( ! $sent_by ){
					return $this->get_meta( 'from' );
				}

				return array_merge( $array, [
					'sent_by' => $sent_by->display_name
				] );
			case 'email_opened':
			case 'email_link_click':
				return array_merge( $array, [
					'email' => new Email( $this->email_id )
				] );
			default:
				return $array;
		}
	}

	/**
	 * Return a META DB instance associated with items of this type.
	 *
	 * @return Meta_DB
	 */
	protected function get_meta_db() {
		return get_db( 'activitymeta' );
	}
}
