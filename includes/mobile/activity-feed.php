<?php

namespace Groundhogg\Mobile;

use DateTime;
use DateTimeZone;
use Groundhogg\Classes\Activity;
use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\Classes\Message;
use Groundhogg\Step;
use Groundhogg\Submission;
use Groundhogg\Utils\DateTimeHelper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use function Groundhogg\as_class;
use function Groundhogg\db;
use function Groundhogg\do_replacements;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_managed_page_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A short, recent list of what has happened with a contact, for the mobile contact record.
 *
 * The desktop timeline endpoint (`contacts/{id}/timeline`) returns everything in a date range, including full email
 * bodies and flow definitions, which is megabytes for a busy contact. This is the opposite: a handful of plain lines
 * ("Email sent", "Opened an email", "Visited /pricing"), newest first, a page at a time.
 *
 * `GET gh/v4/mobile/contacts/{id}/activity?limit=15&before=<unix time>`
 *
 * Entries have: id, type, icon, title, detail, time, time_diff and tone ('', or 'negative' for failures,
 * bounces and unsubscribes). `before` is inclusive, so the last entry of a page comes back first on the next one and
 * the client drops it by id. Add-ons add their own entries with the `groundhogg/mobile/activity_feed` filter.
 */
class Activity_Feed {

	const DEFAULT_LIMIT = 15;
	const MAX_LIMIT     = 50;

	/**
	 * How many batches one source reads through to find enough entries, see collect().
	 */
	const MAX_BATCHES = 4;

	/**
	 * The fewest rows a source reads at a time, so a short page doesn't mean many small queries.
	 */
	const MIN_BATCH = 25;

	/**
	 * @var string[] subject lines by email id, so one email that was sent many times is looked up once
	 */
	protected $subjects = [];

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		register_rest_route( 'gh/v4', '/mobile/contacts/(?P<id>\d+)/activity', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'read' ],
				'permission_callback' => [ $this, 'permissions_callback' ],
				'args'                => [
					'limit'  => [
						'default'           => self::DEFAULT_LIMIT,
						'sanitize_callback' => 'absint',
					],
					'before' => [
						'default'           => 0,
						'sanitize_callback' => 'absint',
					],
				],
			],
		] );
	}

	/**
	 * Whoever can view the contact can see what has happened with them, which is what the desktop timeline checks.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function permissions_callback( WP_REST_Request $request ) {

		$contact = get_contactdata( absint( $request->get_param( 'id' ) ) );

		if ( ! $contact || ! $contact->exists() ) {
			return new WP_Error( 'contact_not_found', 'Contact not found.', [ 'status' => 404 ] );
		}

		return current_user_can( 'view_contact', $contact );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function read( WP_REST_Request $request ) {

		$limit   = max( 1, min( self::MAX_LIMIT, absint( $request->get_param( 'limit' ) ) ?: self::DEFAULT_LIMIT ) );
		$before  = absint( $request->get_param( 'before' ) );
		$contact = get_contactdata( absint( $request->get_param( 'id' ) ) );

		return new WP_REST_Response( $this->for_contact( $contact, $limit, $before ) );
	}

	/**
	 * @param Contact $contact
	 * @param int     $limit
	 * @param int     $before unix time, 0 for the newest
	 *
	 * @return array{items: array[], has_more: bool, next_before: int|null}
	 */
	public function for_contact( Contact $contact, int $limit = self::DEFAULT_LIMIT, int $before = 0 ): array {

		// one more than a page from each source, so we know whether there is another page
		$fetch = $limit + 1;

		$entries = array_merge(
			$this->events( $contact, $fetch, $before ),
			$this->activity( $contact, $fetch, $before ),
			$this->messages( $contact, $fetch, $before ),
			$this->submissions( $contact, $fetch, $before ),
			$this->page_visits( $contact, $fetch, $before )
		);

		/**
		 * Add entries to a contact's mobile activity list.
		 *
		 * Each entry is an array with `id` (unique), `type`, `icon` (one of the mobile app's icon names), `title`,
		 * `detail`, `time` (unix time) and optionally `tone` ('negative'). Return the entries array.
		 *
		 * @param array[] $entries
		 * @param Contact $contact
		 * @param int     $limit
		 * @param int     $before
		 */
		$entries = apply_filters( 'groundhogg/mobile/activity_feed', $entries, $contact, $limit, $before );

		// newest first, and a stable order for entries in the same second
		usort( $entries, function ( $a, $b ) {
			return [ $b['time'], $b['id'] ] <=> [ $a['time'], $a['id'] ];
		} );

		$has_more = count( $entries ) > $limit;
		$entries  = array_slice( $entries, 0, $limit );
		$now      = time();

		foreach ( $entries as &$entry ) {
			$entry['time_diff'] = human_time_diff( $entry['time'], $now );
		}

		unset( $entry );

		return [
			'items'       => $entries,
			'has_more'    => $has_more,
			'next_before' => $entries ? (int) end( $entries )['time'] : null,
		];
	}

	/**
	 * @param string $id
	 * @param string $type
	 * @param string $icon
	 * @param string $title
	 * @param string $detail
	 * @param int    $time
	 * @param string $tone
	 *
	 * @return array
	 */
	protected function entry( string $id, string $type, string $icon, string $title, string $detail, int $time, string $tone = '' ): array {
		return [
			'id'     => $id,
			'type'   => $type,
			'icon'   => $icon,
			'title'  => $title,
			'detail' => $detail,
			'time'   => $time,
			'tone'   => $tone,
		];
	}

	/**
	 * The query args every source shares: this contact, newest first, no total, and not past the cursor.
	 *
	 * @param Contact $contact
	 * @param int     $limit
	 * @param int     $before
	 *
	 * @return array
	 */
	protected function base_query( Contact $contact, int $limit, int $before ): array {
		$query = [
			'contact_id' => $contact->get_id(),
			'order'      => 'DESC',
			'limit'      => $limit,
			'found_rows' => false,
		];

		if ( $before ) {
			$query['before'] = $before;
		}

		return $query;
	}

	/**
	 * Read rows from a source until there are enough entries, or the source runs out.
	 *
	 * Some rows aren't entries (a flow step that isn't an email, a visit to the site's own page, a message the user
	 * can't see). If they were dropped after a single query, a page could come back short and look like the last one
	 * when there is more behind a run of them. So this reads in batches, and caps how many it will look through, so a
	 * contact with thousands of noise rows can't make one request scan them all.
	 *
	 * @param callable $query  fn( int $batch, int $offset ): array of rows
	 * @param callable $to_entry fn( $row ): array|null an entry, or null to skip the row
	 * @param int      $needed
	 *
	 * @return array[]
	 */
	protected function collect( callable $query, callable $to_entry, int $needed ): array {

		$out   = [];
		$batch = max( $needed, self::MIN_BATCH );

		for ( $page = 0; $page < self::MAX_BATCHES; $page ++ ) {

			$rows = $query( $batch, $page * $batch );

			foreach ( $rows as $row ) {

				$entry = $to_entry( $row );

				if ( $entry ) {
					$out[] = $entry;
				}

				if ( count( $out ) >= $needed ) {
					return $out;
				}
			}

			// a short batch means there is nothing further
			if ( count( $rows ) < $batch ) {
				break;
			}
		}

		return $out;
	}

	/**
	 * The subject line of an email, with the contact's details in, as they'd have read it.
	 *
	 * @param \Groundhogg\Email|false $email
	 * @param Contact                 $contact
	 *
	 * @return string
	 */
	protected function subject( $email, Contact $contact ): string {

		if ( ! $email || ! $email->exists() ) {
			return '';
		}

		$key = $email->get_id();

		if ( ! isset( $this->subjects[ $key ] ) ) {
			$this->subjects[ $key ] = wp_strip_all_tags( do_replacements( (string) $email->subject, $contact ) );
		}

		return $this->subjects[ $key ];
	}

	/**
	 * Emails that went out. Flow steps that aren't emails (apply a tag, wait) are skipped, they're noise here.
	 */
	protected function events( Contact $contact, int $limit, int $before ): array {

		return $this->collect( function ( int $batch, int $offset ) use ( $contact, $before ) {

			return as_class( db()->events->query( array_merge( $this->base_query( $contact, $batch, $before ), [
				'orderby' => 'time',
				'offset'  => $offset,
				'status'  => [ Event::COMPLETE, Event::FAILED ],
			] ) ), Event::class );

		}, function ( Event $event ) use ( $contact ) {

			$subject = $this->subject( $event->get_email(), $contact );

			if ( $subject === '' ) {
				return null;
			}

			$failed = $event->status === Event::FAILED;

			return $this->entry(
				'event-' . $event->get_id(),
				$failed ? 'email_failed' : 'email_sent',
				'mail',
				$failed ? __( 'Email failed to send', 'groundhogg' ) : __( 'Email sent', 'groundhogg' ),
				$subject,
				(int) $event->get_time(),
				$failed ? 'negative' : ''
			);

		}, $limit );
	}

	/**
	 * Opens, clicks, bounces and unsubscribes. Page views come from the page visits table, forms from submissions. Logins
	 * are left out: on a contact that is also a user they would crowd out everything else.
	 */
	protected function activity( Contact $contact, int $limit, int $before ): array {

		$types = [
			Activity::EMAIL_OPENED,
			Activity::EMAIL_CLICKED,
			Activity::SMS_CLICKED,
			Activity::UNSUBSCRIBED,
			Activity::BOUNCE,
			Activity::SOFT_BOUNCE,
			Activity::COMPLAINT,
		];

		$rows = as_class( db()->activity->query( array_merge( $this->base_query( $contact, $limit, $before ), [
			'orderby'       => 'timestamp',
			'activity_type' => $types,
		] ) ), Activity::class );

		$out = [];

		foreach ( $rows as $activity ) {

			$type   = $activity->activity_type;
			$detail = '';
			$tone   = '';

			switch ( $type ) {
				case Activity::EMAIL_OPENED:
					$icon   = 'eye';
					$title  = __( 'Opened an email', 'groundhogg' );
					$detail = $this->subject( $activity->email_id ? new \Groundhogg\Email( $activity->email_id ) : false, $contact );
					break;
				case Activity::EMAIL_CLICKED:
					$icon   = 'link';
					$title  = __( 'Clicked a link in an email', 'groundhogg' );
					$detail = $this->short_url( (string) $activity->referer );
					break;
				case Activity::SMS_CLICKED:
					$icon  = 'link';
					$title = __( 'Clicked a link in a text', 'groundhogg' );
					break;
				case Activity::UNSUBSCRIBED:
					$icon  = 'alert';
					$title = __( 'Unsubscribed', 'groundhogg' );
					$tone  = 'negative';
					break;
				case Activity::BOUNCE:
					$icon  = 'alert';
					$title = __( 'Email bounced', 'groundhogg' );
					$tone  = 'negative';
					break;
				case Activity::SOFT_BOUNCE:
					$icon  = 'alert';
					$title = __( 'Email soft bounced', 'groundhogg' );
					$tone  = 'negative';
					break;
				case Activity::COMPLAINT:
					$icon  = 'alert';
					$title = __( 'Marked an email as spam', 'groundhogg' );
					$tone  = 'negative';
					break;
				default:
					continue 2;
			}

			$out[] = $this->entry( 'activity-' . $activity->get_id(), $type, $icon, $title, $detail, (int) $activity->timestamp, $tone );
		}

		return $out;
	}

	/**
	 * Emails written in the app or on the desktop, and ones the contact sent back.
	 */
	protected function messages( Contact $contact, int $limit, int $before ): array {

		return $this->collect( function ( int $batch, int $offset ) use ( $contact, $before ) {

			$query = [
				'object_type' => 'contact',
				'object_id'   => $contact->get_id(),
				'order'       => 'DESC',
				'orderby'     => 'date_created',
				'limit'       => $batch,
				'offset'      => $offset,
				'found_rows'  => false,
			];

			if ( $before ) {
				$query['before'] = $before;
			}

			return as_class( db()->messages->query( $query ), Message::class );

		}, function ( Message $message ) {

			if ( ! current_user_can( 'view_message', $message ) ) {
				return null;
			}

			$inbound = $message->is_inbound();
			$sent_by = $inbound ? false : get_userdata( (int) $message->user_id );
			$time    = ( new DateTime( $message->date_created, new DateTimeZone( 'UTC' ) ) )->getTimestamp();

			if ( $inbound ) {
				$title = __( 'Email received', 'groundhogg' );
			} else if ( $sent_by ) {
				/* translators: %s: the name of the person that sent the email */
				$title = sprintf( __( 'Email sent by %s', 'groundhogg' ), $sent_by->display_name );
			} else {
				$title = __( 'Email sent', 'groundhogg' );
			}

			return $this->entry( 'message-' . $message->get_id(), $inbound ? 'message_in' : 'message_out', 'mail', $title, (string) $message->subject, $time );

		}, $limit );
	}

	protected function submissions( Contact $contact, int $limit, int $before ): array {

		$out = [];

		foreach ( as_class( db()->submissions->query( array_merge( $this->base_query( $contact, $limit, $before ), [ 'orderby' => 'date_created' ] ) ), Submission::class ) as $submission ) {

			$form = $submission->get_form_id() ? new Step( $submission->get_form_id() ) : false;
			$time = ( new DateTimeHelper( $submission->get_date_created(), wp_timezone() ) )->getTimestamp();

			$out[] = $this->entry(
				'submission-' . $submission->get_id(),
				'form_submitted',
				'note',
				__( 'Submitted a form', 'groundhogg' ),
				$form && $form->exists() ? $form->get_title() : '',
				$time
			);
		}

		return $out;
	}

	/**
	 * Pages on the site they looked at, other than the site's own contact pages (preferences, archive and so on).
	 */
	protected function page_visits( Contact $contact, int $limit, int $before ): array {

		$own = '/' . get_managed_page_name() . '/';

		return $this->collect( function ( int $batch, int $offset ) use ( $contact, $before ) {

			return db()->page_visits->query( array_merge( $this->base_query( $contact, $batch, $before ), [
				'orderby' => 'timestamp',
				'offset'  => $offset,
			] ) );

		}, function ( $visit ) use ( $own ) {

			$path = (string) $visit->path;

			if ( $path === '' || strpos( $path, $own ) === 0 ) {
				return null;
			}

			return $this->entry( 'visit-' . $visit->ID, 'page_visit', 'globe', __( 'Visited a page', 'groundhogg' ), $path, (int) $visit->timestamp );

		}, $limit );
	}

	/**
	 * A link shortened to the host and path, which is what someone scanning a list wants to read.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	protected function short_url( string $url ): string {

		$parts = wp_parse_url( $url );

		if ( empty( $parts['host'] ) ) {
			return $url;
		}

		return preg_replace( '#^www\.#', '', $parts['host'] ) . rtrim( $parts['path'] ?? '', '/' );
	}
}
