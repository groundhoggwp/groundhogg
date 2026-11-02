<?php

namespace Groundhogg;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Applies the PII policy, see Pii, to the REST API.
 *
 * Output: every response that carries a contact, a message, an email log entry, a submission, a page visit or an
 * activity row is built with get_as_array(), so the redaction is on the `get_as_array` filters. That covers the
 * contacts routes, the timeline, and anywhere else a contact is embedded.
 *
 * Input: a request that searches by PII is refused before it runs, on `rest_pre_dispatch`. That is every contacts
 * route that takes a Contact_Query, saved searches that filter on PII, and the tables that hold PII, since a count or a
 * match is an answer. A restricted user also can't find out whether an address is already a contact by creating it.
 *
 * Not covered here: the admin UI's server rendered pages and table rows, which is where they are rendered.
 */
class Pii_Rest {

	/**
	 * Routes that are personal by nature, and that a restricted user doesn't get:
	 * - a contact's files and their mailbox
	 * - exports, which are files of contact data
	 *
	 * The admin table row is not blocked, the table redacts its own rows, see Contacts_Table.
	 */
	const BLOCKED_ROUTES = [
		'#^/gh/v4/contacts/\d+/files$#',
		'#^/gh/v4/contacts/\d+/inbox$#',
		'#^/gh/v4/files/exports$#',
	];

	public function __construct() {

		add_filter( 'rest_pre_dispatch', [ $this, 'pre_dispatch' ], 5, 3 );

		// late, so that what other filters added is redacted too
		add_filter( 'groundhogg/contact/get_as_array', [ $this, 'contact_array' ], 99 );
		add_filter( 'groundhogg/log/get_as_array', [ $this, 'email_log_array' ], 99 );
		add_filter( 'groundhogg/message/get_as_array', [ $this, 'message_array' ], 99 );
		add_filter( 'groundhogg/submission/get_as_array', [ $this, 'submission_array' ], 99 );
		add_filter( 'groundhogg/page_visit/get_as_array', [ $this, 'page_visit_array' ], 99 );
		add_filter( 'groundhogg/activity/get_as_array', [ $this, 'activity_array' ], 99 );
		add_filter( 'groundhogg/note/get_as_array', [ $this, 'note_array' ], 99 );
		add_filter( 'groundhogg/task/get_as_array', [ $this, 'note_array' ], 99 );
	}

	/**
	 * Refuse requests a restricted user can't make
	 *
	 * @param mixed           $result
	 * @param WP_REST_Server  $server
	 * @param WP_REST_Request $request
	 *
	 * @return mixed
	 */
	public function pre_dispatch( $result, $server, $request ) {

		$route = $request->get_route();

		if ( ! str_starts_with( $route, '/gh/' ) || ! Pii::is_restricted() ) {
			return $result;
		}

		foreach ( self::BLOCKED_ROUTES as $pattern ) {
			if ( preg_match( $pattern, $route ) ) {
				return Pii::restricted_error();
			}
		}

		$params = $request->get_params();

		// the tables that hold PII, see Pii::table_columns(), are routes of their own name
		foreach ( array_keys( Pii::table_columns() ) as $table ) {

			if ( preg_match( '#^/gh/v4/' . preg_quote( $table, '#' ) . '(/|$)#', $route ) && Pii::table_query_touches_pii( $table, $params ) ) {
				return Pii::restricted_error();
			}
		}

		if ( preg_match( '#^/gh/v4/contacts$#', $route ) ) {
			return $this->check_contacts_request( $request );
		}

		if ( preg_match( '#^/gh/v3/contacts$#', $route ) ) {
			return $this->check_v3_contacts_request( $request );
		}

		if ( preg_match( '#^/gh/v4/searches(/|$)#', $route ) && in_array( $request->get_method(), [ 'POST', 'PUT', 'PATCH' ], true ) ) {

			$query = $request->get_param( 'query' );

			if ( is_array( $query ) && Pii::query_touches_pii( $query ) ) {
				return Pii::restricted_error();
			}
		}

		return $result;
	}

	/**
	 * The contacts collection takes a Contact_Query for reading, bulk updates and bulk deletes, and creates contacts
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|null
	 */
	protected function check_contacts_request( WP_REST_Request $request ) {

		if ( $request->get_method() === 'POST' ) {

			// creating a contact that exists would return it, or refuse, and either one confirms the address is on the list
			foreach ( $this->emails_in_request( $request ) as $email ) {
				if ( is_email_address_in_use( $email ) ) {
					return new WP_Error( 'groundhogg_contact_not_created', __( 'A contact could not be created with this email address.', 'groundhogg' ), [ 'status' => 422 ] );
				}
			}

			return null;
		}

		// the endpoints read a `query` param or, failing that, the request's own params, so check both
		foreach ( [ (array) $request->get_param( 'query' ), $request->get_params() ] as $query ) {

			$allowed = Pii::check_query( $query );

			if ( is_wp_error( $allowed ) ) {
				return $allowed;
			}
		}

		return null;
	}

	/**
	 * The legacy contacts route also looks a contact up by email, and fills pickers with names and addresses
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|null
	 */
	protected function check_v3_contacts_request( WP_REST_Request $request ) {

		if ( $request->get_method() !== 'GET' ) {
			return null;
		}

		foreach ( [ 'email', 'search', 'q', 'select', 'select2' ] as $param ) {
			if ( ! empty( $request->get_param( $param ) ) ) {
				return Pii::restricted_error();
			}
		}

		$allowed = Pii::check_query( (array) $request->get_param( 'query' ) );

		return is_wp_error( $allowed ) ? $allowed : null;
	}

	/**
	 * Every email address a request tries to create a contact with
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return string[]
	 */
	protected function emails_in_request( WP_REST_Request $request ): array {

		$emails = [ $request->get_param( 'email' ) ];
		$data   = $request->get_param( 'data' );

		if ( is_array( $data ) ) {
			$emails[] = $data['email'] ?? '';
		}

		foreach ( (array) $request->get_json_params() as $item ) {

			if ( ! is_array( $item ) ) {
				continue;
			}

			$emails[] = $item['email'] ?? '';
			$emails[] = is_array( $item['data'] ?? null ) ? ( $item['data']['email'] ?? '' ) : '';
		}

		return array_values( array_filter( $emails, fn( $email ) => is_string( $email ) && is_email( $email ) ) );
	}

	/**
	 * A contact. Everything on it that says who they are is redacted, the ID, tags, dates and non personal custom
	 * fields are not.
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	public function contact_array( $array ) {

		if ( ! is_array( $array ) || ! Pii::is_restricted() ) {
			return $array;
		}

		$id = absint( $array['ID'] ?? 0 );

		if ( isset( $array['data'] ) && is_array( $array['data'] ) ) {

			$array['data'] = Pii::redact_fields( $array['data'], array_merge( Pii::contact_fields(), [ 'full_name' ] ) );

			// the picture is a hash of the email address, which would confirm a guess
			if ( isset( $array['data']['gravatar'] ) ) {
				$array['data']['gravatar'] = Pii::blank_picture();
			}

			// worked out from the birthday
			if ( isset( $array['data']['age'] ) ) {
				$array['data']['age'] = 0;
			}
		}

		if ( isset( $array['meta'] ) && is_array( $array['meta'] ) ) {
			$array['meta'] = Pii::filter_meta( $array['meta'] );
		}

		if ( isset( $array['user'] ) && $array['user'] instanceof \JsonSerializable ) {
			$array['user'] = $this->redact_user( $array['user']->jsonSerialize() );
		}

		if ( isset( $array['i18n']['displayAs'] ) ) {
			$array['i18n']['displayAs'] = Pii::contact_label( $id );
		}

		return $array;
	}

	/**
	 * The WordPress user a contact is linked to
	 *
	 * @param array $user
	 *
	 * @return array
	 */
	protected function redact_user( array $user ): array {

		if ( isset( $user['data'] ) && is_array( $user['data'] ) ) {
			$user['data'] = Pii::redact_fields( $user['data'], array_keys( $user['data'] ) );
		}

		return Pii::redact_fields( $user, [ 'from_name', 'from_email' ] );
	}

	/**
	 * @param array $array
	 *
	 * @return array
	 */
	public function email_log_array( $array ) {

		// the object type "log" is shared, only an email log entry has recipients
		if ( ! is_array( $array ) || ! isset( $array['data']['recipients'] ) || ! Pii::is_restricted() ) {
			return $array;
		}

		$array['data'] = Pii::redact_fields( $array['data'], [ 'recipients', 'from_address', 'from_avatar', 'subject', 'content', 'altbody', 'headers', 'error_message', 'msg_id' ] );

		return $array;
	}

	/**
	 * @param array $array
	 *
	 * @return array
	 */
	public function message_array( $array ) {

		if ( ! is_array( $array ) || ! Pii::is_restricted() ) {
			return $array;
		}

		if ( isset( $array['data'] ) && is_array( $array['data'] ) ) {
			$array['data'] = Pii::redact_fields( $array['data'], [ 'from_address', 'to_address', 'subject', 'content', 'summary', 'message_id', 'in_reply_to', 'thread_id' ] );
		}

		if ( isset( $array['preview'] ) ) {
			$array['preview'] = Pii::REDACTED;
		}

		return $array;
	}

	/**
	 * A submission is what someone typed into a form, so what they answered is hidden and the fact that they did is not.
	 * The rendered answers, `i18n.answers`, are added after this filter runs, see Submission::get_as_array().
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	public function submission_array( $array ) {

		if ( ! is_array( $array ) || ! Pii::is_restricted() ) {
			return $array;
		}

		if ( isset( $array['meta'] ) ) {
			$array['meta'] = [];
		}

		return $array;
	}

	/**
	 * A note or a task. What was written can name the contact, so the contact's own details are scrubbed out of it, see
	 * Pii::scrub_text(). A task's `associated` and `activity` are added after this filter runs, see Task::get_as_array().
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	public function note_array( $array ) {

		if ( ! is_array( $array ) || ! isset( $array['data'] ) || ! is_array( $array['data'] ) || ! Pii::is_restricted() ) {
			return $array;
		}

		$contact = Pii::contact_for( (string) ( $array['data']['object_type'] ?? '' ), absint( $array['data']['object_id'] ?? 0 ) );

		foreach ( [ 'content', 'summary' ] as $field ) {
			if ( isset( $array['data'][ $field ] ) && is_string( $array['data'][ $field ] ) ) {
				$array['data'][ $field ] = Pii::scrub_text( $array['data'][ $field ], $contact );
			}
		}

		return $array;
	}

	/**
	 * @param array $array
	 *
	 * @return array
	 */
	public function page_visit_array( $array ) {

		if ( ! is_array( $array ) || ! isset( $array['data'] ) || ! Pii::is_restricted() ) {
			return $array;
		}

		$array['data'] = Pii::redact_fields( $array['data'], [ 'ip_address', 'query', 'fragment' ] );

		return $array;
	}

	/**
	 * @param array $array
	 *
	 * @return array
	 */
	public function activity_array( $array ) {

		if ( ! is_array( $array ) || ! isset( $array['data'] ) || ! Pii::is_restricted() ) {
			return $array;
		}

		$array['data'] = Pii::redact_fields( $array['data'], [ 'ip_address' ] );

		return $array;
	}
}
