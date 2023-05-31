<?php

namespace Groundhogg\Reporting\New_Reports;


use Groundhogg\Broadcast;
use Groundhogg\Classes\Activity;
use Groundhogg\Plugin;
use function Groundhogg\admin_page_url;
use function Groundhogg\generate_referer_hash;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\percentage;
use function Groundhogg\remove_query_string_from_url;

class Table_Broadcast_Link_Clicked extends Table_Email_Links_Clicked {

	protected function get_broadcast_id() {
		return get_array_var( get_request_var( 'data', [] ), 'broadcast_id' );
	}

	protected function get_activities() {
		$broadcast = new Broadcast( $this->get_broadcast_id() );

		return get_db( 'activity' )->query( [
			'funnel_id'     => $broadcast->get_funnel_id(),
			'step_id'       => $broadcast->get_id(),
			'activity_type' => $broadcast->is_sms() ? Activity::SMS_CLICKED : Activity::EMAIL_CLICKED,
		] );
	}

	protected function get_contact_query_link( $link ) {

		$broadcast = new Broadcast( $this->get_broadcast_id() );

		return admin_page_url( 'gh_contacts', [
			'activity' => [
				'activity_type' => $broadcast->is_sms() ? Activity::SMS_CLICKED : Activity::EMAIL_CLICKED,
				'step_id'       => $broadcast->get_id(),
				'funnel_id'     => $broadcast->get_funnel_id(),
				'referer'       => $link['referer'],
			]
		] );
	}
}
