<?php

namespace Groundhogg\Classes;

use Groundhogg\Base_Object;
use Groundhogg\Base_Object_With_Meta;
use Groundhogg\DB\DB;
use Groundhogg\DB\Meta_DB;
use function Groundhogg\get_db;

class Activity extends Base_Object_With_Meta {
	const EMAIL_OPENED = 'email_opened';
	const EMAIL_CLICKED = 'email_link_click';
	const FORM_IMPRESSION = 'form_impression';
	const FORM_SUBMISSION = 'form_submission';
	const UNSUBSCRIBED = 'unsubscribed';
	const PAGE_VIEW = 'page_view';

	/**
	 * Do any post setup actions.
	 *
	 * @return void
	 */
	protected function post_setup() {
		// TODO: Implement post_setup() method.
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
	 * Return a META DB instance associated with items of this type.
	 *
	 * @return Meta_DB
	 */
	protected function get_meta_db() {
		return get_db( 'activitymeta' );
	}
}